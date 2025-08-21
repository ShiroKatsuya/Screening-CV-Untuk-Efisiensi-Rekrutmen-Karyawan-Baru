<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateUploadRequest;
use App\Models\Candidate;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use GuzzleHttp\Client;

class CandidateController extends Controller
{
    public function index(Request $request): View
    {
        $candidates = Candidate::query()
            ->when($request->input('q'), function ($query, $q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('position_applied', 'like', "%{$q}%");
            })
            ->orderByDesc('score')
            ->paginate(10)
            ->withQueryString();

        return view('candidates.index', compact('candidates'));
    }

    public function create(): View
    {
        return view('candidates.create');
    }

    public function store(CandidateUploadRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $file = $request->file('cv_file');
        $path = $file->store('cvs', 'public');

        $cvText = $this->extractTextFromFile(storage_path('app/public/' . $path));

        $candidate = Candidate::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'position_applied' => $validated['position_applied'],
            'skills' => $validated['skills'] ?? null,
            'years_experience' => $validated['years_experience'] ?? 0,
            'education_level' => $validated['education_level'] ?? null,
            'cv_file_path' => $path,
            'cv_text' => $cvText,
        ]);

        [$features, $score, $recommendation] = $this->analyzeAndScore($candidate);

        $candidate->update([
            'features' => $features,
            'score' => $score,
            'recommendation' => $recommendation,
        ]);

        return redirect()->route('candidates.index')
            ->with('success', 'CV berhasil diunggah dan dianalisis.');
    }

    public function show(Candidate $candidate): View
    {
        return view('candidates.show', compact('candidate'));
    }

    protected function extractTextFromFile(string $absolutePath): string
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        try {
            if ($extension === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($absolutePath);
                return trim($pdf->getText());
            }
            if (in_array($extension, ['doc', 'docx'])) {
                $phpWord = WordIOFactory::load($absolutePath);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    $elements = $section->getElements();
                    foreach ($elements as $element) {
                        if (method_exists($element, 'getElements')) {
                            foreach ($element->getElements() as $child) {
                                if (method_exists($child, 'getText')) {
                                    $text .= ' ' . $child->getText();
                                }
                            }
                        } elseif (method_exists($element, 'getText')) {
                            $text .= ' ' . $element->getText();
                        }
                    }
                }
                return trim($text);
            }
        } catch (\Throwable $e) {
            Log::error('CV text extraction failed: ' . $e->getMessage());
        }
        return '';
    }

    protected function analyzeAndScore(Candidate $candidate): array
    {
        $baseUri = config('services.ml_service.base_uri');
        Log::info('ML Service base URI: ' . $baseUri);
        
        $client = new Client([
            'base_uri' => $baseUri,
            'timeout' => 30,
            'verify' => false, // Disable SSL verification for local development
        ]);

        $payload = [
            'name' => $candidate->name,
            'position_applied' => $candidate->position_applied,
            'skills' => $candidate->skills,
            'years_experience' => $candidate->years_experience,
            'education_level' => $candidate->education_level,
            'cv_text' => $candidate->cv_text,
        ];

        Log::info('Sending payload to ML service:', $payload);

        try {
            $response = $client->post('/score', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            
            $responseBody = $response->getBody()->getContents();
            Log::info('ML Service response: ' . $responseBody);
            
            $data = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode JSON response: ' . json_last_error_msg());
                return [[], null, 'Invalid response from ML service'];
            }
            
            return [
                $data['features'] ?? [],
                $data['score'] ?? null,
                $data['recommendation'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('ML scoring failed: ' . $e->getMessage());
            Log::error('ML scoring failed details: ' . $e->getTraceAsString());
            return [[], null, 'ML service unavailable'];
        }
    }
}




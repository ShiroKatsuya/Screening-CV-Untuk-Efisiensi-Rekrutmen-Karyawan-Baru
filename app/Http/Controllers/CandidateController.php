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
        if (!$file) {
            return redirect()->back()->withErrors(['cv_file' => 'CV file is required.']);
        }
        $path = $file->store('cvs', 'public');

        $cvText = $this->extractTextFromFile(storage_path('app/public/' . $path));
        
        // Additional safety check for cv_text
        if (!empty($cvText)) {
            $cvText = $this->cleanText($cvText);
            Log::info('CV text extracted and cleaned. Length: ' . strlen($cvText));
        } else {
            Log::warning('No CV text extracted from file: ' . $path);
        }

        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to create candidate: ' . $e->getMessage());
            Log::error('CV text length: ' . strlen($cvText));
            Log::error('CV text preview: ' . substr($cvText, 0, 100));
            
            // Clean up the uploaded file
            Storage::disk('public')->delete($path);
            
            return redirect()->back()
                ->withErrors(['cv_file' => 'Failed to process CV file. Please try again with a different file.'])
                ->withInput();
        }

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
                $text = trim($pdf->getText());
                return $this->cleanText($text);
            }
            if (in_array($extension, ['doc', 'docx'])) {
                try {
                    $phpWord = WordIOFactory::load($absolutePath);
                    $text = '';
                    
                    // Extract text from Word documents using PhpWord
                    foreach ($phpWord->getSections() as $section) {
                        $elements = $section->getElements();
                        foreach ($elements as $element) {
                            if (is_object($element) && method_exists($element, 'getText')) {
                                $text .= ' ' . $element->getText();
                            }
                        }
                    }
                    
                    return $this->cleanText(trim($text));
                } catch (\Throwable $e) {
                    Log::error('Word document parsing failed: ' . $e->getMessage());
                    return '';
                }
            }
        } catch (\Throwable $e) {
            Log::error('CV text extraction failed: ' . $e->getMessage());
        }
        return '';
    }

    /**
     * Clean and validate text to ensure it's safe for database storage
     */
    protected function cleanText(string $text): string
    {
        // Remove null bytes and other control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Remove invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove any remaining invalid characters
        $text = preg_replace('/[\x80-\x9F]/', '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Ensure the text is not too long for the database column
        $maxLength = 65535; // LONGTEXT max length
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }
        
        return trim($text);
    }

    /**
     * Test method to verify text cleaning functionality
     * This can be removed in production
     */
    public function testTextCleaning(Request $request)
    {
        $testText = $request->input('text', '');
        $cleanedText = $this->cleanText($testText);
        
        return response()->json([
            'original' => $testText,
            'cleaned' => $cleanedText,
            'original_length' => strlen($testText),
            'cleaned_length' => strlen($cleanedText),
            'is_valid_utf8' => mb_check_encoding($cleanedText, 'UTF-8'),
        ]);
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

        // Ensure cv_text is properly encoded for JSON
        $cvText = $candidate->cv_text;
        if (!empty($cvText)) {
            $cvText = $this->cleanText($cvText);
        }
        
        $payload = [
            'name' => $candidate->name,
            'position_applied' => $candidate->position_applied,
            'skills' => $candidate->skills,
            'years_experience' => $candidate->years_experience,
            'education_level' => $candidate->education_level,
            'cv_text' => $cvText,
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
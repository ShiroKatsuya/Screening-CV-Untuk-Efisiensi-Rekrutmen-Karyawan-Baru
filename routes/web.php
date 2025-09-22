<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidateController;

Route::get('/', [CandidateController::class, 'index'])->name('candidates.index');
Route::get('/candidates/create', [CandidateController::class, 'create'])->name('candidates.create');
Route::post('/candidates', [CandidateController::class, 'store'])->name('candidates.store');
Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])->name('candidates.show');

// Test route for text cleaning functionality
Route::post('/test-text-cleaning', [CandidateController::class, 'testTextCleaning'])->name('test.text.cleaning');

// Test route for ML service connection
Route::get('/test-ml', function() {
    $client = new \GuzzleHttp\Client([
        'base_uri' => config('services.ml_service.base_uri'),
        'timeout' => 10,
        'verify' => false,
    ]);
    
    try {
        $response = $client->get('/');
        return response()->json([
            'status' => 'success',
            'message' => 'ML service is accessible',
            'response' => $response->getBody()->getContents()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'ML service connection failed',
            'error' => $e->getMessage(),
            'base_uri' => config('services.ml_service.base_uri')
        ], 500);
    }
});

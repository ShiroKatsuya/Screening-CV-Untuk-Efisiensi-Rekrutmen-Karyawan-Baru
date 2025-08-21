<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

echo "Testing ML Service Connection...\n";
echo "================================\n";

$baseUri = 'http://127.0.0.1:8484';
echo "Base URI: $baseUri\n\n";

// Test 1: Basic connectivity
echo "Test 1: Basic connectivity to root endpoint\n";
try {
    $client = new Client([
        'base_uri' => $baseUri,
        'timeout' => 10,
        'verify' => false,
    ]);
    
    $response = $client->get('/');
    echo "✓ Success! Response: " . $response->getBody()->getContents() . "\n\n";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Scoring endpoint
echo "Test 2: Scoring endpoint\n";
try {
    $payload = [
        'name' => 'Test Candidate',
        'position_applied' => 'Data Engineer',
        'skills' => 'Python, SQL, Machine Learning',
        'years_experience' => 3,
        'education_level' => 'sarjana',
        'cv_text' => 'Experienced data engineer with Python and SQL skills. Worked on machine learning projects.',
    ];
    
    $response = $client->post('/score', [
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'json' => $payload,
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    echo "✓ Success! Score: " . ($data['score'] ?? 'N/A') . "\n";
    echo "  Recommendation: " . ($data['recommendation'] ?? 'N/A') . "\n";
    echo "  Features: " . json_encode($data['features'] ?? []) . "\n\n";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
}

echo "Test completed.\n";

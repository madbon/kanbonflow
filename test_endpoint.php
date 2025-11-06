<?php
// Simple test to check if the endpoint works
$url = 'http://localhost:8081/kanban/board/get-deadline-tasks?category=due_today';

echo "Testing endpoint: $url\n\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'method' => 'GET'
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Failed to get response\n";
} else {
    echo "Response received:\n";
    echo $response . "\n";
}
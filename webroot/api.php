<?php

function exception_error_handler($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

try {
    $_POST = json_decode(file_get_contents("php://input"), true);
    $_SERVER = $_POST['meta']['server'];
    
    $uri = array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : null;
    if (empty($uri)) {
        $cmd = basename($_SERVER['argv'][0]);
        $uri = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1, 1));
    }

    $time = empty($_SERVER['REQUEST_TIME']) ? (new DateTime())->getTimestamp() : $_SERVER['REQUEST_TIME'];
    $milliseconds = empty($_SERVER['REQUEST_TIME_FLOAT']) ? (new DateTime())->getTimestamp() : $_SERVER['REQUEST_TIME_FLOAT'];

    $manager = new MongoDB\Driver\Manager('mongodb://mongodb:27017');
    $bulk = new MongoDB\Driver\BulkWrite;

    $data = [
        'profile' => $_POST['profile'],
        'meta' => [
            'url' => $uri,
            'SERVER' => $_SERVER,
            'get' => $_POST['meta']['get'] ?? [],
            'env' => $_POST['meta']['env'],
            'simple_url' => preg_replace('/\d+$/', '', $uri),
            'request_ts' => new MongoDB\BSON\UTCDateTime(DateTime::createFromFormat('U', $time)),
            'request_ts_micro' => new MongoDB\BSON\UTCDateTime(preg_replace('/\D/', '',  $milliseconds)),
            'request_date' => date('Y-m-d', $time),
            'topic' => $_POST['meta']['method'],
            'params' => $_POST['meta']['params'],
            'queries' => $_POST['meta']['queries'],
        ]
    ];

    $bulk->insert($data);
    $manager->executeBulkWrite('xhprof.results', $bulk);
} catch (Throwable $e) {
    header('HTTP/1.1 500 Internal Server Error');
    error_log($e->getMessage().':'.$e->getLine());
}

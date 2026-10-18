<?php
// Exercise the real front controller and built-in server; no framework test mocks.
$root = dirname(__DIR__);
$listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$listener) {
    throw new RuntimeException($error);
}
$address = stream_socket_get_name($listener, false);
fclose($listener);
$log = tmpfile();
$server = proc_open(array(PHP_BINARY, '-S', $address, '-t', '.'),
    array(0 => array('pipe', 'r'), 1 => $log, 2 => $log), $pipes, $root.'/public');
if (!is_resource($server)) {
    throw new RuntimeException('Could not start PHP HTTP server.');
}
fclose($pipes[0]);
try {
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $ready = @stream_socket_client('tcp://'.$address, $errno, $error, 0.05);
        if ($ready) {
            fclose($ready);
            break;
        }
        usleep(50000);
    }
    foreach (array('/' => array(200, '<h1>Welcome!</h1>'),
        '/hello/LeFuel' => array(200, 'Hello, LeFuel'),
        '/does-not-exist' => array(404, "We can't find that!"),
        '/assets/css/bootstrap.css' => array(200, 'Bootstrap v3')) as $path => $expected) {
        // PHP 8.5 deprecates $http_response_header; use stream metadata instead.
        $stream = fopen('http://'.$address.$path, 'r', false,
            stream_context_create(array('http' => array('ignore_errors' => true, 'timeout' => 5))));
        $body = stream_get_contents($stream);
        $headers = stream_get_meta_data($stream)['wrapper_data'];
        fclose($stream);
        if (!preg_match('/^HTTP\/\S+ '.$expected[0].'\b/', $headers[0]) ||
            strpos($body, $expected[1]) === false ||
            preg_match('/Deprecated|Fatal error|No composer autoloader|PhpErrorException/', $body)) {
            throw new RuntimeException('FAIL: '.$path.' expected '.$expected[0].' and application content; got '.$headers[0].' / '.substr(strip_tags($body), 0, 180));
        }
        echo 'PASS: GET '.$path.' '.$expected[0]." with expected content.\n";
    }
} finally {
    proc_terminate($server);
    proc_close($server);
    fclose($log);
}

<?php

\XH::start();

class XH
{
    static function start() 
    {
        if (!extension_loaded('tideways_xhprof')) {
            error_log('xhgui - either extension tideways must be loaded');
            return;
        }
        tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY);
        $input = file_get_contents('php://input');
        register_shutdown_function(
            function () use ($input) {
                \XH::stop($input);
            }
        );
    }

    static function stop($inputData = null)
    {
        ignore_user_abort(true);
        flush();

        $method = null;
        $params = null;

        if ($inputData !== null) {
            $parsedData = json_decode($inputData, true);

            if (array_key_exists('method', $parsedData)) {
                $method = $parsedData['method'];
            }

            if (array_key_exists('params', $parsedData)) {
                $params = $parsedData['params'];
            }
        }

        $data = [
            'profile' => tideways_xhprof_disable(),
            'meta' => [
                'server' => $_SERVER,
                'get' => $_GET,
                'env' => $_ENV,
                'method' => $method,
                'params' => $params,
            ]
        ];

        try {
            self::send('http://10.0.2.2:8088/api.php', $data);
        } catch (Exception $e) {
            error_log('xhgui - ' . $e->getMessage());
        }
    }

    private static function send($url, $data)
    {
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            error_log(file_get_contents('php://input'));
            throw new Exception('fail to send data');
        }
    }
}

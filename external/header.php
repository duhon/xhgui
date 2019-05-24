<?php

require_once __DIR__ . "/../oms/MemoryDbLogger.php";

\XH::patchDbConnection();
\XH::start();

define('PATH_TO_XHGUI_API', 'http://10.0.2.2:8088/api.php');

class XH
{
    static function getRequestInput()
    {
        $input = null;

        // check if request is via http
        if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
        }

        // check if request is via console
        if (array_key_exists('argv', $_SERVER) && basename($_SERVER['argv'][0]) == 'console') {
            stream_set_blocking(STDIN, false);
            $raw = stream_get_contents(STDIN);

            if (!empty($raw)) {
                array_push($_SERVER['argv'], $raw);
            }

            $json = base64_decode($raw, true);
            $input = json_decode($json, true);
        }

        // check if request is via job
        if (array_key_exists('argv', $_SERVER) && basename($_SERVER['argv'][0]) == 'job') {
            $input = [
                'method' => $_SERVER['argv'][2] ?? '',
                'params' => array_slice($_SERVER['argv'], 1)
            ];
        }

        return $input;
    }

    static function getTopicFromInput($input)
    {
        $topic = null;

        if (!empty($input) && is_array($input)) {
            if (array_key_exists('method', $input)) {
                $topic = $input['method'];
            }

            if ($topic === null && array_key_exists('delivery_info', $input) && array_key_exists('routing_key', $input['delivery_info'])) {
                $topic = $input['delivery_info']['routing_key'];
            }

        }

        return $topic;
    }

    static function getParamsFromInput($input)
    {
        $params = null;

        if (!empty($input) && is_array($input)) {
            if (array_key_exists('params', $input)) {
                $params = $input['params'];
            }

            if ($params === null && array_key_exists('body', $input)) {
                $params = json_decode($input['body'], true);
            }

        }

        return $params;
    }

    static function start()
    {
        if (!extension_loaded('tideways_xhprof')) {
            error_log('xhgui - either extension tideways must be loaded');
            return;
        }
        tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY);

        $input = self::getRequestInput();

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

        $data = [
            'profile' => tideways_xhprof_disable(),
            'meta' => [
                'server' => $_SERVER,
                'get' => $_GET,
                'env' => $_ENV,
                'method' => self::getTopicFromInput($inputData),
                'params' => self::getParamsFromInput($inputData),
                'queries' => OMS\MemoryDbLogger::getQueries()
            ]
        ];

        try {
            self::send(PATH_TO_XHGUI_API, $data);
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

    static function patchDbConnection()
    {
        require_once __DIR__ . "/../vendor/autoload.php";
        require_once __DIR__ . "/../vendor/antecedent/patchwork/Patchwork.php";

        Patchwork\redefine(
            "Doctrine\DBAL\Connection::executeQuery",
            function() {
                $s = microtime(true);
                $r = Patchwork\relay();
                $f = microtime(true);

                OMS\MemoryDbLogger::logQuery(func_get_args(), $f - $s);

                return $r;
            }
        );

        if (!class_exists(Doctrine\DBAL\Connection::class)) {
            error_log("Can't patch Doctrine\DBAL\Connection class");
        }

        Patchwork\CodeManipulation\Stream::unwrap();
    }
}

<?php
namespace Logger;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Class Utils
 * @package Logger
 */
class Utils
{
    /**
     * @return string
     */
    public static function getUrl()
    {
        $url = 'http://';
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $url = 'https://';
        }

        if($_SERVER['SERVER_PORT'] != '80') {
            $url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . ':' . $_SERVER['REQUEST_URI'];
        } else {
            $url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }

        return $url;
    }

    /**
     * @return array
     */
    public static function getHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if ('HTTP_' == substr($key, 0, 5)) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }
        return $headers;
    }

    /**
     * @return array
     */
    public static function getRequestParams()
    {
        return [
            'GET' => $_GET,
            'POST' => $_POST,
            'FILE' => $_FILES
        ];
    }

    /**
     * @return array|false|mixed|string
     */
    public static function getIp()
    {
        $realip = '';
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else {
                if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                    $realip = $_SERVER["HTTP_CLIENT_IP"];
                } else {
                    $realip = !empty($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '127.0.0.1';
                }
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else {
                if (getenv("HTTP_CLIENT_IP")) {
                    $realip = getenv("HTTP_CLIENT_IP");
                } else {
                    $realip = getenv("REMOTE_ADDR");
                }
            }
        }

        return $realip;
    }

    public static function getHtml(\Exception $exception)
    {
        $run     = new Run();
        $handler = new PrettyPageHandler();
        $handler->handleUnconditionally(true);
        $run->pushHandler($handler)->writeToOutput(false);
        $run->allowQuit(false);
        return $run->handleException($exception);
    }
}

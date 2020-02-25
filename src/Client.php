<?php
namespace Logger;

/**
 * Class Client
 */
class Client
{
    /**
     * 项目名称
     * @var string
     */
    private $project;

    /**
     * logger服务器端域名
     * @var string
     */
    private $host;

    /**
     * http请求超时时间
     * @var int
     */
    private $http_request_timeout = 5;

    private $common_log = [];

    /**
     * Logger constructor.
     * @param string $host
     * @param string $project
     */
    function __construct(string $host, string $project)
    {
        $this->host = $host;
        $this->project = $project;
    }

    /**
     * 推送异常消息
     * @param \Exception $exception
     */
    public function exception(\Exception $exception)
    {
        $this->send([
            'project' => $this->project,
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'html' => Utils::getHtml($exception),
            'header' => json_encode(Utils::getHeaders(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'cookie' => json_encode($_COOKIE,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'session' => json_encode($_SESSION??[],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'server' => json_encode($_SERVER,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'request_url' => Utils::getUrl(),
            'method' => $_SERVER['REQUEST_METHOD'],
            'params' => json_encode(Utils::getRequestParams(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'ip' => Utils::getIp(),
        ],'/exception');
    }

    /**
     * 推送普通日志
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function common_log(string $level,string $message,array $context = [])
    {
        $this->common_log[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
    }

    /**
     *
     */
    public function sql_log()
    {

    }

    /**
     *
     */
    public function cron_log(\Closure $callback)
    {

    }

    /**
     * 发送请求日志
     * @param \Closure $callback
     * @return mixed
     */
    public function request_log(\Closure $callback)
    {
        $starttime = microtime(true);
        $response = $callback();
        $memory = memory_get_peak_usage(true);
        $endtime = microtime(true);

        if(method_exists($response,'__toString'))
        {
            $response = $response->__toString();
        }
        else if(method_exists($response,'getContent'))
        {
            $response = $response->getContent();
        }
        else if(method_exists($response,'getContents'))
        {
            $response = $response->getContents();
        }
        else if (is_scalar($response))
        {
            $response = $response;
        }
        else if (is_array($response))
        {
            $response = json_encode($response,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        else
        {
            $response = var_export($response,true);
        }

        $this->send([
            'project' => $this->project,
            'url'=>Utils::getUrl(),
            'method' => $_SERVER['REQUEST_METHOD'],
            'params' => json_encode(Utils::getRequestParams(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'header' => json_encode(Utils::getHeaders(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'cookie'=> json_encode($_COOKIE,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'session' => json_encode($_SESSION??[],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'server' => json_encode($_SERVER,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'ip' => Utils::getIp(),
            'response' => substr($response,0,65535),
            'exectime' =>$endtime - $starttime,
            'memory' => $memory,
        ],'/log/request');

        return $response;
    }

    /**
     * 向服务器端发送数据
     * @param array $data
     * @param string $url
     * @param int $retry_times
     * @return bool
     */
    private function send(array $data,string $url,int $retry_times = 3)
    {
        if ($retry_times == 0)
        {
            return false;
        }
        $client = new \GuzzleHttp\Client([
            'base_uri' => $this->host,
            'timeout' => $this->http_request_timeout,
            'verify' => false,
        ]);
        $response = json_decode($client->post($url,[
            'json' => $data
        ])->getBody()->getContents(),true);
        if (!(isset($response['code']) && $response['code'] == 1))
        {
            return $this->send($data,$url,$retry_times-1);
        }
        return true;
    }

    function __destruct()
    {
        //推送普通日志
        if (!empty($this->common_log))
        {
            $this->send([
                'project'=>$this->project,
                'log' => $this->common_log
            ],'/log/common');
        }
    }
}

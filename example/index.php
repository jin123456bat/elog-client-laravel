<?php

include '../vendor/autoload.php';

try{
    throw new Exception('测试异常');
}
catch(\Exception $e)
{
    $client = new \Logger\Client('http://logger.ifcar99.com','example');
    $client->exception($e);
}

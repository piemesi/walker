<?php

/**
 * This is the summary for a Http Client.
 *
 * @package Eco\Service\HttpClient
 */
namespace App\HttpClient;

//use Eco\Framework\Container;
//use Eco\Logger\Logs;
//use Guzzle\Http\Message\Response;
//use GuzzleHttp\HandlerStack;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client as GClient;


/**
 * Http Client
 */
class Client extends GClient
{
    /** @var Container $di DI Container */
    private $di;

    /** @var string $lastRequestString Last Request */
    private $lastRequestString;

    /** @var string $lastResponseString Last Response */
    private $lastResponseString;

    /**
     * Constructor
     *
     * @param Container $di
     * @param array $config
     *
     * @return Response
     */
    public function __construct(Container $di, array $config = [])
    {
        $this->di = $di;
        $client = $this;

        $logMiddleware = function (callable $handler) use ($client) {
            $formatter = new \GuzzleHttp\MessageFormatter(\GuzzleHttp\MessageFormatter::DEBUG /* "Reguest:\n{req_body} \n\n Response:\n{res_body}" */);
            return function ($request, array $options) use ($handler, $formatter, $client) {
                return $handler($request, $options)->then(
                    function ($response) use ($request, $formatter, $client) {
                        $message = $formatter->format($request, $response);
                        $message = $client->di->formatter->maskingSecureDataString($message);

                        /** @var Client $client */
                        $client->setLastResponseString(\GuzzleHttp\Psr7\str($response));
                        $client->setLastRequestString(\GuzzleHttp\Psr7\str($request));
                        Logs::message("HTTP request sent: " . $message, Logs::ER_OK, SCRIPT_NAME);
                        /** @var Response $response */
                        $response->getBody()->rewind();

                        return $response;
                    },
                    function ($reason) use ($request, $formatter) {
                        $response = $reason instanceof \Guzzle\Http\Exception\RequestException
                            ? $reason->getResponse()
                            : null;
                        $message = $formatter->format($request, $response, $reason);
                        Logs::message("HTTP request sent with error: " . $message, Logs::ER_ERR, SCRIPT_NAME);

                        return \GuzzleHttp\Promise\rejection_for($reason);
                    }
                );
            };
        };


        if (!isset($config['handler'])) {
            // логируем любые запросы и ответы
            $stack = \GuzzleHttp\HandlerStack::create();
            $stack->setHandler(new \GuzzleHttp\Handler\CurlHandler());
        } else {
            /** @var HandlerStack $stack */
            $stack = $config['handler'];
            unset($config['handler']);
        }
        $stack->push($logMiddleware);


        $innerConfig = ['http_errors' => false , 'handler' => $stack];

        $config = array_merge($innerConfig, $config);

        parent::__construct($config);
    }


    /**
     * Set Last request string
     *
     * @param mixed $lastRequestString
     */
    public function setLastRequestString($lastRequestString)
    {
        $this->lastRequestString = $lastRequestString;
    }

    /**
     * Get Last Request string
     *
     * @return mixed
     */
    public function getLastRequestString()
    {
        return $this->lastRequestString;
    }

    /**
     * Get Last Response string
     *
     * @return string
     */
    public function getLastResponseString()
    {
        return $this->lastResponseString;
    }

    /**
     * Set Last Response string
     *
     * @param string $lastResponseString
     */
    public function setLastResponseString($lastResponseString)
    {
        $this->lastResponseString = $lastResponseString;
    }
}

<?php

namespace Sqits\Gripp;

use GuzzleHttp\Client as GuzzleClient;

class Client
{
    /**
     * @var Gripp user api key.
     */
    protected $apiKey;

    /**
     * @var Guzzle client.
     */
    protected $client;

    /**
     * @var int Api version.
     */
    protected $apiVersion = 3011;

    /**
     * @var int Misc
     */
    protected $id = 1;
    protected $batchMode = false;
    protected $requests = [];
    protected $responseHeaders = [];

    //experimental auto paging
    protected $autoPaging = false;
    protected $autoPaging_in_progress = false;
    protected $autoPaging_max_results = 250; //max results per iteration. Fixed value also enforced on serverside.
    protected $autoPaging_result = [];
    protected $latest = null;

    /**
     * Connection constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (is_null(config('gripp.domain'))) {
            throw new \Exception('Url is required.');
        } else {
            $this->client = new GuzzleClient(['base_uri' => config('gripp.domain')]);
        }

        if (is_null(config('gripp.api_key'))) {
            throw new \Exception('API Key is required.');
        }

        $this->apiKey = config('gripp.api_key');
    }

    /**
     * Get the api version.
     *
     * @return int
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    public function getVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Set the api url.
     *
     * @param $url
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Set batch mode.
     *
     * @param $b
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    public function setBatchMode($b)
    {
        $this->batchMode = $b;
    }

    /**
     * Get the batch mode.
     *
     * @return bool
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    public function getBatchMode()
    {
        return $this->batchMode;
    }

    /**
     * Set auto paging.
     *
     * @param $b
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    public function setAutoPaging($b)
    {
        $this->autoPaging = $b;
    }

    /**
     * Get auto paging.
     *
     * @return bool
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    public function getAutoPaging()
    {
        return $this->autoPaging;
    }

    /**
     * Error handling
     *
     * @param $responses
     * @return mixed
     * @throws \Exception
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    public function handleResponseErrors($responses)
    {
        $messages = [];

        foreach ($responses as $response) {
            if (array_key_exists('error', $response) && !empty($response['error'])) {
                if (array_key_exists('error_code', $response)) {
                    switch ($response['error_code']) {
                        default:
                            $messages[] = $response['error'];
                            break;
                    }
                } else {
                    $messages[] = $response['error'];
                }
            } else {
                unset($this->requests[$response['id']]);
            }
        }

        if (count($messages) > 0) {
            throw new \Exception(implode("\n", $messages));
        }
        return $responses;
    }

    /**
     * Get raw posts.
     *
     * @return array
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    function getRawPost()
    {
        $post = [];

        foreach ($this->requests as $r) {
            $post[] = [
                'apiconnectorversion' => $this->apiVersion,
                'method' => $r['class'].'.'.$r['method'],
                'params' => $r['params'],
                'id' => $r['id'],
            ];
        }

        return $post;
    }

    /**
     * Call.
     *
     * @param $fullMethod
     * @param $params
     * @return array|mixed|null
     * @throws \Exception
     * @author Milan Jansen <m.jansen@sqits.nl>
     * @since 1.0.0
     */
    public function __call($fullMethod, $params)
    {
        list($class, $method) = explode("_", $fullMethod);
        $id = $this->id++;

        //default filter array empty
        if (!array_key_exists(0, $params)) {
            $params[0] = [];
        }

        //default options array empty
        if (!array_key_exists(1, $params)) {
            $params[1] = [];
        }

        if ($this->autoPaging && strtolower($method) == 'get') {
            if (!array_key_exists('paging', $params[1])) {
                $params[1]['paging'] = [
                    "firstresult" => 0,
                    "maxresults" => $this->autoPaging_max_results,
                ];
            }
        }

        $this->requests[$id] = [
            'class' => $class,
            'method' => $method,
            'params' => $params,
            'id' => $id,
        ];

        if (!$this->getBatchMode() || count($this->requests) == 1) {
            if ($this->autoPaging && strtolower($method) == 'get') {

                if (!$this->autoPaging_in_progress) {
                    $this->autoPaging_in_progress = true;
                    $this->autoPaging_result = [
                        [
                            'id' => $id,
                            'autopaging_result' => true,
                            'autopagina_number_of_calls' => 1,
                            'result' => [
                                'rows' => [],
                                'count' => 0,
                                'start' => 0,
                                'limit' => 0,
                                'next_start' => 0,
                                'more_items_in_collection' => false,
                            ],
                            'error' => null,
                        ],
                    ];
                }

                $tempRes = $this->run();
                $tempRes = $tempRes[0]['result'];
                $this->autoPaging_result[0]['result']['rows'] = array_merge($this->autoPaging_result[0]['result']['rows'],
                    $tempRes['rows']);
                $this->autoPaging_result[0]['result']['count'] = $tempRes['count'];
                $this->autoPaging_result[0]['result']['start'] = 0;
                $this->autoPaging_result[0]['result']['limit'] = $tempRes['count'];
                $this->autoPaging_result[0]['result']['next_start'] = null;
                $this->autoPaging_result[0]['result']['more_items_in_collection'] = false;

                if ($tempRes['more_items_in_collection']) {
                    $params[1]['paging'] = [
                        "firstresult" => $tempRes['next_start'],
                        "maxresults" => $this->autoPaging_max_results,
                    ];
                    $this->autoPaging_result[0]['autopagina_number_of_calls']++;
                    return $this->__call($fullMethod, $params);
                } else {
                    $this->autoPaging_in_progress = false;
                    return $this->autoPaging_result;
                }
            } else {
                if (!$this->getBatchMode()) {
                    return $this->run();
                }
            }
        }
    }

    /**
     * Run the api.
     *
     * @return array|mixed|null
     * @throws \Exception
     * @author Milan Jansen <m.jansen@sqits.nl>
     * @since 1.0.0
     */
    function run()
    {
        $post = $this->getRawPost();
        if (count($post) > 0) {
            $post_string = json_encode($post);
            $result = $this->send($post_string);
            $result_decoded = json_decode($result, true);
            $this->latest = $this->handleResponseErrors($result_decoded);
            return $this->latest;
        } else {
            if ($this->batchMode && $this->autoPaging) {
                if ($this->autoPaging_result) {
                    return $this->autoPaging_result;
                } else {
                    return $this->latest;
                }
            } else {
                if ($this->batchMode) {
                    return $this->latest;
                } else {
                    return null;
                }
            }
        }
    }

    /**
     * Send the api call to gripp.
     *
     * @param $postString
     * @return string
     * @throws \Exception
     * @since 1.0.0
     * @author Milan Jansen <m.jansen@sqits.nl>
     */
    private function send($postString)
    {
        $response = $this->client->post('/public/api3.php', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
            ],
            'body' => $postString,
        ]);

        switch ($response->getStatusCode()) {
            case 503:
                if (array_key_exists('retry-after', $this->responseHeaders)) {
                    usleep($this->responseHeaders['retry-after'][0] * 1000000);
                    return $this->send($postString);
                } else {
                    throw new \Exception('Received HTTP status code 503 without Retry-After header. Cannot automatically resend the request.');
                }
                break;
            case 429:
                throw new \Exception('Received HTTP status code: 429. Maximum number of request for this hour is reached. Please upgrade your API Request Packs.');
                break;
            case 404:
                throw new \Exception('The given function or url doesn\'t exists');
                break;
            case 200:
                return $response->getBody()->getContents();
                break;
            default:
                throw new \Exception('Received HTTP status code: '.$response->getStatusCode());
                break;
        }
    }
}

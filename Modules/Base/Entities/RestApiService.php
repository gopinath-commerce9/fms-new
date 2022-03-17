<?php


namespace Modules\Base\Entities;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class RestApiService
{

    private $mainConfigKey = 'fms';
    private $apiConfigKey = 'api';
    private $apiEnvironment = 'live';
    private $apiEnvConfigs = [];
    private $apiChannels = [];
    private $apiDefaultChannel = '';
    private $apiChannel = '';
    private $apiDefaultCountry = '';
    private $apiCountry = '';
    private $apiUserTokenSessionKey = 'api_user_token';

    /**
     * RestApiService constructor.
     */
    public function __construct() {
        $this->setApiEnvironment();
    }

    /**
     * Get the URI fragment for the RESTFul API Call.
     *
     * @return mixed
     */
    private function getRestApiUrlFragment() {
        $channelSettings = $this->getApiChannelConfigs();
        return $channelSettings['apiUri'];
    }

    /**
     * Get the URI fragment for the Authentication of RESTFul API Calls.
     *
     * @return mixed
     */
    private function getRestApiAuthUrlFragment() {
        $channelSettings = $this->getApiChannelConfigs();
        return $channelSettings['authUri'];
    }

    /**
     * Get the RESTFul API Authentication Access Token.
     *
     * @return mixed
     */
    private function getApiAccessToken() {
        $channelSettings = $this->getApiChannelConfigs();
        return (
            array_key_exists('accessToken', $channelSettings)
            && !is_null($channelSettings['accessToken'])
            && (trim($channelSettings['accessToken']) != '')
        ) ? trim($channelSettings['accessToken']) : null;
    }

    /**
     * Get the RESTFul API Authentication Credentials.
     *
     * @return array
     */
    private function getApiCredentials() {
        $channelSettings = $this->getApiChannelConfigs();
        return [
            'username' => $channelSettings['authKey'],
            'password' => $channelSettings['authSecret']
        ];
    }

    /**
     * Get the Timeout Configs of the RESTFul API  Call.
     *
     * @return array
     */
    private function getApiTimeoutConfigs() {
        $channelSettings = $this->getApiChannelConfigs();
        return [
            'timeout' => $channelSettings['timeoutSeconds'],
            'retryLoop' => $channelSettings['retryLoop'],
            'retryLoopInterval' => $channelSettings['retryLoopInterval'],
        ];
    }

    /**
     * Get the Session Key for the RESTFul API Authentication token.
     *
     * @return string
     */
    private function getApiBearerTokenKey() {
        return $this->apiUserTokenSessionKey . '_' . $this->apiChannel;
    }

    /**
     * Get the Authentication Bearer Token for the API Calls.
     *
     * @param bool $force
     * @return string|null
     */
    private function getApiUserBearerToken($force = false) {

        $sessionTokenKey = $this->getApiBearerTokenKey();
        if(session()->has($sessionTokenKey) && ($force === false)) {
            $cleanToken = trim(session()->get($sessionTokenKey));
            if (!is_null($cleanToken) && ($cleanToken != '')) {
                return $cleanToken;
            }
        }

        $accessToken = $this->getApiAccessToken();
        if (!is_null($accessToken)) {
            session()->put($sessionTokenKey, $accessToken);
            return $accessToken;
        }

        $authUrl = $this->getRestApiAuthUrl();
        $apiCred = $this->getApiCredentials();
        $authCredentials = [
            'username' => $apiCred['username'],
            'password' => $apiCred['password']
        ];

        $apiResult = $this->processPostApi($authUrl, $authCredentials, [], false);

        if ($apiResult['status']) {
            $responseData = $apiResult['response'];
            session()->put($sessionTokenKey, $responseData);
            return $responseData;
        }

        return null;

    }

    /**
     * Procees the RESTFul API Call.
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param bool $authenticate
     * @param bool $forceAuthenticate
     *
     * @return array
     */
    private function processRestApiCall($method = 'GET', $url = '', $params = [], $headers = [], $authenticate = true, $forceAuthenticate = false) {

        $httpMethods = ['GET', 'POST', 'PUT', 'DELETE'];
        $cleanMethod = strtoupper(str_replace(' ', '_', trim($method)));
        if(!in_array($cleanMethod, $httpMethods)) {
            return [
                'status' => false,
                'message' => 'Invalid HTTP API Method!',
                'response' => null
            ];
        }


        if (is_null($url) || (trim($url) == '')) {
            return [
                'status' => false,
                'message' => 'Invalid API URL!',
                'response' => null
            ];
        }

        if (!is_null($params) && !is_array($params)) {
            return [
                'status' => false,
                'message' => 'Invalid Params input!',
                'response' => null
            ];
        }

        if (!is_null($headers) && !is_array($headers)) {
            return [
                'status' => false,
                'message' => 'Invalid Headers input!',
                'response' => null
            ];
        }

        $apiResponse = null;

        try {

            $timeoutSettings = $this->getApiTimeoutConfigs();
            $pendingRequest = Http::acceptJson()
                ->retry($timeoutSettings['retryLoop'], $timeoutSettings['retryLoopInterval']);

            if(!is_null($headers) && is_array($headers) && (count($headers) > 0)) {
                $pendingRequest->withHeaders($headers);
            }

            if ($authenticate) {
                $authToken = $this->getApiUserBearerToken($forceAuthenticate);
                if(!$authToken) {
                    return [
                        'status' => false,
                        'message' => 'The API could not authenticate!',
                        'response' => null
                    ];
                }
                $pendingRequest->withToken($authToken);
            }

            switch ($cleanMethod) {
                case 'GET':
                    $apiResponse = $pendingRequest->get($url, $params);
                    break;
                case  'POST':
                    $apiResponse = $pendingRequest->post($url, $params);
                    break;
                case 'PUT':
                    $apiResponse = $pendingRequest->put($url, $params);
                    break;
                case 'DELETE':
                    $apiResponse = $pendingRequest->delete($url, $params);
                    break;
            }

            if (($apiResponse->status() === 401) && $authenticate) {
                $authToken = $this->getApiUserBearerToken(true);
                if (!$authToken) {
                    return [
                        'status' => false,
                        'message' => 'The API could not authenticate!',
                        'response' => null
                    ];
                }
                $this->processRestApiCall($method, $url, $params, $headers, $authenticate);
            }

            if ($apiResponse->failed()) {
                return [
                    'status' => false,
                    'message' => 'The API call failed!',
                    'response' => null
                ];
            }

            return [
                'status' => true,
                'message' => '',
                'response' => $apiResponse->json(),
            ];

        } catch(\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'response' => null,
            ];
        }

    }

    /**
     * Get the current API Environment.
     *
     * @return string
     */
    public function getApiEnvironment() {
        return $this->apiEnvironment;
    }

    /**
     * Set the RESTFul API Environment.
     *
     * @param string $env
     */
    public function setApiEnvironment($env = '') {
        $mainConfigs = config($this->mainConfigKey);
        $apiConfigs = $mainConfigs[$this->apiConfigKey];
        $availableEnvs = array_keys($apiConfigs);
        $defaultEnv = $mainConfigs['defaults']['apiEnv'];
        $targetEnv = strtolower(str_replace(' ', '_', trim($env)));
        $envClean = (in_array($targetEnv, $availableEnvs)) ? $targetEnv : $defaultEnv;
        $this->apiEnvironment = $envClean;
        $this->apiEnvConfigs = $apiConfigs[$this->apiEnvironment];
        $this->apiChannels = $this->apiEnvConfigs['channels'];
        $this->apiDefaultChannel = $this->apiEnvConfigs['defaults']['channel'];
        $this->apiChannel = $this->apiDefaultChannel;
        $this->apiDefaultCountry = $this->apiEnvConfigs['defaults']['country_code'];
        $this->apiCountry = $this->apiDefaultCountry;
    }

    /**
     * Get all the Available Channels of RESTFul API Calls.
     *
     * @return array
     */
    public function getAllAvailableApiChannels() {
        $channels = $this->apiChannels;
        if (is_null($channels) || !is_array($channels) || (count($channels) == 0)) {
            return [];
        }
        $channelArray = [];
        foreach ($channels as $channelKey => $channelEl) {
            $channelArray[$channelEl['id']] = [
                'id' => $channelEl['id'],
                'name' => $channelEl['name'],
            ];
        }
        return $channelArray;
    }

    /**
     * Get the default Channel of the RESTFul API.
     *
     * @return string
     */
    public function getDefaultApiChannel() {
        return $this->apiDefaultChannel;
    }

    /**
     * Check whether the given Channel is a Valid RESTFul API Channel.
     *
     * @param string $channel
     *
     * @return bool
     */
    public function isValidApiChannel($channel = '') {
        return (
            !is_null($channel)
            && (trim(strtolower($channel)) != '')
            && array_key_exists(trim(strtolower($channel)), $this->apiChannels)
        ) ? true : false;
    }

    /**
     * Set the RESTFul API Channel.
     *
     * @param string $channel
     */
    public function setApiChannel($channel = '') {
        $this->apiChannel = ($this->isValidApiChannel($channel))
            ? trim(strtolower($channel))
            : $this->apiDefaultChannel;
    }

    /**
     * Get the current RESTFul API Channel.
     *
     * @return string
     */
    public function getCurrentApiChannel() {
        return $this->apiChannel;
    }

    /**
     * Get the default 2-Letter Country Code of the RESTFul API.
     *
     * @return string
     */
    public function getApiDefaultCountry() {
        return $this->apiDefaultCountry;
    }

    /**
     * Get the current 2-Letter Country Code of the RESTFul API.
     *
     * @return string
     */
    public function getCurrentCountry() {
        return $this->apiCountry;
    }

    /**
     * Get the Configs for the RESTFul API Channel.
     *
     * @return mixed
     */
    public function getApiChannelConfigs() {
        return $this->apiChannels[$this->apiChannel];
    }

    /**
     * Get the Base URL of the API.
     *
     * @return string
     */
    public function getBaseUrl() {
        $channelSettings = $this->getApiChannelConfigs();
        return $channelSettings['url'];
    }

    /**
     * Get the full API URL.
     *
     * @return string
     */
    public function getRestApiUrl() {
        $targetUrl = $this->getBaseUrl();
        $targetUrl .= $this->getRestApiUrlFragment();
        return $targetUrl;
    }

    /**
     * Get the full URL for the RESTFul API Authentication.
     *
     * @return string
     */
    public function getRestApiAuthUrl() {
        $targetUrl = $this->getRestApiUrl();
        $targetUrl .= $this->getRestApiAuthUrlFragment();
        return $targetUrl;
    }

    /**
     * Get the TimeZone of the RESTFul API Channel.
     *
     * @return mixed
     */
    public function getApiTimezone() {
        $channelSettings = $this->getApiChannelConfigs();
        return $channelSettings['timezone'];
    }

    /**
     * Execute the GET method RESTFul API Call.
     *
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param bool $authenticate
     * @param bool $forceAuthenticate
     *
     * @return array
     */
    public function processGetApi($url = '', $params = [], $headers = [], $authenticate = true, $forceAuthenticate = false) {
        return $this->processRestApiCall('GET', $url, $params, $headers, $authenticate, $forceAuthenticate);
    }

    /**
     * Execute the POST method RESTFul API Call.
     *
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param bool $authenticate
     * @param bool $forceAuthenticate
     *
     * @return array
     */
    public function processPostApi($url = '', $params = [], $headers = [], $authenticate = true, $forceAuthenticate = false) {
        return $this->processRestApiCall('POST', $url, $params, $headers, $authenticate, $forceAuthenticate);
    }

    /**
     * Execute the PUT method RESTFul API Call.
     *
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param bool $authenticate
     * @param bool $forceAuthenticate
     *
     * @return array
     */
    public function processPutApi($url = '', $params = [], $headers = [], $authenticate = true, $forceAuthenticate = false) {
        return $this->processRestApiCall('PUT', $url, $params, $headers, $authenticate, $forceAuthenticate);
    }

    /**
     * Execute the DELETE method RESTFul API Call.
     *
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param bool $authenticate
     * @param bool $forceAuthenticate
     *
     * @return array
     */
    public function processDeleteApi($url = '', $params = [], $headers = [], $authenticate = true, $forceAuthenticate = false) {
        return $this->processRestApiCall('DELETE', $url, $params, $headers, $authenticate, $forceAuthenticate);
    }

}

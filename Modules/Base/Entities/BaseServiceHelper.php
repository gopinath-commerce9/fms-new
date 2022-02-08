<?php


namespace Modules\Base\Entities;

use Illuminate\Support\Facades\Storage;
use Modules\Base\Entities\RestApiService;

class BaseServiceHelper
{

    private $restApiService = null;

    public function __construct($channel = '')
    {
        $this->restApiService = new RestApiService();
        $this->setApiChannel($channel);
    }

    /**
     * Switch to the given RESTFul API Channel
     *
     * @param string $channel
     */
    public function setApiChannel($channel = '') {
        if ($this->restApiService->isValidApiChannel($channel)) {
            $this->restApiService->setApiChannel($channel);
        }
    }

    /**
     * Fetch the List of Regions Available.
     *
     * @param string $env
     * @param string $channel
     *
     * @return array
     */
    public function getRegionList($env = '', $channel = '') {

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'region';
        $apiResult = $apiService->processGetApi($uri, [], [], true, true);

        if (!$apiResult['status']) {
            return [];
        }

        if (!is_array($apiResult['response']) || (count($apiResult['response']) == 0)) {
            return [];
        }

        return $apiResult['response'];

    }

    /**
     * Fetch the List of Cities Available.
     *
     * @param string $env
     * @param string $channel
     *
     * @return array
     */
    public function getCityList($env = '', $channel = '') {

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'city';
        $apiResult = $apiService->processGetApi($uri, [], [], true, true);

        if (!$apiResult['status']) {
            return [];
        }

        if (!is_array($apiResult['response']) || (count($apiResult['response']) == 0)) {
            return [];
        }

        return $apiResult['response'];

    }

    /**
     * Fetch the List of Cities Available based on Region Id.
     *
     * @param string $env
     * @param string $channel
     * @param null $regionId
     *
     * @return array
     */
    public function getCityListByRegionId($env = '', $channel = '', $regionId = null) {

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $uri = $apiService->getRestApiUrl() . 'citybyregion/' . $regionId;
        $apiResult = $apiService->processGetApi($uri, [], [], true, true);

        if (!$apiResult['status']) {
            return [];
        }

        if (!is_array($apiResult['response']) || (count($apiResult['response']) == 0)) {
            return [];
        }

        return $apiResult['response'];

    }

    public function getFileUrl($path = '') {
        return (!is_null($path) && (trim($path) != '') && Storage::exists(trim($path)))
            ? Storage::url(trim($path))
            : '';
    }

    public function deleteFile($path = '') {
        return (!is_null($path) && (trim($path) != '') && Storage::exists(trim($path)))
            ? Storage::delete(trim($path))
            : true;
    }

}

<?php


namespace Modules\Base\Entities;

use Illuminate\Support\Facades\Storage;
use Modules\Base\Entities\RestApiService;
use Modules\Sales\Entities\SalesRegion;

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
     * @param bool $forceFetch
     *
     * @return array
     */
    public function getRegionList($env = '', $channel = '', $forceFetch = false) {

        $regionData = [];

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $envClean = $apiService->getApiEnvironment();
        $channelClean = $apiService->getCurrentApiChannel();
        $fetchClean = (!is_null($forceFetch) && is_bool($forceFetch)) ? $forceFetch : false;

        if ($fetchClean === false) {

            $regionQuery = SalesRegion::select('*');
            $regionQuery->where('env', $envClean);
            if (!is_null($channel) && (trim($channel) != '')) {
                $regionQuery->where('channel', $channelClean);
            }

            $regionResult = $regionQuery->get();
            if ($regionResult) {
                $regionData = $regionResult->toArray();
            }

            if (count($regionData) > 0) {
                return $regionData;
            }

        }

        $uri = $apiService->getRestApiUrl() . 'region';
        $apiResult = $apiService->processGetApi($uri, [], [], true, true);

        if (!$apiResult['status']) {
            return [];
        }

        if (!is_array($apiResult['response']) || (count($apiResult['response']) == 0)) {
            return [];
        }

        foreach ($apiResult['response'] as $regionEl) {
            SalesRegion::updateOrCreate([
                'env' => $envClean,
                'channel' => $channelClean,
                'region_id' => $regionEl['region_id']
            ], [
                'entity_id' => $regionEl['entity_id'],
                'country_id' => $regionEl['country_id'],
                'name' => $regionEl['name'],
            ]);
        }

        $regionQuery = SalesRegion::select('*');
        $regionQuery->where('env', $envClean);
        if (!is_null($channel) && (trim($channel) != '')) {
            $regionQuery->where('channel', $channelClean);
        }


        $regionResult = $regionQuery->get();
        if ($regionResult) {
            $regionData = $regionResult->toArray();
        }

        if (count($regionData) > 0) {
            return $regionData;
        }

        return [];

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

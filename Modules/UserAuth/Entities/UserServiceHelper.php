<?php


namespace Modules\UserAuth\Entities;

use Modules\Base\Entities\RestApiService;
use Modules\Base\Entities\BaseServiceHelper;

class UserServiceHelper
{

    private $restApiService = null;
    private $baseService = null;

    public function __construct($channel = '')
    {
        $this->restApiService = new RestApiService();
        $this->baseService = new BaseServiceHelper();
        $this->setApiChannel($channel);
    }

    public function getApiEnvironment() {
        return $this->restApiService->getApiEnvironment();
    }

    /**
     * Get the current RESTFul API Channel.
     * @return string
     */
    public function getApiChannel() {
        return $this->restApiService->getCurrentApiChannel();
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
     * Get the list of all the available API Channels.
     *
     * @return array
     */
    public function getAllAvailableChannels() {
        return $this->restApiService->getAllAvailableApiChannels();
    }

    /**
     * Get the given DateTime string in the given DateTime format
     *
     * @param string $dateTimeString
     * @param string $format
     * @param string $env
     * @param string $channel
     *
     * @return string
     */
    public function getFormattedTime($dateTimeString = '', $format = '', $env = '', $channel = '') {

        if (is_null($dateTimeString) || (trim($dateTimeString) == '')) {
            return '';
        }

        if (is_null($format) || (trim($format) == '')) {
            $format = \DateTime::ISO8601;
        }

        $apiService = $this->restApiService;
        if (!is_null($env) && !is_null($channel) && (trim($env) != '') && (trim($channel) != '')) {
            $apiService = new RestApiService();
            $apiService->setApiEnvironment($env);
            $apiService->setApiChannel($channel);
        }

        $appTimeZone = config('app.timezone');
        $channelTimeZone = $apiService->getApiTimezone();
        $zoneList = timezone_identifiers_list();
        $cleanZone = (in_array(trim($channelTimeZone), $zoneList)) ? trim($channelTimeZone) : $appTimeZone;

        try {
            $dtObj = new \DateTime($dateTimeString, new \DateTimeZone($appTimeZone));
            $dtObj->setTimezone(new \DateTimeZone($cleanZone));
            return $dtObj->format($format);
        } catch (\Exception $e) {
            return '';
        }

    }

    public function getFileUrl($path = '') {
        return $this->baseService->getFileUrl($path);
    }

    public function getUserImageUrl($path = '') {
        return $this->baseService->getFileUrl('media/images/users/' . $path);
    }

    public function deleteFile($path = '') {
        return $this->baseService->deleteFile($path);
    }

    public function deleteUserImage($path = '') {
        return $this->baseService->deleteFile('media/images/users/' . $path);
    }

}

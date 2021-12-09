<?php


namespace Modules\API\Entities;

use Modules\Base\Entities\RestApiService;
use Modules\Base\Entities\BaseServiceHelper;

class ApiServiceHelper
{

    const HTTP_STATUS_CODE_CONTINUE = 100;
    const HTTP_STATUS_CODE_SWITCHING_PROTOCOLS = 101;
    const HTTP_STATUS_CODE_PROCESSING = 102;            // RFC2518
    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_CREATED = 201;
    const HTTP_STATUS_CODE_ACCEPTED = 202;
    const HTTP_STATUS_CODE_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_STATUS_CODE_NO_CONTENT = 204;
    const HTTP_STATUS_CODE_RESET_CONTENT = 205;
    const HTTP_STATUS_CODE_PARTIAL_CONTENT = 206;
    const HTTP_STATUS_CODE_MULTI_STATUS = 207;          // RFC4918
    const HTTP_STATUS_CODE_ALREADY_REPORTED = 208;      // RFC5842
    const HTTP_STATUS_CODE_IM_USED = 226;               // RFC3229
    const HTTP_STATUS_CODE_MULTIPLE_CHOICES = 300;
    const HTTP_STATUS_CODE_MOVED_PERMANENTLY = 301;
    const HTTP_STATUS_CODE_FOUND = 302;
    const HTTP_STATUS_CODE_SEE_OTHER = 303;
    const HTTP_STATUS_CODE_NOT_MODIFIED = 304;
    const HTTP_STATUS_CODE_USE_PROXY = 305;
    const HTTP_STATUS_CODE_RESERVED = 306;
    const HTTP_STATUS_CODE_TEMPORARY_REDIRECT = 307;
    const HTTP_STATUS_CODE_PERMANENTLY_REDIRECT = 308;  // RFC7238
    const HTTP_STATUS_CODE_BAD_REQUEST = 400;
    const HTTP_STATUS_CODE_UNAUTHORIZED = 401;
    const HTTP_STATUS_CODE_PAYMENT_REQUIRED = 402;
    const HTTP_STATUS_CODE_FORBIDDEN = 403;
    const HTTP_STATUS_CODE_NOT_FOUND = 404;
    const HTTP_STATUS_CODE_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_CODE_NOT_ACCEPTABLE = 406;
    const HTTP_STATUS_CODE_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_STATUS_CODE_REQUEST_TIMEOUT = 408;
    const HTTP_STATUS_CODE_CONFLICT = 409;
    const HTTP_STATUS_CODE_GONE = 410;
    const HTTP_STATUS_CODE_LENGTH_REQUIRED = 411;
    const HTTP_STATUS_CODE_PRECONDITION_FAILED = 412;
    const HTTP_STATUS_CODE_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_STATUS_CODE_REQUEST_URI_TOO_LONG = 414;
    const HTTP_STATUS_CODE_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_STATUS_CODE_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_STATUS_CODE_EXPECTATION_FAILED = 417;
    const HTTP_STATUS_CODE_I_AM_A_TEAPOT = 418;                                               // RFC2324
    const HTTP_STATUS_CODE_MISDIRECTED_REQUEST = 421;                                         // RFC7540
    const HTTP_STATUS_CODE_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
    const HTTP_STATUS_CODE_LOCKED = 423;                                                      // RFC4918
    const HTTP_STATUS_CODE_FAILED_DEPENDENCY = 424;                                           // RFC4918
    const HTTP_STATUS_CODE_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
    const HTTP_STATUS_CODE_UPGRADE_REQUIRED = 426;                                            // RFC2817
    const HTTP_STATUS_CODE_PRECONDITION_REQUIRED = 428;                                       // RFC6585
    const HTTP_STATUS_CODE_TOO_MANY_REQUESTS = 429;                                           // RFC6585
    const HTTP_STATUS_CODE_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585
    const HTTP_STATUS_CODE_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const HTTP_STATUS_CODE_INTERNAL_SERVER_ERROR = 500;
    const HTTP_STATUS_CODE_NOT_IMPLEMENTED = 501;
    const HTTP_STATUS_CODE_BAD_GATEWAY = 502;
    const HTTP_STATUS_CODE_SERVICE_UNAVAILABLE = 503;
    const HTTP_STATUS_CODE_GATEWAY_TIMEOUT = 504;
    const HTTP_STATUS_CODE_VERSION_NOT_SUPPORTED = 505;
    const HTTP_STATUS_CODE_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
    const HTTP_STATUS_CODE_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
    const HTTP_STATUS_CODE_LOOP_DETECTED = 508;                                               // RFC5842
    const HTTP_STATUS_CODE_NOT_EXTENDED = 510;                                                // RFC2774
    const HTTP_STATUS_CODE_NETWORK_AUTHENTICATION_REQUIRED = 511;                             // RFC6585

    private $restApiService = null;
    private $baseService = null;

    private $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',                                     // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

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
     *
     * @return string
     */
    public function getFormattedTime($dateTimeString = '', $format = '') {

        if (is_null($dateTimeString) || (trim($dateTimeString) == '')) {
            return '';
        }

        if (is_null($format) || (trim($format) == '')) {
            $format = \DateTime::ISO8601;
        }

        $appTimeZone = config('app.timezone');
        $channelTimeZone = $this->restApiService->getApiTimezone();
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

    /**
     * Get the Status Text for the Given HTTP Status Code
     *
     * @param string $httpStatusCode
     *
     * @return mixed|string
     */
    public function getStatusCodeText($httpStatusCode = '') {
        return (
            is_null($httpStatusCode)
            || !is_numeric($httpStatusCode)
            || !array_key_exists($httpStatusCode, $this->statusTexts)
        ) ? '' : $this->statusTexts[$httpStatusCode];
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

<?php


namespace Modules\Base\Entities;

use Illuminate\Support\Facades\Storage;

class BaseServiceHelper
{

    public function __construct()
    {

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

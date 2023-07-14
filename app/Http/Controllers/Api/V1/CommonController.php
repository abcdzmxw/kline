<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Country;
use App\Models\InsideTradePair;
use App\Models\Translate;
use App\Models\User;
use App\Services\HuobiService\HuobiapiService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class CommonController extends ApiController
{
    //

    public function getTranslate2(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'lang' => '',
        ])) return $vr;

        $lang = $request->input('lang','zh-CN');

        $data = Translate::getTranslate($lang);
        return $this->successWithData($data);
    }

    public function getTranslate(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'lang' => '',
        ])) return $vr;

        $lang = $request->input('lang','zh-CN');

        $data = Translate::getTranslate($lang);
        return $this->successWithData($data);
    }

    public function uploadImage(Request $request)
    {
        if ($vr = $this->verifyField($request->all(),[
            'image' => 'required|image',
        ])) return $vr;

        $disk_type = 'public';

        $disk = \Illuminate\Support\Facades\Storage::disk($disk_type);
        $re = $disk->put('upload',$request->image);
        $data = ['url' => getFullPath($re)  ,'path' => $re];
        return $this->successWithData($data,'上传成功');
    }

    public function getCountryList()
    {
        $data = Country::getForeverCachedCountry();
        return $this->successWithData($data);
    }

}

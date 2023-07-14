<?php

namespace App\Http\Controllers\Appapi\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends ApiController
{
    //

    //首页Banner
    public function index(Request $request)
    {
        if ($res = $this->verifyField($request->all(),[
            'location' => 'integer',
            'limit' => 'integer',
        ])) return $res;

        $limit = $request->input('limit',5);
        $location = $request->input('location',1);

        $banner = Banner::query()->where(['location_type'=>$location])->limit($limit)->latest()->get();

        return $this->successWithData($banner);
    }

}

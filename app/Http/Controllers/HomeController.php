<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{

    public function kline(){
        return view('kline');
    }

    public function data(Request $request){
        $date = '2020-07-19 00:00:00';
        $temp = DB::table('data_stai')
            ->selectRaw('datetime,Open,High,Low,Close')
            ->orderBy('datetime')
            ->where('datetime','>', $date)
            ->where('is_1min', 1)
            //->limit(14400)
            ->get();
        $data = [];
        foreach ($temp as $item){
            $index = [];
            $item->datetime = strtotime($item->datetime)*1000;
            foreach ($item as $kk){
                $index[] = $kk;
            }
            $data[] = $index;
        }
        return response()->jsonp($request->callback,$data);
    }
}

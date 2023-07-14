<?php

namespace App\Admin\Forms;

use App\Models\Admin\AdminSetting;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Setting extends Form
{
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return Response
     */
    public function handle(array $input)
    {
        // dump($input);

        DB::beginTransaction();
        try{

            $modules = AdminSetting::$modules;
            foreach ($modules as $k=>$v){
                if(isset($input[$k]) && !blank($input[$k])){
                    foreach ($input[$k] as $key => $value){
                        AdminSetting::query()->where(['module'=>$k,'key'=>$key])->update(['value'=>$value]);
                    }
                }

            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        // return $this->error('Your error message.');

        return $this->success('Processed successfully.');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $modules = AdminSetting::$modules;
        $index = 1;
        foreach ($modules as $k=>$v){
            $func = function () use ($k) {
                $settings = AdminSetting::query()->where('module',$k)->get();
                foreach ($settings as $setting){
                    // 设置默认值防止字段为NULL时报错
                    $setting['value'] = blank($setting['value']) ? '' : $setting['value'];
                    switch ($setting['type']){
                        case 'switch' :
                            $this->switch($setting['module'] . '.' . $setting['key'],$setting['title'])->default($setting['value'])->help($setting['tips']);
                            break;
                        case 'image' :
                            $this->image($setting['module'] . '.' . $setting['key'],$setting['title'])->default([$setting['value']])->uniqueName()->autoUpload()->disableRemove()->help($setting['tips']);
                            break;
                        case 'multipleImage' :
                            $this->multipleImage($setting['module'] . '.' . $setting['key'],$setting['title'])->default(json_decode($setting['value'],true))->uniqueName()->autoUpload()->disableRemove()->help($setting['tips']);
                            break;
                        case 'radio' :
                            $this->radio($setting['module'] . '.' . $setting['key'],$setting['title'])->default($setting['value'])->help($setting['tips']);
                            break;
                        default:
                            $this->text($setting['module'] . '.' . $setting['key'],$setting['title'])->default($setting['value'])->help($setting['tips']);
                            break;
                    }
                }
            };
            // 第一个参数是选项卡标题，第二个参数是内容，第三个参数是是否选中
            if($index == 1){
                $this->tab($v,$func,true);
            }else{
                $this->tab($v,$func);
            }
            $index++;
        }
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [];
    }
}

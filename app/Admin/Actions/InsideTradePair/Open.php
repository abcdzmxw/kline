<?php

namespace App\Admin\Actions\InsideTradePair;

use App\Models\InsideTradePair;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Open extends Action
{
    /**
     * @return string
     */
	protected $title = '交易开启';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        DB::beginTransaction();
        try{

            $res = InsideTradePair::query()->where('status',1)->update(['trade_status'=>1]);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->response()->success('Processed successfully.')->refresh();
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['确定全部' . $this->title . '?'];
        // return ['Confirm?', 'contents'];
    }

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }

    protected function html()
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()}><button class="btn btn-primary btn-mini">{$this->title()}</button></a>
HTML;
    }

}

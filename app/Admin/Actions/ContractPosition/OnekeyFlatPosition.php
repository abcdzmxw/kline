<?php

namespace App\Admin\Actions\ContractPosition;

use App\Jobs\HandleFlatPosition;
use App\Models\ContractEntrust;
use App\Models\ContractPosition;
use App\Models\User;
use App\Services\ContractService;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnekeyFlatPosition extends Action
{
    /**
     * @return string
     */
	protected $title = '一键平仓';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $items = ContractPosition::query()->where('hold_position','>',0)->get()->groupBy('user_id');
//        dd($items->toArray());

        if(!blank($items)){
            DB::beginTransaction();
            try{

                foreach ($items as $user_id => $positions){
                    $user = User::query()->find($user_id);
                    if(blank($user)) continue;
                    // 撤销未完成的委托
                    $entrusts = ContractEntrust::query()
                        ->where('user_id',$user['user_id'])
                        ->whereIn('status',[ContractEntrust::status_wait,ContractEntrust::status_trading])
                        ->get();
                    if(!blank($entrusts)){
                        foreach ($entrusts as $entrust){
                            $params2 = ['entrust_id'=>$entrust['id'],'symbol'=>$entrust['symbol']];
                            (new ContractService())->cancelEntrust($user,$params2);
                        }
                    }

                    // 平仓
                    HandleFlatPosition::dispatch($positions)->onQueue('HandleFlatPosition');
                }

                DB::commit();

                return $this->response()->success('Processed successfully.')->refresh();
            }catch (\Exception $e){
                DB::rollBack();
                return $this->response()->error($e->getMessage());
            }
        }
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['确定执行' . $this->title . '?','该操作会撤销所有用户的未完成委托，然后全部平仓'];
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
<a {$this->formatHtmlAttributes()}><button class="btn btn-danger btn-mini">{$this->title()}</button></a>
HTML;
    }
}

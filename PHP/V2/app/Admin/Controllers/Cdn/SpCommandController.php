<?php

namespace App\Admin\Controllers\Cdn;

use App\Models\Cdn\SpCommand;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class SpCommandController extends Controller
{
    use ModelForm;

    /**
     * 管控指令列表
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('管控指令列表');
            $content->description('');

            $content->body($this->grid());
        });
    }


    /**
     * 创建管控指令Grid
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(SpCommand::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->sn('流水号');

            $grid->commandType('指令类型')->display(function($value){
                if( $value == 0 ){
                    return '监管指令';
                }elseif( $value == 1 ){
                    return '恢复指令';
                }else{
                    return '未知';
                }
            });

            $grid->type('管理指令 类型')->display(function($value){
                if( $value == 1 ){
                    return '常规指令';
                }elseif( $value == 2 ){
                    return '应急指令';
                }else{
                    return '未知';
                }
            });

            $grid->objectType('处置对象类型')->display(function($value){
                if( $value == 1 ){
                    return '域名';
                }elseif( $value == 2 ){
                    return 'URL';
                }elseif( $value == 999 ){
                    return '其他';
                }else{
                    return '未知';
                }
            });
            $grid->object('处置对象');

            $grid->rule('处置要求')->display(function($value){
                if( $value == 1 ){
                    return '拒绝访问';
                }elseif( $value == 2 ){
                    return '停止加速';
                }elseif( $value == 3 ){
                    return '清除缓存';
                }elseif( $value == 999 ){
                    return '其他';
                }else{
                    return '未知';
                }
            });

            $grid->ruleRemark('处置要求说明');
            $grid->reason('处置原因');
            $grid->effectiveDate('生效时间');
            $grid->effectiveScope('生效范围');
            $grid->contacts('联系人/联系电话');
            $grid->generateTime('生成时间');
            $grid->remark('备注');

            $grid->handle_status('处理状态')->display(function($value){
                if( $value == 1 ){
                    return "<span class='label label-info'>未处理</span>";
                }elseif( $value == 2 ) {
                    return "<span class='label label-success'>已处理</span>";
                }elseif( $value == 3 ){
                    return "<span class='label label-warning'>可能失败</span>";
                }else{
                    return "<span class='label label-danger'>未知</span>";
                }
            });

            $grid->handle_group('处理分组')->display(function($value){
                if( $value == 1 ){
                    return '测试组';
                }elseif( $value == 2 ){
                    return '正式组';
                }else{
                    return '未知';
                }
            });

            $grid->created_at();
            $grid->updated_at();

            $grid->disableExport();
            $grid->disableCreation();
            $grid->disableRowSelector();
            $grid->disableActions();

        });
    }

}

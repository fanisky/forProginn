<?php
/**
 * 域名模块
 */
namespace App\Admin\Controllers\Cdn;

use App\Models\Cdn\Domain;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class DomainController extends Controller
{
    use ModelForm;

    /**
     * 域名列表
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('域名');
            $content->description('');

            $content->body($this->grid());
        });
    }

    /**
     * 域名编辑
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('域名');
            $content->description('编辑');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * 域名新增
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('域名');
            $content->description('新增');

            $content->body($this->form());
        });
    }

    /**
     * 域名Grid
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Domain::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->domain('源站域名');
            $grid->ip('源站IP');
            $grid->cname_domain('用于CNAME')->display(function(){
                return "cn{$this->id}.cname.noname.com";
            });

            $grid->created_at();
            $grid->updated_at();

            $grid->disableExport();
            $grid->disableRowSelector();
            $grid->disableFilter();

            $grid->actions(function ($actions) {
                $actions->disableEdit();
            });
        });
    }

    /**
     * 表单
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Domain::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('domain','源站域名');
            $form->text('ip','源站IP');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}

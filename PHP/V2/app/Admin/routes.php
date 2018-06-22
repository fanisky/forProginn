<?php

use App\Admin\Controllers\FinancialSummary\CustomerController;
use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    //CND域名和解析管理
    Route::resource('cdn/host','Cdn\HostController');
    Route::resource('cdn/spCommand','Cdn\SpCommandController');
    Route::resource('cdn/domain','Cdn\DomainController');

});

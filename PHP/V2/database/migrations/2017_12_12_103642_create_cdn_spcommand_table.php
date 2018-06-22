<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdnSpcommandTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdn_spcommand', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sn')->unique()->comment('业务流水号');
            $table->integer('commandType')->comment('指令类型');
            $table->integer('type')->comment('管理指令 类型');
            $table->integer('objectType')->comment('处置对象类型');
            $table->string('object')->comment('处置对象');
            $table->integer('rule')->comment('处置要求');
            $table->string('ruleRemark')->comment('处置要求说明');
            $table->string('reason')->comment('处置原因');
            $table->timestamp('effectiveDate')->nullable()->comment('生效时间');
            $table->string('effectiveScope')->comment('生效范围');
            $table->string('contacts')->comment('联系人/联系电话');
            $table->timestamp('generateTime')->nullable()->comment('生成时间');
            $table->string('remark')->comment('备注');
            $table->integer('handle_status')->command('处理状态')->default(1);
            $table->integer('handle_group')->command('处理分组(1测试组 2正式组)');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdn_spcommand');
    }
}

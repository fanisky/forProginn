<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdnDomainTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdn_domain', function (Blueprint $table) {
            $table->increments('id');
            $table->string('domain')->comment('源站域名');
            $table->string('ip')->comment('源站');
            $table->string('cname_domain')->comment('CName域名');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cdn_domain');
    }
}

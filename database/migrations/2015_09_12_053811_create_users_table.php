<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('users', function (Blueprint $table) {
         $table->increments('id');
         $table->string('name', 16);
         $table->string('email', 64);
         $table->string('password', 128);
         $table->longText('avatar');
         $table->string('location');
         $table->string('region');
         $table->integer('role')->default(0);
         $table->rememberToken();
         $table->boolean('ban')->default(0);
         $table->timestamp('last_login');
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
        Schema::drop('users');
    }
}

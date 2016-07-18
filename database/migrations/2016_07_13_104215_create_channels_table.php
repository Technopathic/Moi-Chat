<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('channels', function (Blueprint $table) {
        $table->increments('id');
        $table->string('channelName');
        $table->integer('userID');
        $table->string('channelPassword')->nullable();
        $table->boolean('channelLock')->default(0);
        $table->string('channelRegion');
        $table->string('channelLocation');
        $table->longText('channelImg')->nullable();
        $table->string('channelTopic')->nullable();
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
      Schema::drop('channels');
    }
}

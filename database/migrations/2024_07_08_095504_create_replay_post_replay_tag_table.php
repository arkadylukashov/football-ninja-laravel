<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up (): void {
    Schema::create('replay_post_replay_tag', function (Blueprint $table) {
      $table->id();
      $table->integer('replay_post_id');
      $table->foreign('replay_post_id')->references('id')->on('replay_posts');
      $table->integer('replay_tag_id');
      $table->foreign('replay_tag_id')->references('id')->on('replay_tags');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down (): void {
    Schema::dropIfExists('replay_post_replay_tag');
  }
};

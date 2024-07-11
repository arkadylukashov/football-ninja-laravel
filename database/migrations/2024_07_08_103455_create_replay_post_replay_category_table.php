<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up (): void {
    Schema::create('replay_post_replay_category', function (Blueprint $table) {
      $table->id();
      $table->integer('replay_post_id');
      $table->foreign('replay_post_id')->references('id')->on('replay_posts');
      $table->integer('replay_category_id');
      $table->foreign('replay_category_id')->references('id')->on('replay_categories');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down (): void {
    Schema::dropIfExists('replay_post_replay_category');
  }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up (): void {
    Schema::create('replay_posts', function (Blueprint $table) {
      $table->integer('id')->primary();
      $table->string('title');
      $table->integer('type');
      $table->text('urls')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down (): void {
    Schema::dropIfExists('replay_posts');
  }
};

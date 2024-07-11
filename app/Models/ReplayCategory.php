<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplayCategory extends Model {
  protected $guarded = [];

  public function posts () {
    return $this->belongsToMany(ReplayPost::class);
  }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplayPost extends Model {
  protected $guarded = [];

  public $incrementing = false;

  protected $casts = [
    'urls' => 'json',
    'date' => 'datetime',
    'original_created_at' => 'datetime',
  ];

  public function tags () {
    return $this->belongsToMany(ReplayTag::class, 'replay_post_replay_tag');
  }

  public function categories () {
    return $this->belongsToMany(ReplayCategory::class, 'replay_post_replay_category');
  }
}

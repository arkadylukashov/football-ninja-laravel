<?php

namespace App\Console\Commands;

use App\Enums\ReplayPostType;
use App\Http\Resources\ReplayPostCollection;
use App\Models\ReplayPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ReplayControllerSaver extends Command {
  protected $signature = 'app:replay-controller-saver';

  protected $description = 'Command description';

  public function handle () {
    Storage::disk('hub')->put('all.json', json_encode($this->getPosts()));
    Storage::disk('hub')->put('matches.json', json_encode($this->getPosts(ReplayPostType::MATCH->value)));
    Storage::disk('hub')->put('broadcasts.json', json_encode($this->getPosts(ReplayPostType::BROADCAST->value)));
    Storage::disk('hub')->put('overviews.json', json_encode($this->getPosts(ReplayPostType::OVERVIEW->value)));
  }

  private function getPosts ($type = null) {
    $list = ReplayPost::query()
      ->when($type, fn ($q) => $q->where('type', $type))
      ->orderBy('original_created_at', 'desc')
      ->get();

    return new ReplayPostCollection($list);
  }
}

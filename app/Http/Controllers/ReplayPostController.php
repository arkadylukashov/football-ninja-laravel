<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReplayPostCollection;
use App\Http\Resources\ReplayPostResource;
use App\Models\ReplayPost;
use Illuminate\Http\Request;

class ReplayPostController extends Controller {
  public function index (Request $request) {
    $list = ReplayPost::query()
      ->when($request->type, fn ($q) => $q->where('type', $request->type))
      ->orderBy('original_created_at', 'desc')
      ->get();

    return new ReplayPostCollection($list);
  }
}

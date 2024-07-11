<?php

namespace App\Http\Resources;

use App\Enums\ReplayPostType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ReplayPostResource extends JsonResource {
  public static $wrap = null;

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray (Request $request): array {
    $media = [];

    foreach (json_decode($this->resource->urls) as $title => $url) {
      $type = null;

      if (Str::lower($title) === 'лучшие моменты') {
        $type = 'HIGHLIGHTS';
      } else if (Str::lower($title) === '1, 2 тайм') {
        $type = 'FULL';
      }

      $media[] = [
        'title' => $title,
        'type' => $type,
        'url' => $url,
      ];
    }

    return [
      'id' => $this->resource->id,
      'title' => $this->resource->title,
      'score' => $this->resource->scores
        ? base64_encode(str_replace(' ', '', str_replace('—', ':', base64_decode($this->resource->scores))))
        : null,
      'type' => ReplayPostType::tryFrom($this->resource->type)->name,
      'date' => $this->resource->date,
      'media' => $media,
      'tags' => new ReplayTagCollection($this->resource->tags),
      'categories' => new ReplayCategoryCollection($this->resource->categories),
    ];
  }
}

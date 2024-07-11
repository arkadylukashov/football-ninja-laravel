<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReplayCategoryResource extends JsonResource {
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray (Request $request): array {
    return [
      'id' => $this->resource->id,
      'slug' => $this->resource->slug,
      'title' => $this->resource->title,
      'name' => $this->resource->name,
    ];
  }
}

<?php

namespace App\Console\Commands\Grabbers;

use App\Enums\ReplayPostType;
use App\Models\ReplayCategory;
use App\Models\ReplayPost;
use App\Models\ReplayTag;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use simplehtmldom\HtmlDocument;

class ReplayInfoGrabber extends Command {
  protected $signature = 'app:replay-info-grabber';

  protected $description = 'ReplayFootballMatches info grabber';

  protected $url = 'https://replayfootballmatches.ru/wp-json/wp/v2';

  protected $BROADCAST_CATEGORY = 639;

  protected $OVERVIEW_CATEGORY = 599;

  public function handle (): void {
    try {
      if (true) {
        $categories = $this->fetchCategories();
        foreach ($categories as $category) {
          ReplayCategory::updateOrCreate(['id' => $category['id']], $category);
        }

        $tags = $this->fetchTags();
        foreach ($tags as $tag) {
          ReplayTag::updateOrCreate(['id' => $tag['id']], $tag);
        }
      }

      $posts = [
        ...$this->fetchMatchesPosts(),
        ...$this->fetchBroadcastsPosts(),
        ...$this->fetchOverviewsPosts(),
      ];
      foreach ($posts as $post) {
        $dbPreparedData = [
          'id' => $post['id'],
          'title' => $post['title'],
          'urls' => json_encode($post['urls']),
          'scores' => $post['scores'],
          'type' => $post['type'],
          'date' => $post['date'],
          'original_created_at' => $post['original_created_at'],
        ];

        $replayPost = ReplayPost::find($post['id']);

        if (!$replayPost) {
          $replayPost = ReplayPost::create($dbPreparedData);
          $replayPost->tags()->attach(ReplayTag::find($post['tags']));
          $replayPost->categories()->attach(ReplayCategory::find($post['categories']));
        } else {
          // TODO Пост уже существует, потому апдейтим и не обращаем внивание на тэги и категории
          $replayPost->fill($dbPreparedData)->save();
          //$existingTags = $replayPost->tags->map(fn ($i) => $i->id)->toArray();
          //$rawTags = [...$post['tags'], 25];
        }
      }

      Storage::disk('local')->put('posts.json', json_encode($posts, JSON_PRETTY_PRINT));
    } catch (\Exception $e) {
      dd('Error', $e);
    }
  }

  private function buildUrl ($type = 'posts', $page = 1): string {
    return $this->url . '/' . $type . '?per_page=100&page=' . $page;
  }

  // Categories
  protected function fetchCategories (): array {
    $result = [];

    $this->line('Fetch Categories page 1');

    $data = Http::get($this->buildUrl('categories'))->json();

    foreach ($data as $item) {
      $result[$item['id']] = [
        'id' => $item['id'],
        'title' => $item['description'],
        'name' => $item['name'],
        'slug' => $item['slug'],
      ];
    }

    return $result;
  }

  // Tags
  protected function fetchTags ($type = 'latest'): array {
    $result = [];

    $totalPages = $type === 'latest' ? 1 : 6;

    for ($page = 1; $page <= $totalPages; $page += 1) {
      $this->line('Fetch Tags page ' . $page);

      $data = Http::get($this->buildUrl('tags', $page))->json();

      foreach ($data as $item) {
        $result[$item['id']] = [
          'id' => $item['id'],
          'title' => $item['description'],
          'name' => $item['name'],
          'slug' => $item['slug'],
        ];
      }
    }

    return $result;
  }

  // Posts
  private function buildPostsUrl ($params, $page = 1): string {
    return $this->url . '/posts?' . $params . '&per_page=10&page=' . $page;
  }

  protected function fetchAllPosts ($totalPages = 1): array {
    $result = [];

    for ($page = 1; $page <= $totalPages; $page += 1) {
      $this->line('Fetch Posts page ' . $page);

      $data = Http::get($this->buildUrl('posts', $page))->json();

      foreach ($data as $item) {
        $result[] = $this->convertPost($item);
      }
    }

    return $result;
  }

  protected function fetchMatchesPosts ($totalPages = 1): array {
    $result = [];

    for ($page = 1; $page <= $totalPages; $page += 1) {
      $this->line('Fetch Matches page ' . $page);

      $data = Http::get($this->buildPostsUrl('categories_exclude=' . $this->OVERVIEW_CATEGORY . ',' . $this->BROADCAST_CATEGORY, $page))->json();

      foreach ($data as $item) {
        $result[] = $this->convertPost($item);
      }
    }

    return $result;
  }

  protected function fetchBroadcastsPosts ($totalPages = 1): array {
    $result = [];

    for ($page = 1; $page <= $totalPages; $page += 1) {
      $this->line('Fetch Broadcasts page ' . $page);

      $data = Http::get($this->buildPostsUrl('categories=' . $this->BROADCAST_CATEGORY, $page))->json();

      foreach ($data as $item) {
        $result[] = $this->convertPost($item);
      }
    }

    return $result;
  }

  protected function fetchOverviewsPosts ($totalPages = 1): array {
    $result = [];

    for ($page = 1; $page <= $totalPages; $page += 1) {
      $this->line('Fetch Overviews page ' . $page);

      $data = Http::get($this->buildPostsUrl('categories=' . $this->OVERVIEW_CATEGORY, $page))->json();

      foreach ($data as $item) {
        $result[] = $this->convertPost($item);
      }
    }

    return $result;
  }

  private function convertPost ($post) {
    if (in_array($this->BROADCAST_CATEGORY, $post['categories'])) {
      $type = ReplayPostType::BROADCAST->value;
    } else if (in_array($this->OVERVIEW_CATEGORY, $post['categories'])) {
      $type = ReplayPostType::OVERVIEW->value;
    } else {
      $type = ReplayPostType::MATCH->value;
    }

    $rawContent = $post['content']['rendered'];
    $document = new HtmlDocument($rawContent);

    return [
      'id' => $post['id'],
      'tags' => $post['tags'],
      'categories' => $post['categories'],
      'title' => html_entity_decode($post['title']['rendered']),
      'urls' => $this->parsePostTabs($document, $rawContent),
      'scores' => $type === ReplayPostType::MATCH->value ? base64_encode($this->parsePostScores($document, $rawContent)) : null,
      'type' => $type,
      'date' => $this->parsePostDate($post),
      'original_created_at' => Carbon::parse($post['date_gmt']),
    ];
  }

  private function parsePostDate ($post) {
    try {
      preg_match('/\d+\.\d+\.\d+/', $post['yoast_head_json']['title'], $matches);

      if ($matches) {
        return Carbon::createFromTimestamp(strtotime($matches[0]));
      } else {
        return null;
      }
    } catch (\Exception $e) {
      return null;
    }
  }

  private function parsePostTabs ($document, $rawInput) {
    $tabs = [];

    foreach ($document->find('.tabtitle') as $tabTitle) {
      $tabs[] = [
        'title' => $tabTitle->plaintext,
        'url' => null,
      ];
    }

    foreach ($document->find('.tabcontent') as $index => $tabContent) {
      try {
        $img = $tabContent->find('img.popupwindow');

        if ($img) {
          $imgOnclick = $img[0]->onclick;

          preg_match("/\'(.*)\'/", $imgOnclick, $matches);

          $tabs[$index]['url'] = $matches[1];
        }
      } catch (\Exception $e) {
        $this->line('ERROR');
        dump($rawInput);
      }
    }

    $result = [];

    foreach ($tabs as $tab) {
      $result[ $tab['title'] ] = $tab['url'];
    }

    return $result;
  }

  private function parsePostScores ($document, $rawInput) {
    try {
      $scoresElement = $document->find('#scores');

      if ($scoresElement) {
        return html_entity_decode($scoresElement[0]->plaintext);
      }

      return null;
    } catch (\Exception $e) {
      $this->line('ERROR');
      dump($rawInput);
    }
  }
}

<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

function cleanupLogs ($dir): void {
  $storage = Storage::disk('cron');

  $suspendedLogs = collect($storage->files($dir))
    ->sort(fn ($a, $b) => $a < $b)
    ->slice(10)
    ->toArray();

  if (count($suspendedLogs) > 0) {
    $storage->delete($suspendedLogs);
  }
}

$startTime = Carbon::now();
$utcOffset = 600;


Schedule::command('app:replay-info-grabber')
  ->everyMinute()
  ->runInBackground()
  ->sendOutputTo(storage_path('app/cron/replay-info-grabber/' . $startTime->timestamp . '__' . $startTime->utcOffset($utcOffset)->format('d-m-Y_H-i-s') . '.log'));

cleanupLogs('replay-info-grabber');

<?php

namespace App\Enums;

enum ReplayPostType: int {
  case MATCH = 1;

  case BROADCAST = 2;

  case OVERVIEW = 3;
}

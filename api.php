<?php 

require 'carbon/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;

http_response_code(400);
die("There was an error");

printf("Now: %s", Carbon::now());

printf("1 day: %s", CarbonInterval::day()->forHumans());

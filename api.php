<?php 

require 'carbon/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonTimeZone;
use Carbon\Exceptions\InvalidFormatException;
use Carbon\Exceptions\InvalidTimeZoneException;


$date1 = Carbon::create($_GET['date1']);
$date2 = Carbon::create($_GET['date2']);


#TODO(Christof): we could do something like keep track of all the issues and output all of them at the end, which might be useful, but doesn't seem worth the effort?
if(isset($_GET['timezone1']))
{
	try
	{
		$thing = CarbonTimeZone::create($_GET['timezone1']);
	} 
	catch(InvalidFormatException|InvalidTimeZoneException $e)
	{	
		$thing = false;
	}

	if($thing)
	{
		$date1->SetTimeZone($thing);	
	}
	else 
	{
		http_response_code(400);
		die("Please supply a valid timezone for datetime1");
	}
}


if(isset($_GET['timezone2']))
{
	try
	{
		$thing = CarbonTimeZone::create($_GET['timezone2']);
	} 
	catch(InvalidFormatException|InvalidTimeZoneException $e)
	{	
		$thing = false;
	}

	if($thing)
	{
		$date1->SetTimeZone($thing);	
	}
	else 
	{
		http_response_code(400);
		die("Please supply a valid timezone for datetime2");
	}
}

$result = 0;
$resultType = 'days';
switch ($_GET['operation']) {
	case 'Days':
		$result = $date2->diffInDays($date1);
		break;

	case 'Weekdays':
		#NOTE(Christof): for some bizzare reason, Carbon appears to be off by 1 if you use weekdays? e.g. Fri->Fri is 7 days, but 6 weekdays, yet if you hang on another full week the numbers increase correctly (to 14 and 11 respectively), but since the WeekendDays work correctly we can kinda just implement the correct behaviour ourselves? This is giving me serious throw the library out and just write the shit ourselves vibes, so might just end up doing that.
		$result = $date2->diffInDays($date1) - $date2->diffInWeekendDays($date1);
		break;

	case 'CompleteWeeks':
		$result = $date2->diffInWeeks($date1);
		$resultType = 'weeks';
		break;
	
	default:
		# code...
		break;
}


#NOTE(Christof): since we're converting the above value and not doing a different kind of diff here, we're not accounting for things like DST, leapseconds and leapyears and just using somewhat arbitrary definitions of what for example a year means exactly
define("DAYS_IN_YEAR", 365.242199);
define("WEEKS_IN_YEAR", DAYS_IN_YEAR / 7);
define("HOURS_IN_DAY", 24);
define("HOURS_IN_WEEK", 24 * 7);
define("MINUTES_IN_DAY", HOURS_IN_DAY * 60);
define("MINUTES_IN_WEEK", MINUTES_IN_DAY * 7);
define("SECONDS_IN_DAY", MINUTES_IN_DAY * 60);
define("SECONDS_IN_WEEK", SECONDS_IN_DAY * 7);

if(isset($_GET['convert']))
{
	switch ($_GET['convert']) {
		case 'seconds':
			if($resultType == "weeks")
			{
				$result *= SECONDS_IN_WEEK;
			}
			else
			{
				$result *= SECONDS_IN_DAY;
			}
			$resultType = 'seconds';
			break;

		case 'minutes':
			if($resultType == "weeks")
			{
				$result *= MINUTES_IN_WEEK;
			}
			else
			{
				$result *= MINUTES_IN_DAY;
			}
			$resultType = 'minutes';
			break;
		
		case 'hours':
			if($resultType == "weeks")
			{
				$result *= HOURS_IN_WEEK;
			}
			else
			{
				$result *= HOURS_IN_DAY;
			}
			$resultType = 'hours';
			break;

		case 'years':
			if($resultType == "weeks")
			{
				$result /= WEEKS_IN_YEAR;
			}
			else
			{
				$result /= DAYS_IN_YEAR;
			}
			$resultType = 'years';
			break;

		default:
			# code...
			break;
	}
}



echo $result;


/*http_response_code(400);
die("There was an error");

printf("Now: %s", Carbon::now());

printf("1 day: %s", CarbonInterval::day()->forHumans());*/




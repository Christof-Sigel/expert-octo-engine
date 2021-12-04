<?php 

if(!isset($_GET['date1']) || !isset($_GET['date2']) || !isset($_GET['operation']))
{
	http_response_code(400);
	die("Parameters 'date1', 'date2' and 'operation' are required");
}

$default_timezone_name = date_default_timezone_get();

$default_timezone = timezone_open($default_timezone_name);

$timezone1 = $timezone2 = $default_timezone;
$timezone1_name = $timezone2_name = $default_timezone_name;




#TODO(Christof): we could do something like keep track of all the issues and output all of them at the end, which might be useful, but doesn't seem worth the effort?
if(isset($_GET['timezone1']))
{
	try
	{
		$thing = timezone_open($_GET['timezone1']);
	} 
	catch(Exception $e)
	{	
		$thing = false;
	}

	if($thing)
	{
		$timezone1 = $thing;
		$timezone1_name = $_GET['timezone1'];
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
		$thing = timezone_open($_GET['timezone2']);
	} 
	catch(Exception $e)
	{	
		$thing = false;
	}

	if($thing)
	{
		$timezone1 = $thing;
		$timezone1_name = $_GET['timezone2'];
	}
	else 
	{
		http_response_code(400);
		die("Please supply a valid timezone for datetime1");
	}
}


define("DATETIME_FORMAT", "Y-m-d\\TG:i");
$date1 = date_create_from_format(DATETIME_FORMAT, $_GET['date1'], $timezone1);

if(!$date1)
{
	http_response_code(400);
	die("Please supply a valid datetime1");
}

$date2 = date_create_from_format(DATETIME_FORMAT, $_GET['date2'], $timezone2);
if(!$date2)
{
	http_response_code(400);
	die("Please supply a valid datetime2");
}


#NOTE(Christof): convert date2 to be in date1's timezone
if($timezone1_name != $timezone2_name)
{
	$date2->setTimezone($timezone1);
}


#NOTE(Christof): so that the following operations don't have to worry about what order the input dates are in, switch them around here if date2 is before date1
if($date2 < $date1)
{
	$temp = $date2;
	$date2 = $date1;
	$date1 = $temp;
}


$result = 0;
$resultType = 'days';
#NOTE(Christof): do the simple, dumb thing that vaguely makes sense (although this is sufficiently bad it should probably change?)
switch ($_GET['operation']) 
{
	case 'Days':
		if($date1 < $date2)
		{
			$date1->setTime(0,0);
			while($date1 < $date2)
			{
				$result++;
				$date1->modify('+1 day');
			}
		}
		break;

	case 'Weekdays':
		if($date1 < $date2)
		{
			$date1->setTime(0,0);
			while($date1 < $date2)
			{
				if($date1->format("N") == 6 ||$date1->format("N") == 7 )
				{
					#NOTE(Christof): do not increment if we're on the weekend!
				}
				else 
				{
					$result++;
				}
				$date1->modify('+1 day');
			}
		}
		break;

	case 'CompleteWeeks':
		if($date1 < $date2)
		{
			$date1->setTime(0,0);
			#NOTE(Christof): forward to the first Monday and then count the number of Sundays, this gets us the number of full weeks (Monday->Sunday sequences)
			while($date1 < $date2)
			{
				if($date1->format("N") == 1 )
				{
					break;
				}
				$date1->modify('+1 day');
			}
			while($date1 < $date2)
			{
				if($date1->format("N") == 7 )
				{
					$result++;
				}
				$date1->modify('+1 day');
			}
		}
		$resultType = 'weeks';
		break;
	
	default:
		http_response_code(400);
		die("Please try a valid operation");
}


#NOTE(Christof): since we're converting the above value and not doing a different kind of diff here, we're not accounting for things like DST, leapseconds and leapyears and using a somewhat arbitrary definition of what a year means exactly
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
			http_response_code(400);
			die("Please try a valid conversion");
	}
}



echo $result;



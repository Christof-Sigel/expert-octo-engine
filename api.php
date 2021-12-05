<?php 


#NOTE(Christof): timezone_open generates a warning if you pass an invalid timezone, which honestly should be covered by the exceptions it throws and the possibile false return value, but what do I know?
error_reporting(E_ERROR | E_PARSE);

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
		$timezone2 = $thing;
		$timezone2_name = $_GET['timezone2'];
	}
	else 
	{
		http_response_code(400);
		die("Please supply a valid timezone for datetime2");
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


define("USE_FASTER_PATH",true);

function CalculateNumberOfDaysBetweenDates(DateTime $date1, DateTime $date2): int
{
	if($date1 == $date2)
	{
		return 0;
	}
	$result = 1;

	$day1 = intval($date1->format("j"));
	$month1 = intval($date1->format("n"));
	$year1 = intval($date1->format("Y"));


	$day2 = intval($date2->format("j"));
	$month2 = intval($date2->format("n"));
	$year2 = intval($date2->format("Y"));


	$result += $day2 - $day1;

	while($month1 != $month2)
	{
		if($month1 == 2)
		{
			$result += ($year1 %4 == 0 && ( $year1 % 400 == 0 || $year1 %100 !=0))?29:28;
		}
		elseif ($month1 == 1
				|| $month1 == 3
				|| $month1 == 5
				|| $month1 == 7
				|| $month1 == 8
				|| $month1 == 10
				|| $month1 == 12) 
		{
			$result += 31;
		}
		else
		{
			$result += 30;
		}
		$month1 += 1;
		if($month1 > 12)
		{
			$month1 -=12;
			$year1++;
		}
	}

	while($year1 < $year2)
	{
		$test_year = $year1;
		if($month1 > 2)
		{
			$test_year++;
		}
		$result += ($test_year %4 == 0 && ( $test_year % 400 == 0 || $test_year %100 !=0))?366:365;
		$year1++;
	}

	return $result;
}

$result = 0;
$resultType = 'days';
#NOTE(Christof): do the simple, dumb thing that vaguely makes sense (although this is sufficiently bad it should probably change?)
switch ($_GET['operation']) 
{
	case 'Days':
		if(!USE_FASTER_PATH)
		{
			if($date1 < $date2)
			{
				$date1->setTime(0,0);
				while($date1 < $date2)
				{
					$result++;
					$date1->modify('+1 day');
				}
			}
		}
		else
		{
			$result = CalculateNumberOfDaysBetweenDates($date1, $date2);
		}
		break;

	case 'Weekdays':
		if(!USE_FASTER_PATH)
		{
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
		}
		else
		{
			if($date1>=$date2)
			{
				break;
			}
			$result = CalculateNumberOfDaysBetweenDates($date1, $date2);


			$NumDays = $result;
			$day_of_week1 = intval($date1->format("N"));
			$day_of_week2 = intval($date2->format("N"));

			//NOTE(Christof): this turns the number of days into some multiple of weeks, 
			$NumDays -= (8 - $day_of_week1);
			$NumDays -= $day_of_week2;

			$NumWeeks = $NumDays / 7;
			$result -=  $NumWeeks *2;

			if($day_of_week1 ==7)
			{
				$result--;
			}
			else
			{
				$result -= 2;
			}

			if($day_of_week2 ==7)
			{
				$result -=2;
			}
			elseif($day_of_week2 == 6)
			{
				$result--;
			}
		}
		break;

	case 'CompleteWeeks':
		if(!USE_FASTER_PATH)
		{
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
		}
		else 
		{
			if($date1>=$date2)
			{
				break;
			}
			$NumDays = CalculateNumberOfDaysBetweenDates($date1, $date2);


			
			$day_of_week1 = intval($date1->format("N"));
			$day_of_week2 = intval($date2->format("N"));

			//NOTE(Christof): this turns the number of days into some multiple of weeks, 
			if($day_of_week1 != 1)
			{
				$NumDays -= (8 - $day_of_week1);
			}
			if($day_of_week2 != 7)
			{
				$NumDays -= $day_of_week2;
			}

			$NumWeeks = $NumDays / 7;

			$result = $NumWeeks;
			if($result < 0)
			{
				$result = 0;
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



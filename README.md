# expert-octo-engine

###Why the name? 
Well, it was github's suggestion and I liked it, so why not?

##Instructions for running
Put the files in a directory served by a web-server that supports php.

index.html has a convenience page for running requests against the api.

##API Docs

Arguments are passed in Get parameters to api.php.

*date1* and *date2* are datetimes in YYYY-MM-DDTHH:MM format and required.

The api supports three different operations, in the *operation* required parameter:

	1. *Days* : returns the number of full days between *date1* and *date2* (including *date1* and *date2*), where partial days are counted as a full day.
	2. *Weekdays*: as 1. but any Saturdays or Sundays in the range are not counted.
	3. *CompleteWeeks*: returns the number of complete weeks (Mondays->Sundays) that occur between the two supplied datetimes. E.g. Wednesday -> Thursday a week later is counted as 0 weeks (since there is no complete sequence of days starting at a Monday and ending on a Sunday in that range).

The *timezone1* and *timezone2* optional parameters specify timezones for *date1* and *date2* (defaulting to the default timezone on the machine it's being run on if not supplied). If the timezones for the two dates are not the same, *date2* is converted to be in *date1*s timezone before any calculations are made.

By passing *seconds*, *minutes*, *hours* or *years* in the optional *convert* parameter the result is returned in those units instead (where a year is considered to be 365.242199 days).

Response is a text encoded number, with errors reported as a 400 response code and an error message.

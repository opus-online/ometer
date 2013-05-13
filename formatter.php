#!/usr/bin/php
<?php
define('OUTPUT_FILE', './output/report.csv');


$files = glob('output/*.xml');
natsort ($files);

$results = array();
$urls = array();
$concurrent = array();
$totalTimes = array();
$requestCounter = array();

foreach ($files as $file)
{
	$filematch = array();
	if (preg_match('/\w+(\d+)\.xml$/U', $file, $filematch))
	{
		$content = file_get_contents($file);
		$xml = new SimpleXMLElement($content);

		if (count($xml->httpSample))
		{
			$concurrency = $filematch[1];
			$concurrent[] = $concurrency;
			$requestCounter[$concurrency] = 0;

			$timestamps = array();
			foreach ($xml->httpSample as $sample)
			{
				$attrs = $sample->attributes();
				$matches = array();
				if (preg_match('/URL request \((.*)\)/', $attrs->lb, $matches))
				{
					$urls[$matches[1]] = array('url' => $matches[1], 'bytes' => (int)$attrs->by);
				}

				if (!isset($results[$concurrency][$matches[1]])) {
					$results[$concurrency][$matches[1]] = array();
				}

				$results[$concurrency][$matches[1]][] = (int)$sample->attributes()->t;
				$timestamps[] = (integer)$attrs->ts;
				$requestCounter[$concurrency]++;
			}
			$totalTimes[$concurrency] = max($timestamps) - min($timestamps);
		}
		else
		{
			echo "File did not contain any results\n";
		}
	}
}

$output = fopen(OUTPUT_FILE, 'w');

// write file headers
$headers1 = array('', '', '');
$headers2 = array('URL', 'Bytes', '');
foreach ($concurrent as $no) {
	$headers1 = array_merge($headers1, array(sprintf('%d CONCURRENT', $no), '', '', '', ''));
	$headers2 = array_merge($headers2, array('Count', 'Sum (ms)', 'Avg (ms)', 'Req/s', ''));
}
fputcsv($output, $headers1, ';');
fputcsv($output, $headers2, ';');



// write data
foreach ($urls as $url)
{
	$line = array($url['url'], $url['bytes'], '');
	foreach ($concurrent as $no) {
		$data = $results[$no][$url['url']];
		$sum = array_sum($data);
		$line[] = count($data);
		$line[] = $sum;
		$line[] = number_format($sum / count($data), 2, ',', '');
		$line[] = number_format(count($data) / ($sum / 1000), 2, ',', '');
		$line[] = '';
	}
	fputcsv($output, $line, ';');
}

$summaryLine = array('Requests sent (req/s)', '', '');

foreach ($concurrent as $no)
{
	$reqPerSec = number_format($totalTimes[$no] ? $requestCounter[$no] / ($totalTimes[$no] / 1000) : 0, 2, ',', '');
	$summaryLine = array_merge($summaryLine, array($reqPerSec, '', '', '', ''));
}
fputcsv($output, array());
fputcsv($output, $summaryLine, ';');



fclose($output);


echo sprintf("Wrote results to %s \n", OUTPUT_FILE);


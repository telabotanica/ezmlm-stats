<?php

$out = fopen('lists-stats.csv', 'w');

$conf = file_get_contents('conf.json', 'r');
$conf = json_decode($conf, true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://vpopmail.tela-botanica.org/ezmlm-php-ng/lists');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	$conf['header'] . ': ' . $conf['token']
));
$data = curl_exec($ch);
curl_close($ch);

$data = json_decode($data, true);
$lists = $data['results'];

$stats = [];
foreach ($lists as $list) {
	$stats[$list] = [];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://vpopmail.tela-botanica.org/ezmlm-php-ng/lists/'.$list.'/calendar');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		$conf['header'] . ': ' . $conf['token']
	));
	$data = curl_exec($ch);
	curl_close($ch);

	$calendar = json_decode($data, true);

	if (is_array($calendar)) {
		foreach ($calendar as $year => $counts) {
			$stats[$list][$year] = array_sum($counts);
		}
	}
}

// list years csv columns
for ($i = 2000; $i <= date('Y'); $i++) {
	$years[] = $i;
}
// write csv header
fputcsv($out, array_merge(['liste'], $years, ['total']));

// write one line for each list
foreach ($stats as $list => $data) {
	$line = [];
	foreach ($years as $year) {
		if (array_key_exists($year, $data)) {
			$line[] = $data[$year];
		} else {
			$line[] = '';
		}
	}

	fputcsv($out, array_merge([$list], $line, [array_sum($line)]));
}

fclose($out);

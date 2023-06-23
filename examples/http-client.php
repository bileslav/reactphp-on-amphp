<?php
declare(strict_types=1);

/*
Usage:
$ php examples/http-client.php 0 # RuntimeException: Timer cancelled
$ php examples/http-client.php 0.02 # RuntimeException: Request cancelled
$ php examples/http-client.php # 200 OK
*/

use Amp\TimeoutCancellation;

require __DIR__ . '/../vendor/autoload.php';

$client = new HttpClient();
$timeout = new TimeoutCancellation((float) ($argv[1] ?? 10));

try {
	$response = $client->get('https://www.google.com/', $timeout);
} catch (Throwable $e) {
	fwrite(STDERR, "{$e}\n");

	exit(1);
}

var_dump($response->getStatusCode());

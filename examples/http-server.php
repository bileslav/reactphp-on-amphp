<?php
declare(strict_types=1);

use Bileslav\AmpedReact\CancellableRequestMiddleware;
use DateTimeInterface as DateTime;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

use function Amp\delay;
use function Amp\trapSignal as trap_signal;
use function Bileslav\callable_hint;

require __DIR__ . '/../vendor/autoload.php';

function handle_request(ServerRequest $request): Response
{
	delay(5, cancellation: CancellableRequestMiddleware::getCancellation($request));

	return new Response(body: date(DateTime::COOKIE));
}

function handle_error(Throwable $e): void
{
	fwrite(STDERR, "{$e}\n");
}

$socket = new SocketServer('tcp://127.0.0.1:0');

$server = new HttpServer(new CancellableRequestMiddleware(), callable_hint('handle_request'));
$server->on('error', 'handle_error');
$server->listen($socket);

printf(
	"Server started on %s\nCtrl+C to stop\n",
	str_replace('tcp://127.0.0.1', 'http://localhost', $socket->getAddress()),
);

echo <<<'EOD'

Send a request (curl/browser) and immediately abort it.
There is a 5-second delay for this (imitation of some work).
You'll see the cancellation in STDERR.


EOD;

trap_signal(SIGINT);

exit("\n");

<?php
declare(strict_types=1);

use Amp\Cancellation;
use Psr\Http\Message\ResponseInterface as Response;
use React\EventLoop\Loop as CurrentEventLoop;
use React\EventLoop\LoopInterface as EventLoop;
use React\Http\Browser as Client;

use function Bileslav\AmpedReact\bind_cancellation;
use function React\Async\await;
use function React\Promise\Timer\sleep;

final class HttpClient
{
	private readonly EventLoop $loop;
	private readonly Client $client;

	public function __construct(?Client $client = null, ?EventLoop $loop = null)
	{
		$this->loop = $loop ?? CurrentEventLoop::get();
		$this->client = $client ?? new Client(loop: $this->loop);
	}

	public function get(string $url, ?Cancellation $cancellation = null): Response
	{
		$response = $this->client->requestStreaming('GET', $url);
		$some_other_promise = sleep(0.01, $this->loop);

		bind_cancellation($cancellation, $response);
		bind_cancellation($cancellation, $some_other_promise);

		await($some_other_promise);

		return await($response);
	}
}

<?php
declare(strict_types=1);

namespace Bileslav\AmpedReact;

use Amp\Cancellation;
use Amp\DeferredCancellation;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use React\Promise\CancellablePromiseInterface as CancellablePromise;
use React\Promise\Deferred;
use Revolt\EventLoop;
use Throwable;

final class CancellableRequestMiddleware
{
	/** Just a random string that no one will normally use as a request attribute name. */
	private const ATTRIBUTE_NAME = 'b1e0e1dd-a203-42cf-baf4-b6d2898d8ae9';

	/**
	 * @return \React\Promise\CancellablePromiseInterface<\Psr\Http\Message\ResponseInterface>
	 */
	public function __invoke(ServerRequest $request, callable $next_handler): CancellablePromise
	{
		$canceller = new DeferredCancellation();
		$promiser = new Deferred(static fn () => $canceller->cancel());

		$request = $request->withAttribute(self::ATTRIBUTE_NAME, $canceller->getCancellation());

		EventLoop::queue(static function () use ($next_handler, $request, $promiser): void {
			try {
				$response = $next_handler($request);
			} catch (Throwable $e) {
				$promiser->reject($e);

				return;
			}

			$promiser->resolve($response);
		});

		return $promiser->promise();
	}

	public static function getCancellation(ServerRequest $request): Cancellation
	{
		return $request->getAttribute(self::ATTRIBUTE_NAME);
	}
}

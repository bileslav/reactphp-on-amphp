<?php
declare(strict_types=1);

namespace Bileslav\AmpedReact;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\NullCancellation;
use React\Promise\CancellablePromiseInterface as CancellablePromise;
use React\Promise\ExtendedPromiseInterface as ExtendedPromise;
use Throwable;

use function Bileslav\append_previous;

function bind_cancellation(?Cancellation $cancellation, CancellablePromise&ExtendedPromise $promise): void
{
	if ($cancellation === null || $cancellation instanceof NullCancellation) {
		return;
	}

	$promise->done(onRejected: function (Throwable $e) use ($cancellation): void {
		try {
			$cancellation->throwIfRequested();
		} catch (CancelledException $previous) {
			append_previous($e, $previous);
		}
	});

	if ($cancellation->isRequested()) {
		$promise->cancel();
	} else {
		$cancellation_id = $cancellation->subscribe($promise->cancel(...));
		$unsubscriber = fn () => $cancellation->unsubscribe($cancellation_id);
		$promise->done($unsubscriber, $unsubscriber);
	}
}

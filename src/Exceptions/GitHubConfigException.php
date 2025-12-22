<?php

namespace App\Exceptions;

use Throwable;

class GitHubConfigException extends \Exception
{
    public function __construct(
        public readonly ?string $param = null,
        string $message = "Config parameter not found",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if ($this->param) {
            $message = sprintf('%s: %s', $this->param, $message);
        }
        parent::__construct($message, $code, $previous);
    }
}

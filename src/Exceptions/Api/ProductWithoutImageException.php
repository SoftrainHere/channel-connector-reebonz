<?php declare(strict_types = 1);

namespace App\Exceptions\Api;

use Exception;
use JetBrains\PhpStorm\Pure;

class ProductWithoutImageException extends Exception
{
    protected $message;
    protected $code;

    #[Pure]
    public function __construct($message = "", $code = 0)
    {
        parent::__construct();
        $this->message = $message;
        $this->code = $code;
    }

}

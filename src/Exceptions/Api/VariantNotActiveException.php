<?php declare(strict_types = 1);

namespace Mxncommerce\ChannelConnector\Exceptions\Api;

use Exception;
use JetBrains\PhpStorm\Pure;

class VariantNotActiveException extends Exception
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

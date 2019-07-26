<?php


namespace Unite\CMSWebsiteBundle\Exception;

use InvalidArgumentException;
use Throwable;

class RedirectException extends InvalidArgumentException
{

    /**
     * @var string $redirect
     */
    protected $redirect = '';

    /**
     * {@inheritDoc}
     */
    public function __construct(string $redirect, string $message = 'Direct access to this host is not allowed. You need to redirect!', $code = 0, Throwable $previous = null) {
        $this->redirect = $redirect;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getRedirect() : string {
        return $this->redirect;
    }
}

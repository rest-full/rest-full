<?php

declare(strict_types=1);

namespace Restfull\Error;

use Exception;
use Throwable;

/**
 *
 */
class Exceptions extends Exception
{

    /**
     * @var array
     */
    private $traces = [];

    /**
     * @var string
     */
    private $previous = Exception::class;

    /**
     * @param string $mensagem
     * @param string|null $error
     * @param array|null $trace
     * @param Throwable|null $previous
     */
    public function __construct(string $mensagem, int $error = null, array $trace = null, Throwable $previous = null)
    {
        if (is_object($mensagem)) {
            $this->message = $mensagem->getMessage();
            if ($mensagem->getCode() === 0) {
                $this->code = 404;
            } else {
                if (!empty($error)) {
                    $this->code = $error;
                } else {
                    $this->code = $mensagem->getCode();
                }
            }
            $this->file = $mensagem->getFile();
            $this->line = $mensagem->getLine();
            $this->traces = $mensagem->getTrace();
            $this->previous = $mensagem->getPrevious();
        } else {
            if (isset($trace)) {
                $newTrace = array_reverse($this->getTrace());
                $newTrace[] = $trace;
                $this->traces = array_reverse($newTrace);
            }
            parent::__construct($mensagem, (empty($error) || !is_null($error) ? 404 : $error), $previous);
        }
    }

    /**
     * @return array
     */
    public function getTraces(): array
    {
        if (count($this->traces) > 0) {
            return $this->traces;
        }
        return $this->getTrace();
    }

}

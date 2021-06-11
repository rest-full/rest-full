<?php

namespace Restfull\Error\Exceptions;

use Restfull\Error\Exceptions;
use Restfull\Filesystem\File;

/**
 * Class ErrorController
 * @package Restfull\Error\Exceptions
 */
class ErrorController
{

    /**
     * @param array $param
     * @return array
     */
    public function handling(array $param): array
    {
        $traces = [];
        for ($a = (count($param['traces']) - 1); $a >= 0; $a--) {
            if (in_array(
                            $param['traces'][$a]['function'],
                            ["__construct", "__Construct", 'loadClass']
                    ) === false || $a == 0) {
                if (isset($param['traces'][$a]['class']) && isset($param['traces'][$a]['type']) && isset($param['traces'][$a]['function'])) {
                    $function = $param['traces'][$a]['class'] . $param['traces'][$a]['type'] . $param['traces'][$a]['function'];
                    if (!in_array($function, $traces)) {
                        $arguments[$function] = $this->arguments(
                                $param['traces'][$a]['line'],
                                $param['traces'][$a]['file']
                        );
                    }
                    $traces[] = $function . " - " . $param['traces'][$a]['file'] . ", line: " . $param['traces'][$a]['line'];
                }
            }
        }
        return ['traces' => $traces, 'msg' => $param['msg'], 'args' => $arguments];
    }

    /**
     * @param int $count
     * @param string $file
     * @return array
     * @throws Exceptions
     */
    private function arguments(int $count, string $file): array
    {
        $arq = new File($file);
        $file = $arq->read();
        for ($a = ($count - 5); $a < $count + 5; $a++) {
            if (isset($file['content'][$a])) {
                $line[$a] = $file['content'][$a];
            }
        }
        return ['line' => $line, 'identify' => $count - 1];
    }
}
<?php

namespace Restfull\Htmltopdf;

use Restfull\Error\Exceptions;
use Restfull\Filesystem\Folder;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Class HtmlToPdf
 * @package Restfull\Htmltopdf
 */
class HtmlToPdf
{

    /**
     * @var Html2Pdf
     */
    private $geration;

    /**
     * @var string
     */
    private $html = '';

    /**
     * @var array
     */
    private $config = [];

    /**
     * HtmlToPdf constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        if ($config['active']) {
            if (!isset($config['orientation'])) {
                $config['orientation'] = 'P';
            }
            if (!isset($config['format'])) {
                $config['format'] = 'A4';
            }
            if (!isset($config['linguage'])) {
                $config['language'] = 'pt';
            }
            if (!isset($config['unicode'])) {
                $config['unicode'] = true;
            }
            if (!isset($config['encoding'])) {
                $config['encoding'] = 'UTF-8';
            }
            if (!isset($config['margins'])) {
                $config['margins'] = ['5', '5', '5', '5'];
            } else {
                if (count($config['margins']) < 4) {
                    if (count($config['margins']) == 0) {
                        $config['margins'] = ['5', '5', '5', '5'];
                    } else {
                        $config['margins'] = $this->margins($config['margins']);
                    }
                }
            }
            if (!isset($config['pdfa'])) {
                $config['pdfa'] = false;
            }
            $this->geration = new Html2Pdf(
                    $config['orientation'], $config['format'], $config['language'],
                    $config['unicode'], $config['encoding'], $config['margins'], $config['pdfa']
            );
        }
        $this->config = $config;
        return $this;
    }

    /**
     * @param array $margins
     * @return array
     */
    public function margins(array $margins): array
    {
        switch (count($margins)) {
            case "1":
                $top = array_shift($margins);
                $margins = [$top, $top, $top, $top];
                break;
            case "2":
                $top = array_shift($margins);
                $button = array_shift($margins);
                $margins = [$top, $button, $top, $button];
                break;
            case "3":
                $top = array_shift($margins);
                $left = array_shift($margins);
                $button = array_shift($margins);
                $margins = [$top, $left, $button, $left];
        }
        return $margins;
    }

    /**
     * @return Array
     */
    public function geration(): array
    {
        if ($this->geration instanceof Html2Pdf) {
            return $this->config;
        }
        return '';
    }

    /**
     * @return HtmlToPdf
     */
    public function destroy(): HtmlToPdf
    {
        $this->geration = null;
        return $this;
    }

    /**
     * @param string $html
     * @param bool|null $display
     * @param bool|null $protection
     * @return HtmlToPdf
     */
    public function validateHTML(string $html, bool $display = null, bool $protection = null): HtmlToPdf
    {
        $this->html = $html;
        if (isset($display)) {
            $this->geration->pdf->SetDisplayMode($display);
        }
        $this->geration->setDefaultFont('Arial');
        if (isset($protection)) {
            $this->geration->pdf->SetProtection($protection);
        }
        return $this;
    }

    /**
     * @param array $pdf
     * @param array|null $index
     * @throws Exceptions
     */
    public function gerarPDF(array $pdf, array $index = null): void
    {
        try {
            $this->geration->writeHTML($this->html);
            if (isset($index)) {
                $this->geration->createIndex(
                        $index['title'],
                        $index['sizeTitle'],
                        $index['sizeBoolmark'],
                        $index['boolmarkTitle'],
                        $index['displayPage'],
                        $index['onPage']
                );
            }
            $folder = new Folder(substr($pdf, 0, strripos($pdf, DS)));
            if (!$folder->exists()) {
                $folder->create();
            }
            if (count($pdf) > 1) {
                $this->geration->output($pdf[0], $pdf[1]);
            } else {
                $this->geration->output($pdf[0]);
            }
        } catch (Html2PdfException $e) {
            $this->geration->clean();
            throw new Exceptions($e->getMessage());
        }
    }

}

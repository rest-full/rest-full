<?php

declare(strict_types=1);

namespace Restfull\Htmltopdf;

use Dompdf\Dompdf;
use Mpdf\Mpdf;
use Restfull\Error\Exceptions;
use Restfull\Filesystem\File;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;

/**
 *
 */
class HtmlToPdf
{

    /**
     * @var object
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
     * @param array $config
     */
    public function __construct(array $config, string $pdf)
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
                    if (count($config['margins']) === 0) {
                        $config['margins'] = ['5', '5', '5', '5'];
                    } else {
                        $config['margins'] = $this->margins($config['margins']);
                    }
                }
            }
            if (!isset($config['pdfa'])) {
                $config['pdfa'] = false;
            }
            if ($pdf === 'Mpdf') {
                $this->geration = new Mpdf($config);
            } elseif ($pdf === 'Dompdf') {
                $this->geration = new Dompdf($config);
            } else {
                $this->geration = new Html2Pdf(
                    $config['orientation'],
                    $config['format'],
                    $config['language'],
                    $config['unicode'],
                    $config['encoding'],
                    $config['margins'],
                    $config['pdfa']
                );
            }
        }
        $this->config = $config;
        return $this;
    }

    /**
     * @return array
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
     *
     * @return HtmlToPdf
     */
    public function validateHTML(string $html, bool $display = null, bool $protection = null): HtmlToPdf
    {
        $this->html = $html;
        if (isset($display)) {
            $this->geration->pdf->SetDisplayMode($display);
        }
        if (!($this->geration instanceof Dompdf)) {
            $this->geration->setDefaultFont('Arial');
        }
        if (isset($protection)) {
            $this->geration->pdf->SetProtection($protection);
        }
        return $this;
    }

    /**
     * @param array $pdf
     * @param array|null $index
     *
     * @throws Exceptions
     */
    public function gerarPDF(array $pdf, array $index = null): void
    {
        try {
            $instaceof = $this->geration instanceof Dompdf;
            $html = !$instaceof ? 'writeHTML' : 'loadHtml';
            $this->geration->{$html}($this->html);
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
            $file = new File($pdf['arq']);
            $modo = 'I';
            if (isset($pdf['modo'])) {
                $modo = $pdf['modo'];
            }
            unset($pdf);
            if (!$file->folder()->exists()) {
                $file->folder()->create();
            }
            if ($instaceof) {
                $this->geration->render();
                $this->geration->stream($file->pathinfo(), $modo);
            } else {
                $this->geration->output($file->pathinfo(), $modo);
            }
        } catch (Html2PdfException $e) {
            $this->geration->clean();
            throw new Exceptions($e->getMessage());
        }
    }

}

<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    public function code128DataUri(string $value): string
    {
        $png = (new BarcodeGeneratorPNG)->getBarcode($value, BarcodeGeneratorPNG::TYPE_CODE_128, 2, 48);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function qrDataUri(string $value): string
    {
        $result = (new Builder(
            writer: new PngWriter,
            data: $value,
            size: 140,
            margin: 0,
        ))->build();

        return $result->getDataUri();
    }
}

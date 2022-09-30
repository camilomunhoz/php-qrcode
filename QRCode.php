<?php
namespace app\Lib\Classes\QRCode;

use Exception;

/**
 * Essa classe gera um QRCode com a API do Google.
 * @docs https://developers.google.com/chart/infographics/docs/qr_codes
 * 
 * Além disso, permite adicionar imagem no centro e possui meio para armazenar o QR Code gerado.
 * 
 * @author Camilo
 */
class QRCode
{
    const API_URL = "https://chart.googleapis.com/chart?cht=qr&chs=%sx%s&chld=H&chl=%s";
    protected static $link;
    protected static $qrcode;
    protected static $dimension;
    
    public function __construct($text = null, $dimension = null)
    {
        self::boot($text, $dimension);
    }

    private static function boot($text, $dimension)
    {
        self::$dimension = self::$dimension ?? $dimension;
        if ($text) {
            self::$link = sprintf(self::API_URL, $dimension, $dimension, urlencode($text));
        }
    }

    /**
     * Traz QR Code gerado pela API em formato PNG.
     */
    public static function generate($text = null, $dimension = null)
    {
        if (!self::$link) {
            self::boot($text, $dimension);
        }
        header('Content-type: image/png');
        self::$qrcode = imagecreatefrompng(self::$link);
        return new self;
    }

    /**
     * Substitui as cores preto e branco pelas informadas.
     * @param array $colors Example:
     *   [  
     *       'black' => [34, 78, 99],
     *       'white' => [100, 77, 190],
     *   ]
     */
    public function colorize(array $colors)
    {
        if (!self::$qrcode) {
            throw new Exception('No QR Code defined yet. Should call `generate()` method before.');
        }
        imagetruecolortopalette(self::$qrcode, true, 255);
        
        if (isset($colors['black'])) {
            if (count($colors['black']) !== 3) {
                throw new Exception('Expects the 3 channels of RGB. '.count($colors).' was passed.');
            }
            $index000 = imagecolorclosest(self::$qrcode, 0, 0, 0);
            imagecolorset(self::$qrcode, $index000, ...$colors['black']);
        }
        if (isset($colors['white'])) {
            if (count($colors['white']) !== 3) {
                throw new Exception('Expects the 3 channels of RGB. '.count($colors).' was passed.');
            }
            $indexFFF = imagecolorclosest(self::$qrcode, 255, 255, 255);
            imagecolorset(self::$qrcode, $indexFFF, ...$colors['white']);
        }
        return new self;
    }

    /**
     * Adiciona a imagem no centro do QR Code.
     * @param $filepath Caminho da imagem como se estivesse no diretório public
     *                  Ex: "./images/logo.png"
     */
    public function overlayImage($filepath)
    {
        if (!self::$qrcode) {
            throw new Exception('No QR Code defined yet. Should call `generate()` method before.');
        }
        $image = self::imageFromJpegPng($filepath);
        $width  = imagesx($image);
        $height = imagesy($image);

        $new_width = self::$dimension/3;
        $scale = $width/$new_width;
        $new_height = $height/$scale;

        imagecopyresampled(
            self::$qrcode, // Destination image resource.
            $image, // Source image resource.
            self::$dimension/3, // x-coordinate of destination point.
            self::$dimension/3, // y-coordinate of destination point.
            0, // x-coordinate of source point.
            0, // y-coordinate of source point.
            $new_width, // Destination width.
            $new_height, // Destination height.
            $width, // Source width.
            $height, // Source height.
        );
        return new self;
    }

    /**
     * @param $path Caminho de destino
     */
    public function save($path)
    {
        if (!self::$qrcode) {
            throw new Exception('No QR Code to be saved.');
        }
        imagepng(self::$qrcode, $path);
        imagedestroy(self::$qrcode);
    }

    /**
     * Mata a execução e exibe o QR Code.
     */
    public function show()
    {
        imagepng(self::$qrcode);
        die;
    }

    /**
     * Dinamicamente cria uma GD Image em formato PNG ou JPEG.
     */
    private function imageFromJpegPng($filepath)
    {
        $mime = getImageSize($filepath)['mime'];
        switch($mime) {
            case  'image/png': $image = imagecreatefrompng($filepath); break;
            case 'image/jpeg': $image = imagecreatefromjpeg($filepath); break;
            default: throw new Exception("$mime MIME type is not supported. Must be image/png or image/jpeg.");
        }
        return $image;
    }
}

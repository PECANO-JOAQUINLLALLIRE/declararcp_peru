<?php
namespace SunatDeclaracion\Sunat;

use Greenter\XMLSecLibs\Sunat\SignedXml;
use Exception;
require_once('vendor/autoload.php');

class FirmarComprobante{
    public $numeroDocumento;
    private $pem = null;

    function  __construct($numeroDocumento){
        $this->numeroDocumento = pathinfo($numeroDocumento)['filename'];
    }

    public function setCertificadoFromFile($pathCertificado = NULL, $pwd = NULL){
        if(is_null($pathCertificado) || empty($pathCertificado)) throw new Exception ("La ruta del certificado es obligatorio");
        if(is_null($pwd) || empty($pwd)) throw new Exception("La contraseña del certificado digital es obligatorio");
        if(!file_exists($pathCertificado)) throw new Exception("El certifidicado digital no se encuentra. Ruta: {$pathCertificado}");

        $this->obtenerPem(file_get_contents($pathCertificado), $pwd);
    }

    public function setCertificadoFromContent($contentCertificado = NULL, $pwd = NULL){
        if(is_null($contentCertificado) || empty($contentCertificado)) throw new Exception ("El contenido del certificado es obligatorio");
        if(is_null($pwd) || empty($pwd)) throw new Exception("La contraseña del certificado digital es obligatorio");

        $this->obtenerPem($contentCertificado, $pwd);
    }

    function obtenerPem($contenido, $pwd){
        $res = [];
        $openSSL = openssl_pkcs12_read($contenido, $res, $pwd);
        if(!$openSSL) {
            throw new Exception("Error: ".openssl_error_string());
        }
        $this->pem = $res["cert"] . PHP_EOL . $res["pkey"];
    }

    public function firmarComprobante($rutaXml = NULL, $contentXml = ""){
        $tieneArchivo = false;

        if(is_null($this->pem)) throw new Exception("Es necesario configurar el certificado digital");
        if(!is_null($rutaXml) && !empty($rutaXml)){
            if(!file_exists($rutaXml)) throw new Exception("Archivo XML no encontrado");
            $tieneArchivo = true;
        } else if(!is_null($contentXml) && !empty($contentXml)){
            $tieneArchivo = false;
        } else throw new Exception("Archivo o Contenido XML no puede estar vacío");

        $signer = new SignedXml();
        $signer->setCertificate($this->pem);

        if($tieneArchivo) $xmlSigned = $signer->signFromFile($rutaXml);
        else $xmlSigned = $signer->signXml($contentXml);

        file_put_contents(str_replace("-sinfirma", "", $this->numeroDocumento) . ".xml", $xmlSigned);
    }
}

?>
<?php 
namespace SunatDeclaracion\Sunat;

interface ISendBill{
    public function declararComprobante($tipoDoc, $numeroDocumento, $pathXml = NULL, $contentXml = NULL);
    public function obtenerCdr($tipoDoc, $serie, $correlativo);
}
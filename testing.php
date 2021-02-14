<?php
spl_autoload_register(function($class) {
    $class = str_replace('\\', '/', $class);
    require_once('./' . $class . '.php');
    // require_once($_SERVER['DOCUMENT_ROOT'] . "/concepto/" . $class . '.php');

});

use SunatDeclaracion\Sunat\DeclaracionSunat;
use SunatDeclaracion\Entidad;
use SunatDeclaracion\Entidad\EmpresaFE;
use SunatDeclaracion\Sunat\FirmarComprobante;

try{
    $rutaBaseApp = $_SERVER['DOCUMENT_ROOT'] . "/concepto";

    // $obj = new FirmarComprobante("0001-0221-sinfirma");
    // $rutaCertificado = $rutaBaseApp . "/files/certificadoDigital/LLAMA-PE-CERTIFICADO-DEMO-10432898275.pfx";
    // $rutaXml = $rutaBaseApp . "/xml/0001-2405-sinfirma.xml";
    // $obj->setCertificadoFromContent(file_get_contents($rutaCertificado), "10432898275");
    // $obj->setCertificadoFromFile($rutaCertificado, "10432898275");
    // $obj->firmarComprobante(NULL, file_get_contents($rutaXml));

    // $numeroDocumento = "20103818860-01-FE01-00000048-sinfirma";
    // $obj = new FirmarComprobante($numeroDocumento);
    // $rutaCertificado = $rutaBaseApp . "/files/certificadoDigital/20103818860.pfx";
    // $rutaXml = $rutaBaseApp . "/xml/{$numeroDocumento}.xml";
    // $obj->setCertificadoFromContent(file_get_contents($rutaCertificado), "VilussaC372");
    // $obj->setCertificadoFromFile($rutaCertificado, "VilussaC372");
    // $obj->firmarComprobante(NULL, file_get_contents($rutaXml));
    // $rpta = "OK";
    
    // Beta - Vilussa
    // $obj = new DeclaracionSunat("20103818860", "MODDATOS", "MODDATOS", EmpresaFE::Sunat, FALSE);
    // $rpta = $obj->declararComprobante("01","FE01-00000047", NULL, file_get_contents(str_replace("-sinfirma","", "./{$numeroDocumento}.xml")));

    // Producción - Vilussa
    // $obj = new DeclaracionSunat("20103818860", "FPALACIO", "H67w45h4w5", EmpresaFE::Sunat, TRUE);
    // $rpta = $obj->declararComprobante("01","FE01-00000047", NULL, file_get_contents(str_replace("-sinfirma","", "./{$numeroDocumento}.xml")));

    // $obj = new DeclaracionSunat("20103818860", "vilssa01", "#vilssa01#", EmpresaFE::NubeFact, TRUE);
    // $rpta = $obj->declararComprobante("01","FE01-00000048", NULL, file_get_contents(str_replace("-sinfirma","", "./{$numeroDocumento}.xml")));


    /* Obtener el CDR */

    // KIO
    // $obj = new DeclaracionSunat("20512090878", "epngcibp", "03epngcibp", EmpresaFE::NubeFact, TRUE);
    // $rpta = $obj->obtenerCdr("01", "FB01", "00040443");

    // Pecano
    // $obj = new DeclaracionSunat("20600700228", "BILLPKN0", "pkNO5916$#", EmpresaFE::Sunat, TRUE);
    // $rpta = $obj->obtenerCdr("01", "FP01", "00005321");

    // Vilussa
    // $obj = new DeclaracionSunat("20103818860", "vilssa01", "#vilssa01#", EmpresaFE::NubeFact, TRUE);
    // $rpta = $obj->obtenerCdr("01", "FE01", "00000047");
    
    var_dump($rpta);
} catch(Exception $ex){
    echo "Error " . $ex->getCode() . " - Detalles: " . $ex->getMessage();
}







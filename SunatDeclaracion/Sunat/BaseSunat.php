<?php
namespace SunatDeclaracion\Sunat;

use DOMDocument;
use Exception;
use ZipArchive;
use SunatDeclaracion\Entidad\RespuestaSunat;
use SunatDeclaracion\Entidad\ConfiguracionSunat;
use SunatDeclaracion\Entidad\EmpresaFE;
use SunatDeclaracion\Entidad\TipoRespuestaSunat;
use SunatDeclaracion\Entidad\TipoSolicitud;

class BaseSunat {
    protected $declaracionSunat = NULL;
    protected $soapClient = NULL;
    private $esProduccion = FALSE;
    private $endPoint = NULL;
    private $empresaFE = 10;

    private $rutaBase = NULL;
    protected $pathXml = NULL;
    protected $contentXml = NULL;

    public function __construct($rucContribuyente, $usuarioWS, $passwordWS, $empresaFE = 10, $esProduccion = true){
        $this->declaracionSunat = new ConfiguracionSunat($rucContribuyente, $usuarioWS, $passwordWS);
        $this->esProduccion = $esProduccion;
        $this->empresaFE = $empresaFE;
        $this->rutaBase = __DIR__ . '../..';
    }

    public function obtenerRutaWsdl($tipoSolicitud = 0){
        switch($tipoSolicitud){
            case TipoSolicitud::SendBill:
                return $this->rutaBase .'/wsdl/billService.wsdl';
            case TipoSolicitud::GetStatusCdr:
                return $this->rutaBase .'/wsdl/billConsultService.wsdl';
            default : 
                throw new Exception("El tipo enviado no tiene un WSDL configurado");
        }
    }

    public function obtenerEndPoint($tipoSolicitud = 0){
        if($tipoSolicitud == TipoSolicitud::SendBill){
            switch($this->declaracionSunat->tipoDocumento){
                case "01":
                case "03":
                case "07":
                case "08":
                    $this->endPoint = $this->endPointEmpresaFE($tipoSolicitud);
                    return $this->endPoint;
                case "09":
                    $this->endPoint = $this->endPointEmpresaFE($tipoSolicitud);
                    return $this->endPoint;
                default:
                    throw new Exception("No se pudo obtener el EndPoint para declarar");
                    break;
            }
        }
        else if ($tipoSolicitud == TipoSolicitud::GetStatusCdr){
            $this->endPoint = $this->endPointEmpresaFE($tipoSolicitud);
            return $this->endPoint;
        }
        throw new Exception("No se puede obtener el EndPoint para declarar el comprobante");  
    }
    
    protected function obtenerNombreArchivo($tipo = 0){
        switch($tipo){
            case 1:
                return $this->declaracionSunat->rucContribuyente."-".$this->declaracionSunat->tipoDocumento."-".$this->declaracionSunat->numeroDocumento.".zip";
            case 2:
                return $this->declaracionSunat->rucContribuyente."-".$this->declaracionSunat->tipoDocumento."-".$this->declaracionSunat->numeroDocumento.".xml";
            case 3:
                return "R-" . $this->declaracionSunat->rucContribuyente."-".$this->declaracionSunat->tipoDocumento."-".$this->declaracionSunat->numeroDocumento.".xml";
            default:
                return "";
        }
    }

    private function obtenerTemplateSolicitud($tipoSolicitud){
        switch($tipoSolicitud){
            case TipoSolicitud::SendBill:
                return <<<EOF
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
    <soapenv:Header>
        <wsse:Security>
            <wsse:UsernameToken>
                <wsse:Username>{USUARIO_WS}</wsse:Username>
                <wsse:Password>{PASSWORD_WS}</wsse:Password>
            </wsse:UsernameToken>
        </wsse:Security>
    </soapenv:Header>
    <soapenv:Body>
        <ser:sendBill>
            <fileName>{NOMBRE_ARCHIVO}</fileName>
            <contentFile>{BUFFER_COMPRIMIDO_ARCHIVO}</contentFile>
        </ser:sendBill>
    </soapenv:Body>
</soapenv:Envelope>
EOF;
            break;
            case TipoSolicitud::GetStatusCdr:
                return <<<EOF
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<SOAP-ENV:Header xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope">
		<wsse:Security>
			<wsse:UsernameToken>
				<wsse:Username>{USUARIO_WS}</wsse:Username>
				<wsse:Password>{PASSWORD_WS}</wsse:Password>
			</wsse:UsernameToken>
		</wsse:Security>
	</SOAP-ENV:Header>
	<SOAP-ENV:Body>
		<m:getStatusCdr xmlns:m="http://service.sunat.gob.pe">
			<rucComprobante>{RUC_CONTRIBUYENTE}</rucComprobante>
			<tipoComprobante>{TIPO_DOC}</tipoComprobante>
			<serieComprobante>{SERIE}</serieComprobante>
			<numeroComprobante>{CORRELATIVO}</numeroComprobante>
		</m:getStatusCdr>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
EOF;
        }
    }

    protected function armarSolicitud($tipoSolicitud, $data = []){
        switch($tipoSolicitud){
            case TipoSolicitud::SendBill:
                return str_replace(
                    [
                        "{USUARIO_WS}", 
                        "{PASSWORD_WS}", 
                        "{NOMBRE_ARCHIVO}", 
                        "{BUFFER_COMPRIMIDO_ARCHIVO}"
                    ], 
                    [
                        $this->declaracionSunat->rucContribuyente.$this->declaracionSunat->usuarioWS,
                        $this->declaracionSunat->passwordWS,
                        $this->obtenerNombreArchivo(1),
                        $data['contentZipBase64']
                    ],
                    $this->obtenerTemplateSolicitud($tipoSolicitud));
                break;
            case TipoSolicitud::GetStatusCdr:
                return str_replace(
                    [
                        "{USUARIO_WS}", 
                        "{PASSWORD_WS}", 
                        "{RUC_CONTRIBUYENTE}", 
                        "{TIPO_DOC}",
                        "{SERIE}",
                        "{CORRELATIVO}"
                    ], 
                    [
                        $this->declaracionSunat->rucContribuyente.$this->declaracionSunat->usuarioWS,
                        $this->declaracionSunat->passwordWS,
                        $this->declaracionSunat->rucContribuyente,
                        $this->declaracionSunat->tipoDocumento,
                        $data['serie'],
                        $data['correlativo']
                    ],
                    $this->obtenerTemplateSolicitud($tipoSolicitud));
                break;
        }
    }

    protected function comprimirArchivo(){
        $tieneArchivo = false;

        if(!is_null($this->pathXml) && !empty($this->pathXml)){
            if(!file_exists($this->pathXml)) throw new Exception("Archivo XML no encontrado");
            $tieneArchivo = true;
        } else if(!is_null($this->contentXml) && !empty($this->contentXml)){
            $tieneArchivo = false;
        } else throw new Exception("Archivo o Contenido XML no puede estar vacío");

        $nombreComprimido = uniqid(16) . ".zip";
        $zipArchive = new ZipArchive();
        if($zipArchive->open($nombreComprimido, ZipArchive::CREATE) !== FALSE){
            if($tieneArchivo) $zipArchive->addFile($this->pathXml, $this->obtenerNombreArchivo(2));
            else $zipArchive->addFromString($this->obtenerNombreArchivo(2), $this->contentXml);
            // $zipArchive->addEmptyDir("dummy");
            $zipArchive->close();
        }

        $bufferZip = file_get_contents($nombreComprimido);
        @unlink($nombreComprimido);
        return base64_encode($bufferZip);
    }

    protected function descomprimirArchivo($contentResponse = null){
        $nomZipResponse = $this->declaracionSunat->numeroDocumento . "-" . uniqid(12) . ".zip";
        if(file_put_contents($nomZipResponse, $contentResponse) ===false ) throw new Exception ("No se pudo grabar el Zip del CDR");

        $zipArchive = new ZipArchive();
        if($zipArchive->open($nomZipResponse) !== false){
            $zipArchive->extractTo($this->rutaBase . "/");
            $zipArchive->close();
        }
        @unlink($nomZipResponse);

        $cdrDocumento = $this->rutaBase. "/" . $this->obtenerNombreArchivo(3);
        if(!file_exists($cdrDocumento)) throw new Exception("No se ha podido ubicar el CDR del archivo {$this->declaracionSunat->numeroDocumento}");
        
        $contenido = file_get_contents($cdrDocumento);
        @unlink($cdrDocumento);

        return $contenido;
    }

    protected function deserealizarResponse($response = null){
        $xmlResponse = new DOMDocument();
        $xmlResponse->loadXML($response);

        $nodos = $xmlResponse->getElementsByTagName("Fault");
        $error = [];
        if($nodos->length > 0){
            foreach($nodos as $nodo){
                $nodosHijos = $nodo->childNodes;
                if($nodosHijos->length > 0){
                    foreach($nodosHijos as $hijo){
                        $error[$hijo->localName] = $hijo->textContent;
                    }
                }
            }
            if(count($error)) {
                $rpta = new RespuestaSunat();
                $rpta->esProduccion = ($this->esProduccion) ? "PRODUCCION" : "BETA";
                $rpta->endPointEmpresaFE = EmpresaFE::getNombreEmpresa($this->empresaFE) ." EndPoint: ". $this->endPoint;
                $rpta->tipoRespuestaSunat = TipoRespuestaSunat::Excepcion;
                $rpta->codigoCdr = -1;
                $rpta->mensajeCdr = "Error|" . implode(" ", $error);
                $rpta->xmlResponseSunat = $response;
                return $rpta;
            } 
        }
        $nodos = null;
        $nodos = $xmlResponse->getElementsByTagName("applicationResponse");
        if($nodos->length > 0){
            foreach($nodos as $nodo){
                $contenidoCdr = $this->descomprimirArchivo(base64_decode($nodo->textContent));
                return $this->analizarCdr($contenidoCdr);
            }            
        }
        $nodos = null;
        $nodos = $xmlResponse->getElementsByTagName("statusCdr");
        if($nodos->length > 0){
            $nodo = $nodos->item(0)->getElementsByTagName("content")->item(0);
            if($nodo != null){
                $contenidoCdr = $this->descomprimirArchivo(base64_decode($nodo->textContent));
                return $this->analizarCdr($contenidoCdr);
            }
        }
    }

    protected function analizarCdr($contenidoCdr){
        $rpta = new RespuestaSunat();
        $rpta->esProduccion = ($this->esProduccion) ? "PRODUCCION" : "BETA";
        $rpta->endPointEmpresaFE = EmpresaFE::getNombreEmpresa($this->empresaFE) ." EndPoint: ". $this->endPoint;

        $rpta->xmlResponseSunat = $contenidoCdr;

        $cdr = new DOMDocument();
        $cdr->loadXML($contenidoCdr);

        $seccionResponse = $cdr->getElementsByTagName("Response")->item(0);

        $nodo = $seccionResponse->getElementsByTagName("ResponseCode")->item(0);
        if($nodo){
            $rpta->codigoCdr = (int)$nodo->nodeValue;
            $rpta->tipoRespuestaSunat = ($nodo->nodeValue == 0) ? TipoRespuestaSunat::Aceptado : TipoRespuestaSunat::Rechazado; 
        }

        $nodo = $seccionResponse->getElementsByTagName("Description")->item(0);
        if($nodo) $rpta->mensajeCdr = $nodo->nodeValue;

        return $rpta;
    }

    private function endPointEmpresaFE($tipoSolicitud = 0){
        switch ($tipoSolicitud){
            case TipoSolicitud::SendBill:
                if($this->esProduccion){
                    switch($this->empresaFE){
                        case EmpresaFE::Sunat:
                            return "https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService";
                        case EmpresaFE::NubeFact:
                            return "https://ose.nubefact.com/ol-ti-itcpe/billService";
                    }
                }
                else{
                    switch($this->empresaFE){
                        case EmpresaFE::Sunat:
                            return "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService";
                        case EmpresaFE::NubeFact:
                            return "https://demo-ose.nubefact.com/ol-ti-itcpe/billService";
                    }
                }
                break;                
            case TipoSolicitud::GetStatusCdr:
                if($this->esProduccion){
                    switch($this->empresaFE){
                        case EmpresaFE::Sunat:
                            return "https://ww1.sunat.gob.pe/ol-it-wsconscpegem/billConsultService";
                        case EmpresaFE::NubeFact:
                            return "https://ose.nubefact.com/ol-ti-itcpe/billService";
                    }
                }
                else{
                    switch($this->empresaFE){
                        case EmpresaFE::Sunat:
                            return "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService";
                        case EmpresaFE::NubeFact:
                            return "https://demo-ose.nubefact.com/ol-ti-itcpe/billService";
                    }
                }
                break;
        }
    }

}

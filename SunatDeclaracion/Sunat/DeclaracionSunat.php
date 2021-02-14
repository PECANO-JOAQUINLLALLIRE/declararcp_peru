<?php 
namespace SunatDeclaracion\Sunat;

use SoapClient;
use Exception;
use SoapFault;
use SunatDeclaracion\Entidad\TipoSolicitud;

class DeclaracionSunat extends BaseSunat implements ISendBill {    

    public function __construct($rucContribuyente, $usuarioWS, $passwordWS, $empresaFE = 10, $esProduccion = true){
        parent::__construct($rucContribuyente, $usuarioWS, $passwordWS, $empresaFE, $esProduccion);
    }

    public function declararComprobante($tipoDoc, $numeroDocumento, $pathXml = NULL, $contentXml = NULL) {
        $this->declaracionSunat->tipoDocumento = $tipoDoc;
        $this->declaracionSunat->numeroDocumento = pathinfo($numeroDocumento)['filename'];
        $this->pathXml = $pathXml;
        $this->contentXml = $contentXml;
        $parameters=[
			'stream_context' => stream_context_create([
				'ssl' => [
					// 'ciphers'=>'AES256-SHA',
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				],
			]),
        ];
                
        try{
            $this->soapClient = new SoapClient($this->obtenerRutaWsdl(TipoSolicitud::SendBill), $parameters);

            $request = $this->armarSolicitud(TipoSolicitud::SendBill, ['contentZipBase64' => $this->comprimirArchivo()]);
            $response = $this->soapClient->__doRequest($request, $this->obtenerEndPoint(TipoSolicitud::SendBill), "sendBill", SOAP_SSL_METHOD_SSLv23);
            if(isset($this->soapClient->__soap_fault) && ($this->soapClient->__soap_fault instanceof SoapFault) ) throw $this->soapClient->__soap_fault;
            
            return $this->deserealizarResponse($response);
        }
        catch(SoapFault $ex){
            echo "Error " . $ex->getCode() . " Codigo: " . $ex->faultcode . " Detalles: " . $ex->faultstring;
        }
        catch(Exception $ex){
            echo "Error " . $ex->getCode() . " Detalles: " . $ex->getMessage();
        }
    }

    public function obtenerCdr($tipoDoc, $serie, $correlativo){
        $this->declaracionSunat->tipoDocumento = $tipoDoc;
        $this->declaracionSunat->serie = $serie;
        $this->declaracionSunat->correlativo = $correlativo;
        $this->declaracionSunat->numeroDocumento = $serie."-".$correlativo;
        $parameters=[
			'stream_context' => stream_context_create([
				'ssl' => [
					// 'ciphers'=>'AES256-SHA',
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				],
			]),
        ];
        
        try{
            $this->soapClient = new SoapClient($this->obtenerRutaWsdl(TipoSolicitud::SendBill), $parameters);

            $request = $this->armarSolicitud(TipoSolicitud::GetStatusCdr, ['serie' => $serie, 'correlativo' => $correlativo]);
            $response = $this->soapClient->__doRequest($request, $this->obtenerEndPoint(TipoSolicitud::GetStatusCdr), "getStatusCdr", SOAP_SSL_METHOD_SSLv23);
            
            if(isset($this->soapClient->__soap_fault) && ($this->soapClient->__soap_fault instanceof SoapFault) ) throw $this->soapClient->__soap_fault;
            
            return $this->deserealizarResponse($response);
        }
        catch(SoapFault $ex){
            echo "Error " . $ex->getCode() . " Codigo: " . $ex->faultcode . " Detalles: " . $ex->faultstring;
        }
        catch(Exception $ex){
            echo "Error " . $ex->getCode() . " Detalles: " . $ex->getMessage();
        }
    }
}


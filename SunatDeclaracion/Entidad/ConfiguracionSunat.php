<?php 
namespace SunatDeclaracion\Entidad;

class ConfiguracionSunat{
    public $rucContribuyente;
    public $tipoDocumento;
    public $numeroDocumento;
    public $usuarioWS;
    public $passwordWS;
    public $serie;
    public $correlativo;

    public function __construct($rucContribuyente, $usuarioWS, $passwordWS){
        $this->rucContribuyente = $rucContribuyente;
        $this->usuarioWS = $usuarioWS;
        $this->passwordWS = $passwordWS;
    }
}
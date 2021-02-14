<?php
namespace SunatDeclaracion\Entidad;

abstract class EmpresaFE{
    const Sunat = 10;
    const NubeFact= 30;

    public static function getNombreEmpresa($empresaFE = 0){
        switch ($empresaFE) {
            case strval(self::Sunat):
                return "SUNAT";
            case strval(self::NubeFact):
                return "NUBEFACT";
            default:
                return "NO EXISTE EMPRESA";
        }
    }
}
<?php

if (! function_exists('eventName')) {
    function eventName(String $evento): String
    {
        $even = "";
        switch ($evento) {
            case 'created':
                $even = "creado";
                break;
            case 'updated':
                $even = "actualizado";
                break;
            case 'deleted':
                $even = "borrado";
                break;
            case 'restored':
                $even = "restaurado";
                break;
            default:
                # code...
                break;
        }
        return $even;
    }
}

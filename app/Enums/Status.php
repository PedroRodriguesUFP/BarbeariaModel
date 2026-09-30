<?php

namespace App\Enums;

enum Status: string
{   
    case Pendente = 'pendente';
    case Confirmado = 'confirmado';
    case Cancelado = 'cancelado';
    case Concluido = 'concluido';
}
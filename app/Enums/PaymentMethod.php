<?php

namespace App\Enums;

/**
 * Formas de pagamento aceites.
 * Os valores são os mesmos textos que já estão guardados na base de dados.
 */
enum PaymentMethod: string
{
    case Cash     = 'cash';
    case Card     = 'card';
    case Mbway    = 'mbway';
    case Transfer = 'transfer';
}

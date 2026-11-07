<?php

namespace App\Enums;

/**
 * Estados possíveis de um pagamento.
 * Os valores são os mesmos textos que já estão guardados na base de dados.
 */
enum PaymentStatus: string
{
    case Pending  = 'pending';
    case Paid     = 'paid';
    case Failed   = 'failed';
    case Refunded = 'refunded';
}

<?php

namespace App\Models;

use ORM;

/**
 * Class Name: TicketPriority
 *
 * ORM Klasse für das TicketPriority Objekt.
 *
 * @package App\Models
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class TicketPriority
{
    public static function find($id)
    {
        return ORM::for_table('ticket_priorities')->find_one($id);
    }

    public static function all()
    {
        return ORM::for_table('ticket_priorities')->find_many();
    }

    public static function createPriority($label, $sort_order)
    {
        return ORM::for_table('ticket_priorities')->create()
            ->set('label', $label)
            ->set('sort_order', $sort_order)
            ->save();
    }
}

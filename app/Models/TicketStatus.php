<?php

namespace App\Models;

use ORM;

/**
 * Class Name: TicketStatus
 *
 * ORM Klasse für das TicketStatus Objekt.
 *
 * @package App\Models
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class TicketStatus
{
    public static function find($id)
    {
        return ORM::for_table('ticket_statuses')->find_one($id);
    }

    public static function all()
    {
        return ORM::for_table('ticket_statuses')->find_many();
    }

    public static function createStatus($label, $sort_order)
    {
        return ORM::for_table('ticket_statuses')->create()
            ->set('label', $label)
            ->set('sort_order', $sort_order)
            ->save();
    }
}

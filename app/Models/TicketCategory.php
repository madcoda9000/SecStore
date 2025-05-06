<?php

namespace App\Models;

use ORM;

/**
 * Class Name: TicketCategory
 *
 * ORM Klasse für das Ticket Category Objekt.
 *
 * @package App\Models
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class TicketCategory
{
    public static function find($id)
    {
        return ORM::for_table('ticket_categories')->find_one($id);
    }

    public static function all()
    {
        return ORM::for_table('ticket_categories')->find_many();
    }

    public static function createCategory($name)
    {
        return ORM::for_table('ticket_categories')->create()
            ->set('name', $name)
            ->save();
    }
}

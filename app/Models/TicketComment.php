<?php

namespace App\Models;

use ORM;

/**
 * Class Name: TicketComment
 *
 * ORM Klasse für das Ticket Kommentar Objekt.
 *
 * @package App\Models
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class TicketComment
{
    public static function find($id)
    {
        return ORM::for_table('ticket_comments')->find_one($id);
    }

    public static function createComment($ticket_id, $user_id, $message, $is_internal = false)
    {
        return ORM::for_table('ticket_comments')->create()
            ->set('ticket_id', $ticket_id)
            ->set('user_id', $user_id)
            ->set('message', $message)
            ->set('is_internal', $is_internal)
            ->save();
    }

    public static function allCommentsForTicket($ticket_id)
    {
        return ORM::for_table('ticket_comments')
            ->where('ticket_id', $ticket_id)
            ->order_by_desc('created_at')
            ->find_many();
    }
}

<?php

namespace App\Models;

use ORM;

/**
 * Class Name: TicketAttachment
 *
 * ORM Klasse für das TicketAttachment Objekt.
 *
 * @package App\Models
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class TicketAttachment
{
    public static function find($id)
    {
        return ORM::for_table('ticket_attachments')->find_one($id);
    }

    public static function createAttachment($ticket_id, $filename, $filepath)
    {
        return ORM::for_table('ticket_attachments')->create()
            ->set('ticket_id', $ticket_id)
            ->set('filename', $filename)
            ->set('filepath', $filepath)
            ->save();
    }

    public static function allAttachmentsForTicket($ticket_id)
    {
        return ORM::for_table('ticket_attachments')
            ->where('ticket_id', $ticket_id)
            ->find_many();
    }
}

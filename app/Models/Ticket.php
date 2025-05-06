<?php

namespace App\Models;

use ORM;

/**
 * Class Name: Ticket
 *
 * ORM Klasse für das Ticket Objekt.
 *
 * @package App\Models
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class Ticket
{
    public static function find($id)
    {
        return ORM::for_table('tickets')->find_one($id);
    }

    public static function all()
    {
        return ORM::for_table('tickets')->find_many();
    }

    // Alle Tickets mit Paging abrufen
    public static function getTicketsWithPaging($page, $perPage)
    {
        // Berechnung der 'offset' für die SQL-Abfrage
        $offset = ($page - 1) * $perPage;

        // Anzahl der Tickets insgesamt
        $totalTickets = ORM::for_table('tickets')->count();

        // Abrufen der Tickets für die aktuelle Seite
        $tickets = ORM::for_table('tickets')
            ->limit($perPage)
            ->offset($offset)
            ->find_many();

        // Gesamtseitenanzahl berechnen
        $totalPages = ceil($totalTickets / $perPage);

        return [
            'tickets' => $tickets,
            'paging' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'per_page' => $perPage,
                'total_items' => $totalTickets
            ]
        ];
    }

    // Eigene Tickets des angemeldeten Benutzers mit Paging abrufen
    public static function getUserTicketsWithPaging($userId, $page, $perPage)
    {
        // Berechnung der 'offset' für die SQL-Abfrage
        $offset = ($page - 1) * $perPage;

        // Anzahl der Tickets insgesamt des Benutzers
        $totalTickets = ORM::for_table('tickets')
            ->where('user_id', $userId)
            ->count();

        // Abrufen der Tickets des Benutzers für die aktuelle Seite
        $tickets = ORM::for_table('tickets')
            ->where('user_id', $userId)
            ->limit($perPage)
            ->offset($offset)
            ->find_many();

        // Gesamtseitenanzahl berechnen
        $totalPages = ceil($totalTickets / $perPage);

        return [
            'tickets' => $tickets,
            'paging' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'per_page' => $perPage,
                'total_items' => $totalTickets
            ]
        ];
    }

    public static function createTicket($user_id, $assigned_to, $category_id, $priority_id, $status_id, $subject, $description)
    {
        return ORM::for_table('tickets')->create()
            ->set('user_id', $user_id)
            ->set('assigned_to', $assigned_to)
            ->set('category_id', $category_id)
            ->set('priority_id', $priority_id)
            ->set('status_id', $status_id)
            ->set('subject', $subject)
            ->set('description', $description)
            ->save();
    }

    public static function updateTicket($id, $status_id, $assigned_to = null)
    {
        $ticket = ORM::for_table('tickets')->find_one($id);
        if ($ticket) {
            $ticket->status_id = $status_id;
            if ($assigned_to !== null) {
                $ticket->assigned_to = $assigned_to;
            }
            $ticket->save();
        }
    }
}

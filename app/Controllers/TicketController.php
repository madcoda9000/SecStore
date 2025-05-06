<?php

namespace App\Controllers;

use Flight;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketPriority;
use App\Models\TicketType;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\User;

/**
 * TicketController
 *
 * Diese Klasse verwaltet die Ticket-Funktionalitäten.
 */
class TicketController
{
    // Ticket erstellen
    public function createTicket()
    {
        // Daten vom POST-Request
        $user_id = $_POST['user_id'];
        $assigned_to = $_POST['assigned_to'];
        $category_id = $_POST['category_id'];
        $priority_id = $_POST['priority_id'];
        $status_id = $_POST['status_id'];
        $subject = $_POST['subject'];
        $description = $_POST['description'];

        // Ticket erstellen
        $ticket = Ticket::createTicket($user_id, $assigned_to, $category_id, $priority_id, $status_id, $subject, $description);

        // JSON Antwort zurückgeben
        Flight::json(['status' => 'success', 'ticket' => $ticket]);
    }

    // Ticket-Status aktualisieren
    public function updateTicketStatus()
    {
        // Daten vom POST-Request
        $ticket_id = $_POST['ticket_id'];
        $status_id = $_POST['status_id'];
        $assigned_to = $_POST['assigned_to'] ?? null;

        // Ticket aktualisieren
        $ticket = Ticket::updateTicket($ticket_id, $status_id, $assigned_to);

        // JSON Antwort zurückgeben
        Flight::json(['status' => 'success', 'ticket' => $ticket]);
    }

    // Alle Kommentare zu einem Ticket anzeigen
    public function showTicketComments($ticket_id)
    {
        // Alle Kommentare abrufen
        $comments = TicketComment::allCommentsForTicket($ticket_id);

        // Kommentare in JSON format zurückgeben
        Flight::json(['status' => 'success', 'comments' => $comments]);
    }

    // Kommentar zu einem Ticket hinzufügen
    public function addCommentToTicket()
    {
        // Daten vom POST-Request
        $ticket_id = $_POST['ticket_id'];
        $user_id = $_POST['user_id'];
        $message = $_POST['message'];
        $is_internal = $_POST['is_internal'] ?? false;

        // Kommentar hinzufügen
        $comment = TicketComment::createComment($ticket_id, $user_id, $message, $is_internal);

        // JSON Antwort zurückgeben
        Flight::json(['status' => 'success', 'comment' => $comment]);
    }

    // Tickets mit Paging abrufen
    public function getTicketsWithPaging()
    {
        // Abfrageparameter aus dem Request holen (falls vorhanden)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Standard auf Seite 1 setzen
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10; // Standard auf 10 Tickets pro Seite setzen

        // Tickets aus dem Model holen
        $result = Ticket::getTicketsWithPaging($page, $perPage);

        // Antwort im JSON-Format zurückgeben
        Flight::json([
            'status' => 'success',
            'data' => $result['tickets'],
            'paging' => $result['paging']
        ]);
    }

    // Eigene Tickets des Benutzers mit Paging abrufen
    public function getUserTicketsWithPaging()
    {
        // Die Benutzer-ID aus der Session holen
        $userId = $_SESSION['user_id'];  // Vorausgesetzt, dass die Benutzer-ID in der Session gespeichert wird

        // Abfrageparameter aus dem Request holen (falls vorhanden)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Standard auf Seite 1 setzen
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10; // Standard auf 10 Tickets pro Seite setzen

        // Tickets aus dem Model holen
        $result = Ticket::getUserTicketsWithPaging($userId, $page, $perPage);

        // Antwort im JSON-Format zurückgeben
        Flight::json([
            'status' => 'success',
            'data' => $result['tickets'],
            'paging' => $result['paging']
        ]);
    }
}

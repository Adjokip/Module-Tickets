<?php

require 'include/Ticket.php';
require 'include/functions.php';

function exec_ogp_module()
{
    global $db;

    if (isset($_SESSION['ticket'])) unset($_SESSION['ticket']);
    if (isset($_SESSION['ticketReply'])) unset($_SESSION['ticketReply']);

    $ticket = new Ticket($db);
    $isAdmin = $db->isAdmin($_SESSION['user_id']);

    $ticketOwner = (!$isAdmin ? $_SESSION['user_id'] : null);
    $tickets = $ticket->tickets($ticketOwner);

    echo '<h2>'.get_lang('support_tickets').'</h2>';

    echo '<table class="center" style="width:100%;"><a href="?m=Tickets&p=submitticket">'.get_lang('submit_ticket').'</a></table>';

    if ($tickets->count() > 0) {
        echo '<table class="ticketListTable center" style="width:100%;">';
        echo '<tr>';
        echo '<th>'.get_lang('ticket_subject').'</th>';
        echo '<th>'.get_lang('ticket_status').'</th>';
        echo '<th>'.get_lang('ticket_updated').'</th>';
        echo '</tr>';
        
        foreach ($tickets->list() as $t) {
            echo '<tr class="ticketRow '.ticketCodeToName($t['status'], true).'">
	    		<td><a href="?m=Tickets&p=viewticket&tid='.$t['tid'].'&uid='.$t['uid'].'">'. htmlentities($t['subject']) .'</a></td>
	    		<td>'. ticketCodeToName($t['status']) .'</a></td>
	    		<td>'. $t['last_updated'] .'</a></td>
	    	</tr>';
        }

        echo '</table>';
    } else {
        echo '<div class="no_tickets_submitted">' . get_lang('no_tickets_submitted') . '</div>';
    }
}

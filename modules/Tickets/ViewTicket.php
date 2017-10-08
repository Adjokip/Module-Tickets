<?php

require 'include/Ticket.php';
require 'include/functions.php';

function exec_ogp_module()
{
    global $db, $view;

    if (isset($_SESSION['ticket'])) unset($_SESSION['ticket']);

    $ticket = new Ticket($db);
    $isAdmin = $db->isAdmin($_SESSION['user_id']);

    echo '<h2>'.get_lang('viewing_ticket').'</h2>';

    $tid = (int)$_GET['tid'];
    $uid = $_GET['uid'];
    $ticketData = $ticket->getTicket($tid, $uid);

    if (!$ticket->exists($tid, $uid)) {
        print_failure(get_lang('ticket_not_found'));
        $view->refresh("?m=Tickets");

        return;
    }

    if (!$isAdmin && !$ticket->authorized($_SESSION['user_id'], $tid, $uid)) {
        print_failure(get_lang('ticket_cant_read'));
        $view->refresh("?m=Tickets");

        return;
    }

    if (!$ticketData) {
        print_failure(get_lang('cant_view_ticket'));
        $view->refresh("?m=Tickets");

        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['ticket_submit_response'])) {
            $_POST = array_map('trim', $_POST);
            $_SESSION['ticketReply'] = $_POST['reply_content'];

            $errors = array();

            if (empty($_POST['reply_content'])) $errors[] = get_lang('no_ticket_reply');
            elseif (strlen($_POST['reply_content']) < 4) $errors[] = get_lang('invalid_ticket_reply_length');

            if (empty($errors)) {
                $reply = $ticket->reply($tid, $_SESSION['user_id'], getClientIPAddress(), strip_real_escape_string($_POST['reply_content']), $isAdmin, $uid);
                
                if (!$reply) {
                    echo ticketErrors(array(get_lang('failed_to_reply')));
                    $view->refresh("?m=Tickets&p=submitticket", 60);
                    return;
                }

                if (isset($_SESSION['ticketReply'])) unset($_SESSION['ticketReply']);
                $view->refresh("?m=Tickets&p=viewticket&tid=".$tid."&uid=".$uid, 0);

                return;
            } else {
                echo ticketErrors($errors);
                $view->refresh("?m=Tickets&p=viewticket&tid=".$tid."&uid=".$uid, 60);
                return;
            }

        } elseif(isset($_POST['ticket_close'])) {
            $ticket->updateStatus($tid, $uid, 0);
            $view->refresh("?m=Tickets&p=viewticket&tid=".$tid."&uid=".$uid, 0);
            return;
        }
    }

    echo ticketHeader($ticketData);
    echo ticketReply($ticketData);
    echo '<div class="ticket_ReplyBox status_'.ticketCodeToName($ticketData['status'], true).'">
        <form method="POST">
            <textarea name="reply_content" style="width:100%;" rows="12">'.(isset($_SESSION['ticketReply']) ? $_SESSION['ticketReply'] : '').'</textarea>
            <input type="submit" class="ticket_button" name="ticket_submit_response" value="'. get_lang('ticket_submit_response') . '">
            <input type="submit" class="ticket_button" name="ticket_close" value="'. get_lang('ticket_close') . '">
        </form>
    </div>';


    if (!empty($ticketData['replies'])) {
        echo '<div class="replyContainer">';
        foreach ($ticketData['replies'] as $replyData) {
            echo ticketReply($replyData, $isAdmin);
        }
        echo '</div>';
    } else {
        echo '<div class="no_ticket_replies">'.get_lang('no_ticket_replies').'</div>';
    }

}

<?php

require 'include/Ticket.php';
require 'include/functions.php';

function exec_ogp_module()
{
    global $db, $view;

    $ticket = new Ticket($db);
    $isAdmin = $db->isAdmin($_SESSION['user_id']);
    $services = $ticket->getServices($_SESSION['user_id'], $isAdmin);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$_POST = array_map('trim', $_POST);

    	$_SESSION['ticket']['ticket_subject'] = $_POST['ticket_subject'];
    	$_SESSION['ticket']['ticket_service'] = $_POST['ticket_service'];
    	$_SESSION['ticket']['ticket_message'] = $_POST['ticket_message'];

    	$errors = array();

    	if (empty($_POST['ticket_subject'])) $errors[] = get_lang('no_ticket_subject');
    	elseif (strlen($_POST['ticket_subject']) > 64 || strlen($_POST['ticket_subject']) < 4) $errors[] = get_lang('invalid_ticket_subject_length');

    	if (!empty($services)) {
    		if (array_search($_POST['ticket_service'], array_column($services, 'home_id')) === false) $errors[] = get_lang('invalid_home_selected');
    	}

    	if (empty($_POST['ticket_message'])) $errors[] = get_lang('no_ticket_message');
    	elseif (strlen($_POST['ticket_message']) < 4) $errors[] = get_lang('invalid_ticket_message_length');

    	if (empty($errors)) {
    		$open = $ticket->open($_SESSION['user_id'], getClientIPAddress(), $_POST['ticket_subject'], strip_real_escape_string($_POST['ticket_message']), $_POST['ticket_service']);

    		if (!$open) {
    			echo ticketErrors(array(get_lang('failed_to_open')));
    			$view->refresh("?m=Tickets&p=submitticket", 60);
    			return;
    		}

    		if (isset($_SESSION['ticket'])) unset($_SESSION['ticket']);
    		$view->refresh("?m=Tickets&p=viewticket&tid=".$open['tid']."&uid=".$open['uid'], 0);

    		return;
    	} else {
    		echo ticketErrors($errors);
    		$view->refresh("?m=Tickets&p=submitticket", 60);
    		return;
    	}
    }

    echo '<h2>'.get_lang('submit_ticket').'</h2>';

    echo '
    <form method="POST">
    <div class="ticket_elementDiv">
	    <label>'.get_lang('ticket_subject').'</label>
	    <input type="text" name="ticket_subject" '. (isset($_SESSION['ticket']['ticket_subject']) ? 'value="'.$_SESSION['ticket']['ticket_subject'].'"' : '')  .' pattern=".{4,64}" required title="4 to 64 characters" autofocus>
	</div>
	<div class="ticket_elementDiv">
	    <label>'.get_lang('ticket_service').'</label>
	    <select name="ticket_service">';
	    	if ($services) {
	    		foreach ($services as $service) {
	    			echo '<option value="'.$service['home_id'].'" '.(isset($_SESSION['ticket']['ticket_service']) && $_SESSION['ticket']['ticket_service'] == $service['home_id'] ? 'selected' : '') .'>'.htmlentities($service['home_name']).'</option>';
	    		}
	    	}
	    
	    echo '</select>
	</div>
	<div class="ticket_elementDiv">
	    <label>'.get_lang('ticket_message').'</label>
	    <textarea rows="12" name="ticket_message">'. (isset($_SESSION['ticket']['ticket_message']) ? $_SESSION['ticket']['ticket_message'] : '')  .'</textarea>
	</div>
	<div class="ticket_buttonDiv">
    	<input type="submit" value="'.get_lang('submit_ticket').'" />
    </div>
</form>';

}
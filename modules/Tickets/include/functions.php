<?php

function ticketHeader($info)
{
    return '<div class="divTable">
    <div class="divTableBody">
        <div class="divTableRow">
            <div class="divTableCell infoblock_ticket">'.get_lang('ticket_id').'</div>
            <div class="divTableCell contentblock_ticket">#'.$info['tid'].' - '.$info['uid'] .'</div>
        </div>
        <div class="divTableRow">
            <div class="divTableCell infoblock_ticket">'.get_lang('service_id').'</div>
            <div class="divTableCell contentblock_ticket">'.((int)$info['service_id'] === 0 ? '<i>'.get_lang('ticket_no_service').'</i>' :
                                        '<a href="?m=user_games&p=edit&home_id='.(int)$info['service_id'].'">#'.(int)$info['service_id'].'</a>'). '</div>
        </div>
        <div class="divTableRow">
            <div class="divTableCell infoblock_ticket">'.get_lang('ticket_subject').'</div>
            <div class="divTableCell contentblock_ticket">'.$info['subject'].'</div>
        </div>
        <div class="divTableRow">
            <div class="divTableCell infoblock_ticket">'.get_lang('ticket_submitted').'</div>
            <div class="divTableCell contentblock_ticket">'.$info['created_at'].'</div>
        </div>
        <div class="divTableRow">
            <div class="divTableCell infoblock_ticket">'.get_lang('ticket_updated').'</div>
            <div class="divTableCell contentblock_ticket">'.$info['last_updated'].'</div>
        </div>
        <div class="divTableRow">
            <div class="divTableCell infoblock_ticket">'.get_lang('ticket_status').'</div>
            <div class="divTableCell contentblock_ticket">'.ticketCodeToName($info['status']).'</div>
        </div>
        <div class="divTableRow">
            <div class="divTableCell infoblock_ticket">'.get_lang('submitter_info').'</div>
            <div class="divTableCell contentblock_ticket">'.get_lang('username').': <a href="?m=user_admin&p=edit_user&user_id='. $info['user_id'] .'">'. $info['users_login'] .'</a> - 
            '. (!empty($info['users_fname']) ? get_lang('name') . ': ' . htmlentities($info['users_fname']) . (!empty($info['users_lname']) ? ' '.htmlentities($info['users_lname']).' - ' : '') : '') .
            get_lang('ip') . ': '. inet_ntop($info['user_ip']) .' - '.get_lang('role') .': '. ucfirst($info['users_role']).'
            </div>
        </div>
    </div>
</div>';
}

function ticketReply($replyData, $loggedInAdmin = false)
{
    if (!isset($replyData['is_admin'])) {
        return '<div class="ticket_reply">
    <div class="date">
        '.$replyData['created_at'].'
    </div>
    <div class="user">
        <span class="name">
            <a href="?m=user_admin&p=edit_user&user_id='.$replyData['user_id'].'">'. htmlentities($replyData['users_login']) .'</a> ' .
                    (!empty($info['users_fname']) ? get_lang('name') . ': ' . htmlentities($info['users_fname']) . (!empty($info['users_lname']) ? ' '.htmlentities($info['users_lname']).' - ' : '') : '') .'
        </span>
        <span class="type">
            '.ucfirst($replyData['users_role']).'
        </span>
    </div>
    <div class="message">'.nl2br(htmlentities($replyData['message'])).'</div>
    '. ($loggedInAdmin ? '<div class="ticket_footer">'.get_lang('ip') .' '. inet_ntop($replyData['user_ip']) .'</div>' : '') .'
</div>';
    } else {
        $class = $replyData['is_admin'] == 1 ? 'admin' : 'user';

        return '<div class="ticket_reply '.$class.'">
    <div class="date">
        '.$replyData['date'].'
    </div>
    <div class="'.$class.'">
        <span class="name">
            <a href="?m=user_admin&p=edit_user&user_id='.$replyData['user_id'].'">'. htmlentities($replyData['users_login']) .'</a> ' .
                    (!empty($info['users_fname']) ? get_lang('name') . ': ' . htmlentities($info['users_fname']) . (!empty($info['users_lname']) ? ' '.htmlentities($info['users_lname']).' - ' : '') : '') .'
        </span>
        <span class="type">
            '.ucfirst($replyData['users_role']).'
        </span>
    </div>
    <div class="message">'.nl2br(htmlentities($replyData['message'])).'</div>
    '. ($loggedInAdmin ? '<div class="ticket_footer">'.get_lang('ip') .' '. inet_ntop($replyData['user_ip']) .'</div>' : '') .'
</div>';
    }
}

function ticketErrors($errors) {
    $return = '<div class="ticketErrorHolder">
    <p class="failure">'.(get_lang('ticket_errors_occured') . ':').'</p>
    <ul class="ticketErrorList">';
    foreach ($errors as $error) {
        $return .= '<li class="ticketError">' . $error . '</li>';
    }
    $return .= '</ul>
    </div>';

    return $return;
}

function ticketCodeToName($code, $css = false) {
    $codes = array(
        'ticket_closed',
        'ticket_open',
        'ticket_admin_response',
        'ticket_customer_response',
    );
    
    return $css ? $codes[$code] : get_lang($codes[$code]);
}
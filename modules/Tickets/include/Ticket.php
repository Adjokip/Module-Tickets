<?php

class Ticket
{
    private $db;
    private $tickets;

    public function __construct(OGPDatabase $db)
    {
        $this->db = $db;
    }

    public function tickets($ticketsFor = null)
    {
        $query = "SELECT a.tid, a.uid, a.user_id, a.parent_id, a.subject, a.created_at, a.last_updated, a.status, a.assigned_to
                    FROM OGP_DB_PREFIXtickets a ";

        if ($ticketsFor !== null) {
            $query .= "WHERE a.user_id = ".(int)$ticketsFor." OR a.parent_id = ".(int)$ticketsFor." ";

            if ($this->db->isSubUser($ticketsFor)) {
                $result = $this->db->resultQuery("SELECT users_parent FROM OGP_DB_PREFIXusers WHERE user_id = ".(int)$ticketsFor);
                $query .= "OR a.parent_id = ".(int)$result[0]['users_parent']." ";
            }
        }

        $query .= "ORDER BY a.last_updated DESC";
        $this->tickets = $this->db->resultQuery($query);

        return $this;
    }

    public function list()
    {
        return $this->tickets;
    }

    public function count()
    {
        if ($this->tickets === false) {
            return 0;
        }

        return count($this->tickets);
    }

    public function getTicket($tid, $uid)
    {
        $query = "SELECT a.tid, a.uid, a.user_id, a.user_ip, a.subject, a.message, a.status, a.service_id, a.created_at, a.last_updated,
                            b.users_login, b.users_fname, b.users_lname, b.users_role, b.users_email
                    FROM OGP_DB_PREFIXtickets a
                        JOIN OGP_DB_PREFIXusers b
                            ON (a.user_id = b.user_id)
                    WHERE tid = $tid
                    AND uid = '".$this->db->real_escape_string($uid)."'";

        $result = $this->db->resultQuery($query);

        if (is_array($result)) {
            $ticketInfo = $result[0];
            $ticketInfo['replies'] = $this->getTicketReplies($tid);
            
            return $ticketInfo;
        } else {
            return false;
        }
    }

    private function getTicketReplies($tid)
    {
        $query = "SELECT a.reply_id, a.user_id, a.user_ip, a.message, a.date, a.rating, a.is_admin,
                            b.user_id, b.users_login, b.users_role, b.users_fname, b.users_lname, b.users_email, b.users_parent
                        FROM OGP_DB_PREFIXticket_replies a
                            JOIN OGP_DB_PREFIXusers b
                                ON (a.user_id = b.user_id)
                        WHERE a.ticket_id = $tid
                        ORDER BY a.date DESC";

        return $this->db->resultQuery($query) ?: array();
    }

    public function open($user_id, $user_ip, $subject, $message, $service_id)
    {
        $parent_id = $user_id;
        if ($this->db->isSubUser($user_id)) {
            $result = $this->db->resultQuery("SELECT users_parent FROM OGP_DB_PREFIXusers WHERE user_id = ".(int)$user_id);
            $parent_id = (int)$result[0]['users_parent'];
        }

        $uid = bin2hex(openssl_random_pseudo_bytes(4));

        // $this->db->resultInsertId calls real_escape_string on all the values.
        $fields = array(
            'uid'           =>  $uid,
            'user_id'       =>  $user_id,
            'parent_id'     =>  $parent_id,
            'user_ip'       =>  inet_pton($user_ip),
            'subject'       =>  $subject,
            'message'       =>  $message,
            'service_id'    =>  ($service_id === 0 ? null : (int)$service_id),
            'status'        =>  1
        );

        $insertId = $this->db->resultInsertId('tickets', $fields);
        if ($insertId !== false) {
            return array('uid' => $uid, 'tid' => $insertId);
        }
    }

    public function reply($tid, $user_id, $user_ip, $message, $is_admin, $uid)
    {
        $query = "INSERT INTO OGP_DB_PREFIXticket_replies (
                        ticket_id, user_id, user_ip, message, is_admin
                    ) VALUES (
                        $tid, $user_id,
                        '".inet_pton($user_ip)."',
                        '".$this->db->real_escape_string($message)."', ".($is_admin ? '1' : '0')."
                    )";

        if ($this->db->query($query)) {
            $this->updateStatus($tid, $uid, ($is_admin ? 2 : 3));
            $this->updateTimestamp($tid, $uid);
            return true;
        }

        return false;
    }

    // 0 = closed
    // 1 = open
    // 2 = admin response
    // 3 = customer response
    public function updateStatus($tid, $uid, $status)
    {
        $status = (int)$status;
        return $this->db->query("UPDATE OGP_DB_PREFIXtickets SET status = $status WHERE tid = $tid AND uid = '$uid'");
    }

    public function updateTimestamp($tid, $uid)
    {
        return $this->db->query("UPDATE OGP_DB_PREFIXtickets SET last_updated = NOW() WHERE tid = $tid AND uid = '$uid'");
    }

    public function exists($tid, $uid)
    {
        $query = "SELECT COUNT(1) AS ticketCount FROM OGP_DB_PREFIXtickets
                    WHERE `tid` = $tid AND
                        `uid` = '".$this->db->real_escape_string($uid)."'";
                        
        $result = $this->db->resultQuery($query);
        return ($result[0]['ticketCount'] == 0 ? false : true);
    }

    public function authorized($user_id, $tid, $uid)
    {
        $query = "SELECT a.user_id as utid, a.parent_id, b.user_id, b.users_parent
                    FROM OGP_DB_PREFIXtickets a
                        JOIN ogp_users b
                        ON (
                            a.user_id = b.user_id
                            OR a.user_id = b.users_parent
                            OR a.parent_id = b.user_id
                            OR a.parent_id = b.users_parent
                        )
                    WHERE a.tid = ".(int)$tid." AND a.uid = '".$this->db->real_escape_string($uid)."'
                        AND (
                            b.user_id = ".(int)$user_id ."
                            OR b.users_parent = ".(int)$user_id."
                        )";

        $result = $this->db->resultQuery($query);
        return $result[0] ?: false;
    }

    public function getServices($user_id, $is_admin)
    {
        if ($is_admin) {
            $homes = $this->db->getHomesFor('admin', $user_id);
        } else {
            $homes = $this->db->getHomesFor('user_and_group', $user_id);
        }

        if (!$homes || count($homes) === 0) {
            return false;
        }

        $return = array(
            array('home_id' => 0, 'home_name' => '')
        );

        foreach ($homes as $home) {
            $return[] = array('home_id' => $home['home_id'], 'home_name' => $home['home_name']);
        }

        return $return;
    }

    public function setRating($tid, $uid, $user_id, $reply_id, $rating)
    {
    }

    public function assignTo($tid, $admin_id)
    {
    }
}
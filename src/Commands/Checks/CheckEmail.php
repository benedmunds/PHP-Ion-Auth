<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:46 PM
 */

namespace IonAuth\IonAuth\Commands\Checks;


class CheckEmail
{
    /**
     * Checks email
     *
     * @return bool
     * @author Mathew
     **/
    public function emailCheck($email)
    {
        $this->triggerEvents('email_check');

        $this->triggerEvents('extra_where');

        return $this->db->where('email', $email)
            ->count_all_results($this->tables['users']) > 0;
    }
}
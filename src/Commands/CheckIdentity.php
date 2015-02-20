<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:47 PM
 */

namespace IonAuth\IonAuth\Commands;


class CheckIdentity
{
    /**
     * Identity check
     *
     * @return bool
     * @author Mathew
     **/
    public function identityCheck($identity)
    {
        $this->triggerEvents('identity_check');

        return $this->db->where($this->identityColumn, $identity)
            ->count_all_results($this->tables['users']) > 0;
    }
}
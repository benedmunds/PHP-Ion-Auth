<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:18 PM
 */

namespace IonAuth\IonAuth\Commands;


class ClearForgottenPassword
{
    public function clearForgottenPasswordCode($code)
    {

        if (empty($code))
        {
            return false;
        }

        $this->db->where('forgottenPasswordCode', $code);

        if ($this->db->count_all_results($this->tables['users']) > 0)
        {
            $data = array(
                'forgottenPasswordCode' => null,
                'forgottenPasswordTime' => null
            );

            $this->db->update($this->tables['users'], $data, array('forgottenPasswordCode' => $code));

            return true;
        }

        return false;
    }
}
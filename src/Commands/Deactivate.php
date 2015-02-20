<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:19 PM
 */

namespace IonAuth\IonAuth\Commands;


class Deactivate
{
    /**
     * Deactivate
     *
     * @return void
     * @author Mathew
     **/
    public function deactivate($id = null)
    {
        $this->triggerEvents('deactivate');

        if (!isset($id))
        {
            $this->setError('deactivateUnsuccessful');
            return false;
        }

        $activationCode = $this->salt();
        $this->activationCode = $activationCode;

        $data = array(
            'activation_code' => $activationCode,
            'active' => 0
        );

        $this->triggerEvents('extraWhere');
        $this->db->update($this->tables['users'], $data, array('id' => $id));

        $return = $this->db->affected_rows() == 1;
        if ($return)
        {
            $this->setMessage('deactivateSuccessful');
        }
        else
        {
            $this->setError('deactivateUnsuccessful');
        }

        return $return;
    }
}
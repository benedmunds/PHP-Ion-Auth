<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:19 PM
 */

namespace IonAuth\IonAuth\Commands;


class Activate
{

    /**
     * Activation functions
     *
     * Activate : Validates and removes activation code.
     * Deactivae : Updates a users row with an activation code.
     *
     * @author Mathew
     */

    /**
     * activate
     *
     * @return void
     * @author Mathew
     **/
    public function activate($id, $code = false)
    {
        $this->triggerEvents('pre_activate');

        if ($code !== false)
        {
            $query = $this->db->select($this->identityColumn)
                ->where('activation_code', $code)
                ->take(1)
                ->get($this->tables['users']);

            $result = $query->first();

            if (count($query) !== 1)
            {
                $this->triggerEvents(array('postActivate', 'postActivateUnsuccessful'));
                $this->setError('activateUnsuccessful');
                return false;
            }

            $identity = $result->{$this->identityColumn};

            $data = array(
                'activationCode' => null,
                'active' => 1
            );

            $this->triggerEvents('extraWhere');
            $this->db->update($this->tables['users'], $data, array($this->identityColumn => $identity));
        }
        else
        {
            $data = array(
                'activation_code' => null,
                'active' => 1
            );


            $this->triggerEvents('extraWhere');
            $this->db->update($this->tables['users'], $data, array('id' => $id));
        }


        if ($this->db->affected_rows() == 1)
        {
            $this->triggerEvents(array('postActivate', 'postActivateSuccessful'));
            $this->setMessage('activateSuccessful');
        }
        else
        {
            $this->triggerEvents(array('postActivate', 'postActivateUnsuccessful'));
            $this->setError('activateUnsuccessful');
        }


//        return $return;
    }
}
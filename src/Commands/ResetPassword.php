<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:18 PM
 */

namespace IonAuth\IonAuth\Commands;


class ResetPassword
{
    /**
     * reset password
     *
     * @return bool
     * @author Mathew
     **/
    public function resetPassword($identity, $new)
    {

        $this->triggerEvents('preChangePassword');

        if (!$this->identityCheck($identity))
        {
            $this->triggerEvents(array('postChangePassword', 'postChangePasswordUnsuccessful'));
            return false;
        }

        $this->triggerEvents('extraWhere');

        $query = $this->db->select('id, password, salt')
            ->where($this->identityColumn, $identity)
            ->take(1)
            ->get($this->tables['users']);

        if (count($query) !== 1)
        {
            $this->triggerEvents(array('postChangePassword', 'postChangePasswordUnsuccessful'));
            $this->setError('passwordChangeUnsuccessful');
            return false;
        }

        $result = $query->first();

        $new = $this->hashPassword($new, $result->salt);

        //store the new password and reset the remember code so all remembered instances have to re-login
        //also clear the forgotten password code
        $data = array(
            'password' => $new,
            'remember_code' => null,
            'forgotten_password_code' => null,
            'forgotten_password_time' => null,
        );

        $this->triggerEvents('extraWhere');
        $this->db->update($this->tables['users'], $data, array($this->identityColumn => $identity));

        $return = $this->db->affected_rows() == 1;
        if ($return)
        {
            $this->triggerEvents(array('postChangePassword', 'postChangePasswordSuccessful'));
            $this->setMessage('passwordChangeSuccessful');
        }
        else
        {
            $this->triggerEvents(array('postChangePassword', 'postChangePasswordUnsuccessful'));
            $this->setError('passwordChangeUnsuccessful');
        }


        return $return;

    }

}
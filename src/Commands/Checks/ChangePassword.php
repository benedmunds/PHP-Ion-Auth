<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:17 PM
 */

namespace IonAuth\IonAuth\Commands\Checks;


class ChangePassword
{
    /**
     * change password
     *
     * @access public
     * @param  $identity
     * @param  $old
     * @param  $new
     *
     * @return bool
     **/
    public function changePassword($identity, $old, $new)
    {
        $this->triggerEvents('preChangePassword');

        $this->triggerEvents('extraWhere');

        $query = $this->db->select('id, password, salt')
            ->where($this->identityColumn, $identity)
            ->take(1)
            ->get($this->tables['users']);

        if (count($query) !== 1)
        {
            $this->triggerEvents(['postChangePassword', 'postChangePasswordUnsuccessful']);
            $this->setError('passwordChangeUnsuccessful');
            return false;
        }

        $user = $query->first();

        $oldPasswordMatches = $this->hashPasswordDb($user->id, $old);

        if ($oldPasswordMatches === true)
        {
            //store the new password and reset the remember code so all remembered instances have to re-login
            $hashedNewPassword = $this->hashPassword($new, $user->salt);
            $data = [
                'password' => $hashedNewPassword,
                'remember_code' => null,
            ];

            $this->triggerEvents('extra_where');

            $successfullyChangedPasswordInDb = $this->db->update(
                $this->tables['users'],
                $data, [$this->identityColumn => $identity]
            );
            if ($successfullyChangedPasswordInDb)
            {
                $this->triggerEvents(['postChangePassword', 'postChangePassword_Successful']);
                $this->setMessage('passwordChangeSuccessful');
            }
            else
            {
                $this->triggerEvents(['postChangePassword', 'postChangePasswordUnsuccessful']);
                $this->setError('passwordChangeUnsuccessful');
            }

            return $successfullyChangedPasswordInDb;
        }

        $this->setError('passwordChangeUnsuccessful');
        return false;
    }
}

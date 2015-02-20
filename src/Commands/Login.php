<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:14 PM
 */

namespace IonAuth\IonAuth\Commands;


class Login
{
    /**
     * login
     *
     * @return bool
     * @author Mathew
     **/
    public function login($identity, $password, $remember = false)
    {
        $this->triggerEvents('preLogin');

        if (empty($identity) || empty($password))
        {
            $this->setError('loginUnsuccessful');
            return false;
        }

        $this->triggerEvents('extraWhere');

        $query = $this->db->table($this->config->get('tables')['users'])
            ->select(
                array(
                    'id',
                    $this->config->get('identity'),
                    'username',
                    'email',
                    'id',
                    'password',
                    'active',
                    'last_login'
                )
            )
            ->where($this->config->get('identity'), $identity)
            ->take(1);


        if ($this->isTimeLockedOut($identity))
        {
            //Hash something anyway, just to take up time
            $this->hashPassword($password);

            $this->triggerEvents('postLoginUnsuccessful');
            $this->setError('loginTimeout');

            return false;
        }


        $user = $query->first();

        if (isset($user) === true)
        {

            echo 'here:';
            var_dump($user->id, $password);
            $password = $this->hashPasswordDb($user->id, $password);

            echo '------';
            if ($password === true)
            {
                if ($user->active == 0)
                {
                    $this->triggerEvents('post_login_unsuccessful');
                    $this->setError('login_unsuccessful_not_active');

                    return false;
                }

                $this->setSession($user);

                $this->updateLastLogin($user->id);

                $this->clearLoginAttempts($identity);

                if ($remember && $this->config->get('remember_users'))
                {
                    $this->rememberUser($user->id);
                }

                $this->triggerEvents(array('postLogin', 'postLoginSuccessful'));
                $this->setMessage('loginSuccessful');

                return true;
            }
        }

        //Hash something anyway, just to take up time
        $this->hashPassword($password);

        $this->increaseLoginAttempts($identity);

        $this->triggerEvents('postLoginUnsuccessful');
        $this->setError('loginUnsuccessful');

        return false;
    }
}
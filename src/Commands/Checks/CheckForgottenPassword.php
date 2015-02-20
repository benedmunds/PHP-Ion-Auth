<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:22 PM
 */

namespace IonAuth\IonAuth\Commands\Checks;


class CheckForgottenPassword
{
    /**
     * forgotten_password_check()
     * --------------------------
     * @param $code
     * @return void
     * @author Michael
     */
    public function forgottenPasswordCheck($code)
    {
        $profile = $this->where('forgotten_password_code', $code)->users()->row(); //pass the code to profile

        if (!is_object($profile))
        {
            $this->setError('passwordChangeUnsuccessful');
            return false;
        }
        else
        {
            if ($this->config->get('forgotPasswordExpiration') > 0)
            {
                //Make sure it isn't expired
                $expiration = $this->config->get('forgotPasswordExpiration');
                if (time() - $profile->forgotten_password_time > $expiration)
                {
                    //it has expired
                    $this->clearForgottenPasswordCode($code);
                    $this->setError('passwordChangeUnsuccessful');
                    return false;
                }
            }

            return $profile;
        }
    }
}
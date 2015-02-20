<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:21 PM
 */

namespace IonAuth\IonAuth\Commands;


class Logout
{

    /**
     * logout()
     * --------------------
     * @return void
     * @author Mathew
     **/
    public function logout()
    {
        $this->ionAuthModel->triggerEvents('logout');

        $identity = $this->config->get('identity');
        $this->session->unset_userdata(
            array(
                $identity => '',
                'id' => '',
                'user_id' => ''
            )
        );

        // delete the remember me cookies if they exist
        if ($this->ionAuthModel->getCookie('identity'))
        {
            $this->ionAuthModel->deleteCookie('identity');
        }

        if ($this->ionAuthModel->getCookie('remember_code'))
        {
            $this->ionAuthModel->deleteCookie('remember_code');
        }

        // Destroy the session
        $this->session->sess_destroy();

        $this->setMessage('logoutSuccessful');

        return true;
    }

}
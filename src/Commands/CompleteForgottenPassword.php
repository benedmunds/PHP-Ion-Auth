<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 4:12 AM
 */

namespace IonAuth\IonAuth\Commands;


class CompleteForgottenPassword
{
    /**
     * Forgotten Password Complete
     *
     * @return string
     * @author Mathew
     **/
    public function _forgottenPasswordComplete($code, $salt = false)
    {
        $this->triggerEvents('preForgottenPasswordComplete');

        if (empty($code))
        {
            $this->triggerEvents(array('postForgottenPasswordComplete', 'postForgottenPasswordCompleteUnsuccessful'));
            return false;
        }

        $profile = $this->where('forgottenPasswordCode', $code)->users()->first(); //pass the code to profile

        if ($profile)
        {

            if ($this->config->get('forgotPasswordExpiration') > 0)
            {
                //Make sure it isn't expired
                $expiration = $this->config->get('forgotPasswordExpiration');
                if (time() - $profile->forgotten_password_time > $expiration)
                {
                    //it has expired
                    $this->setError('forgotPasswordExpired');
                    $this->triggerEvents(
                        array('postForgottenPasswordComplete', 'postForgottenPasswordCompleteUnsuccessful')
                    );
                    return false;
                }
            }

            $password = $this->salt();

            $data = array(
                'password' => $this->hashPassword($password, $salt),
                'forgotten_password_code' => null,
                'active' => 1,
            );

            $this->db->update($this->tables['users'], $data, array('forgotten_password_code' => $code));

            $this->triggerEvents(array('postForgottenPasswordComplete', 'postForgottenPasswordCompleteSuccessful'));
            return $password;
        }

        $this->triggerEvents(array('postForgottenPasswordComplete', 'postForgottenPasswordCompleteUnsuccessful'));
        return false;
    }

    /**
     * forgotten_password_complete()
     * ------------------------------
     * @param $code
     * @return void
     * @author Mathew
     */
    public function forgottenPasswordComplete($code)
    {
        $this->ionAuthModel->triggerEvents('prePasswordChange');

        $identity = $this->config->get('identity');
        $profile = $this->where('forgotten_password_code', $code)->users()->row(); //pass the code to profile

        if (!$profile)
        {
            $this->ionAuthModel->triggerEvents(array('postPasswordChange', 'passwordChangeUnsuccessful'));
            $this->setError('passwordChangeUnsuccessful');
            return false;
        }

        $newPassword = $this->ionAuthModel->forgottenPasswordComplete($code, $profile->salt);

        if ($newPassword)
        {
            $data = array(
                'identity' => $profile->{$identity},
                'new_password' => $newPassword
            );

            if (!$this->config->get('useDefaultEmail'))
            {
                $this->setMessage('passwordChangeSuccessful');
                $this->ionAuthModel->triggerEvents(array('postPasswordChange', 'passwordChangeSuccessful'));
                return $data;
            }
            else
            {
                $message = $this->load->view(
                    $this->config->get('emailTemplates') . $this->config->get('emailForgotPasswordComplete'),
                    $data,
                    true
                );

                $this->email->clear();
                $this->email->from($this->config->get('adminEmail'), $this->config->get('siteTitle'));
                $this->email->to($profile->email);
                $this->email->subject(
                    $this->config->get('siteTitle') . ' - ' . $this->lang->line('emailNewPasswordSubject')
                );
                $this->email->message($message);

                if ($this->email->send())
                {
                    $this->setMessage('passwordChangeSuccessful');
                    $this->ionAuthModel->triggerEvents(array('postPasswordChange', 'passwordChangeSuccessful'));
                    return true;
                }
                else
                {
                    $this->setError('passwordChangeUnsuccessful');
                    $this->ionAuthModel->triggerEvents(array('postPasswordChange', 'passwordChangeUnsuccessful'));
                    return false;
                }

            }
        }

        $this->ionAuthModel->triggerEvents(array('postPasswordChange', 'passwordChangeUnsuccessful'));
        return false;
    }

}
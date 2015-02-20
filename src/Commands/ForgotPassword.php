<?php
/**
 * Created by PhpStorm.
 * User: kayladnls
 * Date: 1/19/15
 * Time: 11:16 PM
 */

namespace IonAuth\IonAuth\Commands;


class ForgotPassword
{

    /**
     * Insert a forgotten password key.
     *
     * @return bool
     * @author Mathew
     * @updated Ryan
     * @updated 52aa456eef8b60ad6754b31fbdcc77bb
     **/
    public function _forgottenPassword($identity)
    {
        if (empty($identity))
        {
            $this->triggerEvents(array('postForgottenPassword', 'postForgottenPasswordUnsuccessful'));
            return false;
        }

        //All some more randomness
        $activationCodePart = "";
        if (function_exists("openssl_random_pseudo_bytes"))
        {
            $activationCodePart = openssl_random_pseudo_bytes(128);
        }

        for ($i = 0; $i < 1024; $i++)
        {
            $activationCodePart = sha1($activationCodePart . mt_rand() . microtime());
        }

        $key = $this->hash_code($activationCodePart . $identity);

        $this->forgottenPasswordCode = $key;

        $this->triggerEvents('extraWhere');

        $update = array(
            'forgotten_password_code' => $key,
            'forgotten_password_time' => time()
        );

        $this->db->update($this->tables['users'], $update, array($this->identityColumn => $identity));

        $return = $this->db->affected_rows() == 1;

        if ($return)
        {
            $this->triggerEvents(array('postForgottenPassword', 'postForgottenPasswordSuccessful'));
        }
        else
        {
            $this->triggerEvents(array('postForgottenPassword', 'postForgottenPasswordUnsuccessful'));
        }

        return $return;
    }

    /**
     * forgotten password feature
     *
     * @param $identity
     * @return mixed  boolean / array
     * @author Mathew
     */
    public function forgottenPassword($identity)
    {
        if ($this->ionAuthModel->forgottenPassword($identity)) // changed
        {
            // Get user information
            $user = $this->where($this->config->get('identity'), $identity)->users()->row(
            ); //changed to get_user_by_identity from email

            if ($user)
            {
                $data = array(
                    'identity' => $user->{$this->config->get('identity')},
                    'forgotten_password_code' => $user->forgottenPasswordCode
                );

                if (!$this->config->get('useDefaultEmail'))
                {
                    $this->setMessage('forgotPasswordSuccessful');
                    return $data;
                }
                else
                {
                    $message = $this->load->view(
                        $this->config->get('emailTemplates') . $this->config->get('emailForgotPassword'),
                        $data,
                        true
                    );
                    $this->email->clear();
                    $this->email->from($this->config->get('adminEmail'), $this->config->get('siteTitle'));
                    $this->email->to($user->email);
                    $this->email->subject(
                        $this->config->get('siteTitle') . ' - ' . $this->lang->line('emailForgottenPasswordSubject')
                    );
                    $this->email->message($message);

                    if ($this->email->send())
                    {
                        $this->setMessage('forgotPasswordSuccessful');
                        return true;
                    }
                    else
                    {
                        $this->setError('forgotPasswordUnsuccessful');
                        return false;
                    }
                }
            }
            else
            {
                $this->setError('forgotPasswordUnsuccessful');
                return false;
            }
        }
        else
        {
            $this->setError('forgotPasswordUnsuccessful');
            return false;
        }
    }
}
<?php

namespace IonAuth\IonAuth\Entities;

use IonAuth\IonAuth\Utilities\Collection\CollectionItem;

class LoginAttempt implements CollectionItem
{

    private $id;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Function: increaseLoginAttempts()
     * ------------------------------------------------
     * increase_login_attempts
     * Based on code from Tank Auth, by Ilya Konyukhov (https://github.com/ilkon/Tank-Auth)
     *
     * @param string $identity
     **/
    public function increaseLoginAttempts($identity)
    {
      if ($this->config->get('trackLoginAttempts')) {
        $ipAddress = $this->_prepareIp($_SERVER['REMOTE_ADDR']);

        return $this->db->insert(
          $this->tables['loginAttempts'],
          array('ip_address' => $ipAddress, 'login' => $identity, 'time' => time())
        );
      }
      return false;
    }

    /**
     * clear_login_attempts
     *
     * Based on code from Tank Auth, by Ilya Konyukhov (https://github.com/ilkon/Tank-Auth)
     *
     * @param string $identity
     * @param $expire_period
     * @return bool
     **/
    public function clearLoginAttempts($identity, $expire_period = 86400)
    {
        if ($this->config->get('trackLoginAttempts')) {
            $ipAddress = $this->_prepareIp($_SERVER['REMOTE_ADDR']);

            $this->db->where(array('ip_address' => $ipAddress, 'login' => $identity));
            // Purge obsolete login attempts
            $this->db->or_where('time <', time() - $expire_period, false);

            return $this->db->delete($this->tables['loginAttempts']);
        }
        return false;
    }

    /**
     * Function: getNumberOfAttempts()
     * ------------------------------------------------------------------------------------
     * Get number of attempts to login occured from given IP-address or identity
     * Based on code from Tank Auth, by Ilya Konyukhov (https://github.com/ilkon/Tank-Auth)
     *
     * @param    string $identity
     * @return    int
     */
    function getNumberOfAttempts(User $user)
    {
        if ($this->config->get('trackLoginAttempts')) {
            $ipAddress = $this->_prepareIp($_SERVER['REMOTE_ADDR']);

            $this->db->select('1', false);
            $this->db->where('ip_address', $ipAddress);
            if (strlen($identity) > 0) {
                $this->db->or_where('login', $identity);
            }

            $qres = $this->db->get($this->tables['loginAttempts']);
            return count($qres);
        }
        return 0;
    }

    /**
     * getLastAttemptTime
     *
     * Get the last login attempt from a user.
     * @param    string $user
     */
    public function getLastAttemptTime(User $user)
    {
        if ($this->config->get('trackLoginAttempts'))
        {
            $ipAddress = $this->_prepareIp($_SERVER['REMOTE_ADDR']);

            $this->db->select_max('time');
            $this->db->where('ip_address', $ipAddress);
            if (strlen($identity) > 0)
            {
                $this->db->or_where('login', $identity);
            }
            $qres = $this->db->get($this->tables['loginAttempts'], 1);

            if (count($qres) > 0)
            {
                return $qres->first()->time;
            }
        }
        return 0;
    }

    /**
     * is_max_login_attempts_exceeded
     * Based on code from Tank Auth, by Ilya Konyukhov (https://github.com/ilkon/Tank-Auth)
     *
     * @param string $identity
     * @return boolean
     **/
    public function isMaxLoginAttemptsExceeded()
    {
        if ($this->config->get('trackLoginAttempts'))
        {
            $maxAttempts = $this->config->get('maximumLoginAttempts');
            if ($maxAttempts > 0)
            {
                $attempts = $this->getAttemptsNum();
                return $attempts >= $maxAttempts;
            }
        }
        return false;
    }

    /**
     *
     */
    /**
     * getIpAddress
     *
     * get the ip address of the user.
     */
    public function getIpAddress()
    {
        // TODO: write logic here
    }
}

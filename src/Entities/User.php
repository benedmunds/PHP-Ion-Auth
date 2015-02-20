<?php

namespace IonAuth\IonAuth\Entities;

use IonAuth\IonAuth\Helper;
use IonAuth\IonAuth\Utilities\Collection\CollectionItem;
use IonAuth\IonAuth\Utilities\Collection\GroupCollection;
use IonAuth\IonAuth\Repositories\UserRepository;

class User implements CollectionItem
{
    /**
     * account status ('not_activated', etc ...)
     *
     * @var string
     **/
    protected $status;

    private $email;
    private $groups;
    private $id;
    private $last_name;
    private $last_login;

    function __construct()
    {
//        $this->userRepository = new UserRepository($config, $db);
        $this->groups = GroupCollection::create([]);
    }

    public function getId()
    {
        return $this->id;
    }


    /**
     * logged_in
     * -----------------
     * @return bool
     **/
    public function loggedIn()
    {
        $this->ionAuthModel->triggerEvents('loggedIn');
        return (bool)isset($_SESSION['identity']) ? true : false;
    }

    /**
     * logged in
     * --------------------
     * @return integer
     **/
    public function getUserId()
    {
        $userId = $_SESSION['userId'];

        if (!empty($userId))
        {
            return $userId;
        }

        return null;
    }


    /**
     * is admin
     * ---------------------
     * @param bool $id
     * @return bool
     */
    public function isAdmin($id = false)
    {
        $this->ionAuthModel->triggerEvents('isAdmin');

        $adminGroup = $this->config['adminGroup'];

        return $this->inGroup($adminGroup, $id);
    }


    /**
     * in group
     * --------------------------
     * @param mixed group(s) to check
     *
     * @return bool
     **/
    public function inGroup(Group $group)
    {
        return in_array($group->getID(), $this->groups->getKeys());
    }

    /**
     * Function is TimeLockedOut()
     * ---------------------------------------------------------------------
     * Get a boolean to determine if an account should be locked out due to
     * exceeded login attempts within a given period
     *
     * @param     string , $identity
     * @return    boolean
     */
    public function isTimeLockedOut()
    {
        return $this->isMaxLoginAttemptsExceeded() && $this->getLastAttemptTime() > time(
        ) - $this->config->get('lockout_time');
    }

    /**
     * Function: getLastAttemptTime()
     * --------------------------------------
     * Get the time of the last time a login attempt occured from given IP-address or identity
     *
     * @param    string $identity
     * @return    int
     */


    /**
     * users
     *
     * @return object Users
     **/
    public function all($groups = null)
    {
        $this->events->trigger('users');

        if (isset($this->_ionSelect) && !empty($this->_ionSelect))
        {
            foreach ($this->_ionSelect as $select)
            {
                $this->db->select($select);
            }

            $this->_ionSelect = array();
        }
        else
        {
            //default selects
            $this->db->select(
                array(
                    $this->tables['users'] . '.*',
                    $this->tables['users'] . '.id as id',
                    $this->tables['users'] . '.id as user_id'
                )
            );
        }

        //filter by group id(s) if passed
        if (isset($groups))
        {
            //build an array if only one group was passed
            if (is_numeric($groups))
            {
                $groups = Array($groups);
            }

            //join and then run a where_in against the group ids
            if (isset($groups) && !empty($groups))
            {
                $this->db->distinct();
                $this->db->join(
                    $this->tables['usersGroups'],
                    $this->tables['usersGroups'] . '.' . $this->join['users'] . '=' . $this->tables['users'] . '.id',
                    'inner'
                );

                $this->db->where_in($this->tables['usersGroups'] . '.' . $this->join['groups'], $groups);
            }
        }

        $this->events->trigger('extraWhere');

        //run each where that was passed
        if (isset($this->_ionWhere) && !empty($this->_ionWhere))
        {
            foreach ($this->_ionWhere as $where)
            {
                $this->db->where($where);
            }

            $this->_ionWhere = array();
        }

        if (isset($this->_ionLike) && !empty($this->_ionLike))
        {
            foreach ($this->_ionLike as $like)
            {
                $this->db->orLike($like);
            }

            $this->_ionLike = array();
        }

        if (isset($this->_ionLimit) && isset($this->_ionOffset))
        {
            $this->db->take($this->_ionLimit, $this->_ionOffset);

            $this->_ionLimit = null;
            $this->_ionOffset = null;
        }
        else
        {
            if (isset($this->_ionLimit))
            {
                $this->db->take($this->_ionLimit);

                $this->_ionLimit = null;
            }
        }

        //set the order
        if (isset($this->_ionOrderBy) && isset($this->_ionOrder))
        {
            $this->db->order_by($this->_ionOrderBy, $this->_ionOrder);

            $this->_ionOrder = null;
            $this->_ionOrderBy = null;
        }

        $this->response = $this->db->get($this->tables['users']);

        return $this;
    }

    /**
     * user
     *
     * @return IonAuth\IonAuth\Entities\User
     **/
    public function find($id)
    {
        $this->events->trigger('user');

        $this->triggerEvents('getUsersGroup');

        //if no id was passed use the current users id
        $id || $id = $_SESSION['user_id'];

        return $this->db->select(
            'select ' . $this->tables['users_groups'] . '.' . $this->join['groups'] . ' as id, ' . $this->tables['groups'] . '.name, ' . $this->tables['groups'] . '.description
		     FROM ' . $this->tables['users_groups']
            . " LEFT JOIN " . $this->tables['groups'] . " ON " . $this->tables['users_groups'] . '.' . $this->join['groups'] . ' = ' . $this->tables['groups'] . '.id'
            . ' WHERE ' . $this->tables['users_groups'] . '.' . $this->join['users'] . " = " . $id
        );

        $this->take(1);
        $this->where($this->tables['users'] . '.id', $id);

        $this->users();

        return $this;
    }

    /**
     * update
     *
     * @return bool
     * @author Phil Sturgeon
     **/
    public function update()
    {
//        $this->triggerEvents('preUpdateUser');

//        $this->db->trans_begin();

        if (array_key_exists($this->identityColumn, $data) && $this->identityCheck(
                $data[$this->identityColumn]
            ) && $user->{$this->identityColumn} !== $data[$this->identityColumn]
        )
        {
            $this->db->trans_rollback();
            $this->setError('accountCreationDuplicate' . ucwords($this->identityColumn));

            $this->events->trigger(array('postUpdateUser', 'postUpdateUserUnsuccessful'));
            $this->setError('updateUnsuccessful');

            return false;
        }

        // Filter the data passed
        $data = $this->_filterData($this->tables['users'], $data);

        if (array_key_exists('username', $data) || array_key_exists('password', $data) || array_key_exists(
                'email',
                $data
            )
        )
        {
            if (array_key_exists('password', $data))
            {
                if (!empty($data['password']))
                {
                    $data['password'] = $this->hash_password($data['password'], $user->salt);
                }
                else
                {
                    // unset password so it doesn't effect database entry if no password passed
                    unset($data['password']);
                }
            }
        }

        $this->events->trigger('extraWhere');
        $this->db->update($this->tables['users'], $data, array('id' => $user->id));

        if ($this->db->trans_status() === false)
        {
            $this->db->trans_rollback();

            $this->events->trigger(array('postUpdateUser', 'postUpdateUserUnsuccessful'));
            $this->setError('updateUnsuccessful');
            return false;
        }

        $this->db->trans_commit();

        $this->events->trigger(array('postUpdateUser', 'postUpdateUserSuccessful'));
        $this->setMessage('updateSuccessful');
        return true;
    }

    /**
     * delete_user
     *
     * @return bool
     **/
    public function delete()
    {
        $this->events->trigger('preDeleteUser');

        // remove user from groups
        $this->groups->clear();

        // delete user from users table should be placed after remove from group
        $affectedRows = $this->db->delete($this->tables['users'], array('id' => $id));

        if ($affectedRows == 0) return false;


        if ($this->db->trans_status() === false)
        {
            $this->db->trans_rollback();
            $this->events->trigger(array('postDeleteUser', 'postDeleteUserUnsuccessful'));
            $this->setError('deleteUnsuccessful');
            return false;
        }

//        $this->triggerEvents(array('postDeleteUser', 'postDeleteUserSuccessful'));
//        $this->setMessage('deleteSuccessful');
        return true;
    }

    /**
     * update_last_login
     *
     **/
    public function updateLastLogin()
    {
//        $this->triggerEvents('updateLastLogin');
//        $this->triggerEvents('extraWhere');

        $this->last_login = time();

        return $this->last_login;
    }

    public function setEmail($email)
    {
        if (\IonAuth\IonAuth\Helper\validateEmail($email)) $this->email = $email;
        else throw new \Exception('InvalidEmail');
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function addGroup(Group $group)
    {
        $this->groups->add($group);
    }

    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last name
     *
     * @param $last_name , string
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
    }

    /**
     * Get last name
     *
     * @return
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Get full name
     *
     * @return
     */
    public function getFullName()
    {
        return $this->first_name . " " . $this->last_name;
    }

    /**
     * remove_from_group
     *
     * @return bool
     * @author Ben Edmunds
     **/
    public function removeGroup(Group $group)
    {
        $this->groups->remove($group);

//?        $this->triggerEvents('removeFromGroup');

//        // if group id(s) are passed remove user from the group(s)
//        if (!empty($groupIds) && !is_array($groupIds)) $groupIds = array($groupIds);
//
//        foreach ($groupIds as $groupId)
//        {
//            $this->db->delete(
//                $this->tables['usersGroups'],
//                array($this->join['groups'] => (int)$groupId, $this->join['users'] => (int)$user_id)
//            );
//            if (isset($this->_cacheUserInGroup[$userId]) && isset($this->_cacheUserInGroup[$userId][$groupId])) {
//                unset($this->_cacheUserInGroup[$userId][$groupId]);
//            }
//        }

            return true;
    }

    /**
     * Get last login
     *
     * @return $this->last_login, string
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }
}

<?php
namespace IonAuth\IonAuth\Repositories;

use IonAuth\IonAuth\Config;
use IonAuth\IonAuth\Db;


class UserRepository implements RepositoryInterface {

	protected $db;
	protected $statement;
	protected $table;

    public function __construct(Config $config, DbInterface $db) {

    	$this->config = $config;
    	$this->db     = $db;
    	$this->table  = $config->tables['users'];

    }

}
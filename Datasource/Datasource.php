<?php
namespace PhpDatabaseModel\Datasource;

interface Datasource
{
    public function __construct(Config $config);
    public function select($q);
    public function insert($q);
    public function update($q);
    public function delete($q);
    public function query($q);
    public function getLogs();
    public function getInstance(Config $config);
    public function getCleanField($name);
    public function getCleanValue($value);
    public function getFieldInfo($table);
    public function getLastInsertId();
    public function begin();
    public function commit();
    public function rollback();
}

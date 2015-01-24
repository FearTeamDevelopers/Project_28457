<?php

namespace THCFrame\Router\Model;

use THCFrame\Model\Model;

/**
 * ORM Redirect model 
 * 
 * SQL code: 
 * CREATE TABLE `tb_redirect` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fromPath` varchar(250) NOT NULL DEFAULT '',
  `toPath` varchar(250) NOT NULL DEFAULT '',
  `module` varchar(30) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`fromPath`, `toPath`, `module`),
  KEY `ix_redirect` (`fromPath`, `module`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */
class Redirect extends Model
{

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * @unique
     * 
     * @validate required, alphanumeric, max(250)
     * @label from
     */
    protected $_fromPath;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * @unique
     * 
     * @validate required, alphanumeric, max(250)
     * @label to
     */
    protected $_toPath;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     * @unique
     * 
     * @validate required, alphanumeric, max(30)
     * @label module
     */
    protected $_module;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_modified;

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

}

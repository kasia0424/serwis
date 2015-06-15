<?php
/**
 * Photos model.
 *
 * @author Wanda Sipel <katarzyna.sipel@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/photos/
 * @copyright 2015 EPI
 */

namespace Model;

use Doctrine\DBAL\DBALException;
use Silex\Application;

/**
 * Class PhotosModel.
 *
 * @category Epi
 * @package Model
 * @use Silex\Application
 */
class PhotosModel
{
    /**
     * Db object.
     *
     * @access protected
     * @var Silex\Provider\DoctrineServiceProvider $db
     */
    protected $db;


    /**
     * Object constructor.
     *
     * @access public
     * @param Silex\Application $app Silex application
     */
    public function __construct(Application $app)
    {
        $this->_db = $app['db'];
    }


    /**
     * Save file.
     *
     * @access public
     * @param array $name File namea
     * @retun mixed Result
     */
    public function saveFile($name, $data)
    {
        $sql = 'INSERT INTO `so_photos` (`name`, ad_id) VALUES (?, ?)';
        $this->_db->executeQuery($sql, array($name, $data['ad_id']));
    }


    /**
     * Update file.
     *
     * @access public
     * @param array $name File namea
     * @retun mixed Result
     */
    public function updateFile($name, $data)
    {
        $sql = 'UPDATE `so_photos` SET `name`=? WHERE `ad_id` = ?';
        $this->_db->executeQuery($sql, array($name, $data['ad_id']));
    }


    /**
     * Changes file name
     *
     * @access public
     * @param array $name File name
     * @retun $newName Result
     */
    public function createName($name)
    {
        $newName = '';
        $ext = pathinfo($name, PATHINFO_EXTENSION); //losowy ci¹g z nazwy i rozszerzenia
        $newName = $this->randomString(32) . '.' . $ext;

        while (!$this->isUniqueName($newName)) {
            $newName = $this->randomString(32) . '.' . $ext;
        }

        return $newName;
    }


    /**
     * Creates random string
     *
     * @access protected
     * @param int $length length of string
     * @retun $string Result
     */
    protected function randomString($length) //tworzenie ci¹gu liter i cyfr o zadanej d³
    {
        $string = '';
        $keys = array_merge(range(0, 9), range('a', 'z'));
        for ($i = 0; $i < $length; $i++) {
            $string .= $keys[array_rand($keys)];
        }
        return $string;
    }


    /**
     * Checks if name is unique
     *
     * @access protected
     * @param $name string name
     * @retun boolean Result
     */
    protected function isUniqueName($name) //zwraca true jak ci¹g unikalny
    {
        $sql = 'SELECT COUNT(*) AS files_count FROM so_photos WHERE name = ?';
        $result = $this->_db->fetchAssoc($sql, array($name));
        return !$result['files_count'];
    }


    /**
     * Gets file by id
     *
     * @access public
     * @param $id
     * @return file array Result
     */
    public function getPhoto($id)
    {
        if ($id != '') {
            $sql = 'SELECT name, ad_id FROM so_photos WHERE ad_id =?
            ORDER BY id DESC LIMIT 1';
            $result = $this->_db->fetchAssoc($sql, array((int) $id));
            return $result;
        }
    }
}

<?php
/**
 * Photos model.
 *
 * @author Wanda Sipel <katarzyna.sipel@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/files/
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
    public function saveFile($name, $id)
    {
        $sql = 'INSERT INTO `so_photos` (`name`, user_id) VALUES (?, ?)';
        $this->_db->executeQuery($sql, array($name, $data['user_id']));
        
        // $query = 'SELECT id FROM so_photos
            // WHERE name=?';
        // $result = $this->_db->executeQuery($sql, array($name));
        // //var_dump($result);die();

        // $sql = 'UPDATE so_ads SET photo_id= ? WHERE id = ?';
        // $this->_db->executeQuery($sql, array((int) $result,(int) $id));
    }
    
    
    /**
     * Ads photo to ad
     *
     * @access public
     * @param array $name File namea
     * @retun mixed Result
     */
    public function adPhoto($id)
    {
        $query = 'SELECT id FROM so_photos
            order by id limit 1';
        $result = $this->_db->executeQuery($sql, array($name));
        //var_dump($result);die();

        $sql = 'UPDATE so_photos SET ad_id= ? WHERE id = ?';
        $this->_db->executeQuery($sql, array((int) $id, (int) $result));
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
        $ext = pathinfo($name, PATHINFO_EXTENSION); //losowy ci�g z nazwy i rozszerzenia
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
    protected function randomString($length) //tworzenie ci�gu liter i cyfr o zadanej d�
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
    protected function isUniqueName($name) //zwraca true jak ci�g unikalny
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
    public function getFile($id)
    {
        if ($id != '') {
            $sql = 'SELECT name FROM so_photos WHERE id =?
            ORDER BY id DESC LIMIT 1';
            $result = $this->_db->fetchAssoc($sql, array((int) $id));
            return $result;
        }
    }
}
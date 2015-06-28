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
     * Save image.
     *
     * @access public
     * @param array $image Image data from request
     * @param string $mediaPath Path to media folder on disk
     * @param integer $adId Ad Id
     * @throws \PDOException
     * @return mixed Result
     */
    public function saveImage($image, $mediaPath, $adId)
    {
        try {
            $originalFilename = $image['image']->getClientOriginalName();
            $newFilename = $this->createName($originalFilename);
            $image['image']->move($mediaPath, $newFilename);
            $this->saveFile($newFilename, $adId);
            return true;
        } catch (\PDOException $e) {
            throw $e;
        }
    }


    /**
     * Save filename in database.
     *
     * @access protected
     * @param string $name Filename
     * @param integer $adId Ad Id
     * @return mixed Result
     */
    protected function saveFilename($name, $adId)
    {
        return $this->db->insert('files', array('name' => $name, 'ad_id' => $adId));
    }


    /**
     * Save file.
     *
     * @access public
     * @param array $name File namea
     * @param integer $adId Ad Id
     * @retun mixed Result
     */
    public function saveFile($name, $adId)
    {
        $sql = 'INSERT INTO `so_photos` (`name`, ad_id) VALUES (?, ?)';
        $this->_db->executeQuery($sql, array($name, (int)$adId));
    }

    /**
     * Update image.
     *
     * @access public
     * @param array $image Image data from request
     * @param string $mediaPath Path to media folder on disk
     * @param integer $adId Ad Id
     * @throws \PDOException
     * @return mixed Result
     */
    public function updateImage($image, $mediaPath, $adId)
    {
        try {
            $originalFilename = $image['image']->getClientOriginalName();
            $newFilename = $this->createName($originalFilename);
            $image['image']->move($mediaPath, $newFilename);
            $this->updateFile($newFilename, $adId);
            return true;
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * Update file.
     *
     * @access public
     * @param array $name File name
     * @param integer $adId Ad Id
     * @retun mixed Result
     */
    public function updateFile($name, $adId)
    {
        $sql = 'UPDATE `so_photos` SET `name`=? WHERE `ad_id` = ?';
        $this->_db->executeQuery($sql, array($name, $adId));
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
        $ext = pathinfo($name, PATHINFO_EXTENSION);
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
    protected function randomString($length)
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
    protected function isUniqueName($name)
    {
        $sql = 'SELECT COUNT(*) AS files_count FROM so_photos WHERE name = ?';
        $result = $this->_db->fetchAssoc($sql, array($name));
        return !$result['files_count'];
    }


    /**
     * Gets file by id
     *
     * @access public
     * @param integer $id Record Id
     * @return file array Result
     */
    public function getPhoto($id)
    {
        if ($id != '') {
            $sql = 'SELECT id, name, ad_id FROM so_photos WHERE ad_id =?
            ORDER BY id DESC LIMIT 1';
            $result = $this->_db->fetchAssoc($sql, array((int) $id));
            return $result;
        }
    }
    
    
    /**
     * Deletes photo
     *
     * @access public
     * @param integer $id Record Id
     * @retun mixed Result
     */
    public function deletePhoto($id)
    {
        $id = $id;
        $sql = 'DELETE FROM so_photos WHERE id = ?';
        $this->_db->executeQuery($sql, array((int) $id));
    }
}

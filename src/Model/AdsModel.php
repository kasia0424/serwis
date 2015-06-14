<?php
/**
 * Ads model.
 *
 * @author Wanda Sipel <katarzyna.sipel@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/ads/
 * @copyright 2015 EPI
 */

namespace Model;

use Silex\Application;

/**
 * Class AdsModel.
 *
 * @category Epi
 * @package Model
 * @use Silex\Application
 */
class AdsModel
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
        $this->db = $app['db'];
    }

    /**
     * Gets all ads.
     *
     * @access public
     * @return array Result
     */
    public function getAll()
    {
        $query = 'SELECT so_ads.id, title, text, postDate, category_id, so_categories.name as category, user_id
            FROM so_ads JOIN so_categories ON so_ads.category_id = so_categories.id';
        $result = $this->db->fetchAll($query);
    }


    /**
     * Gets single ads data.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getAd($id)
    {
        if (($id != '') && ctype_digit((string)$id)) {
            $query = 'SELECT so_ads.id, title, text, postDate, photo_id, category_id,
                so_categories.name as category, user_id
                FROM so_ads JOIN so_categories ON so_ads.category_id = so_categories.id
                WHERE so_ads.id= :id';
            $statement = $this->db->prepare($query);
            $statement->bindValue('id', $id, \PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return !$result ? array() : current($result);
        } else {
            return array();
        }
    }


    /**
     * Ads photo to ad
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function adPhoto($id)
    {
        var_dump("ajdik".$id);
        if ($id != '') {
            $query = 'SELECT id FROM so_photos
                ORDER BY id DESC LIMIT 1';
            $result = $this->_db->executeQuery($query);

            $sql = 'UPDATE so_ads SET photo_id= ? WHERE id = ?';
            $this->db->executeQuery($sql, array((int) $result,(int) $id));

        } else {
            return array();
        }
    }


    /**
     * Update ad
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function updateAd($data)
    {
        if ($data['id'] != '') {
            $sql = 'UPDATE so_ads 
                SET title = ?, text = ?, category_id = ?, postDate = ?, user_id = ?
                WHERE id = ?';
            $this->db->executeQuery(
                $sql,
                array(
                    $data['title'],
                    $data['text'],
                    (int) $data['category_id'],
                    $data['postDate'],
                    (int) $data['user_id'],
                    (int) $data['id']
                )
            );
        } else {
            return array();
        }
    }


    /**
     * Save ad.
     *
     * @access public
     * @param array $ad Ads data
     * @retun mixed Result
     */
    public function saveAd($ad)
    {
        if (isset($ad['id'])
            && ($ad['id'] != '')
            && ctype_digit((string)$ad['id'])) {
            // update record
            $id = $ad['id'];
            unset($ad['id']);
            return $this->db->update('so_ads', $ad, array('id' => $id));
        } else {
            // add new record
            return $this->db->insert('so_ads', $ad);
        }
    }


    /**
     * Delete ad
     *
     * @access public
     * @param integer $id Record Id
     */
    public function deleteAd($id)
    {
        $id = $id;
        $sql = 'DELETE FROM so_ads WHERE id = ?';
        $this->db->executeQuery($sql, array((int) $id));
    }
    
    /**
     * Delete users ads
     *
     * @access public
     * @param integer $id Record Id
     */
    public function deleteUsersAds($id)
    {
        $id = $id;
        $sql = 'DELETE FROM so_ads WHERE user_id = ?';
        $this->db->executeQuery($sql, array((int) $id));
    }
    
    //porcjowanie
    /**
     * Get all ads on page.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @retun array Result
     */
    public function getAdsPage($page, $limit)
    {
        $query = 'SELECT so_ads.id, title, text, postDate, category_id, so_categories.name as category, user_id
            FROM so_ads JOIN so_categories ON so_ads.category_id = so_categories.id LIMIT :start, :limit';
        $statement = $this->db->prepare($query);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return !$result ? array() : $result;
    }
    
    /**
     * Counts ad pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @return integer Result
     */
    public function countAdsPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM so_ads';
        $result = $this->db->fetchAssoc($sql);
        if ($result) {
            $pagesCount =  ceil($result['pages_count']/$limit);
        }
        return $pagesCount;
    }

    /**
     * Returns current page number.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $pagesCount Number of all pages
     * @return integer Page number
     */
    public function getCurrentPageNumber($page, $pagesCount)
    {
        return (($page < 1) || ($page > $pagesCount)) ? 1 : $page;
    }
}

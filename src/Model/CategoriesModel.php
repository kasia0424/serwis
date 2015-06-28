<?php
/**
 * Categories model.
 *
 * @author Wanda Sipel <katarzyna.sipel@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/categories/
 * @copyright 2015 EPI
 */

namespace Model;

use Silex\Application;

/**
 * Class CategoriesModel.
 *
 * @category Epi
 * @package Model
 * @use Silex\Application
 */
class CategoriesModel
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
     * Gets all categories.
     *
     * @access public
     * @return array Result
     */
    public function getAll()
    {
        $query = 'SELECT id, name, description FROM so_categories';
        $result = $this->db->fetchAll($query);
        return !$result ? array() : $result;
    }


   /**
     * Gets categories id's and names
     *
     * @access public
     * @return array Result
     */
    public function getCategoriesList()
    {
        $categories = $this->getAll();
        $data = array();
        foreach ($categories as $row) {
            $data[$row['id']] = $row['name'];
        }
        return $data;
    }


    /**
     * Gets single category data.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getCategory($id)
    {
        if ($id != '') {
            $query = 'SELECT id, name, description FROM so_categories WHERE id = ?';
            return $this->db->fetchAssoc($query, array((int) $id));
        } else {
            return array();
        }
    }


    /**
     * Gets ads of category
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getCategoryAds($id)
    {
        if ($id != '') {
            $query = 'SELECT so_ads.id, title, text, postDate, category_id FROM so_ads INNER JOIN so_categories
            ON so_categories.id = so_ads.category_id where so_categories.id = ?';
            return $this->db->fetchAll($query, array((int) $id));
        } else {
            return array();
        }
    }
    
    
    /**
     * Ads ad without category to rest
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function restCategory($id)
    {
        if ($id != '') {
            $sql = 'UPDATE `so_ads` SET `category_id`="19" WHERE `id`= ?;';
            $this->db->executeQuery($sql, array($id));
        } else {
            return array();
        }
    }


    /**
     * Saves category.
     *
     * @access public
     * @param array $category Categories data
     * @retun mixed Result
     */
    public function saveCategory($category)
    {
        if (isset($category['id'])
            && ($category['id'] != '')
            && ctype_digit((string)$category['id'])) {
            // update record
            $id = $category['id'];
            unset($category['id']);
            return $this->db->update('so_categories', $category, array('id' => $id));
        } else {
            // add new record
            return $this->db->insert('so_categories', $category);
        }
    }


    /**
     * Deletes category
     *
     * @access public
     * @param integer $id Record Id
     * @retun mixed Result
     */
    public function deleteCategory($id)
    {
        $id = $id;
        $sql = 'DELETE FROM so_categories WHERE id = ?';
        $this->db->executeQuery($sql, array((int) $id));
    }


    //porcjowanie
    /**
     * Gets all categories on page.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @retun array Result
     */
    public function getCategoriesPage($page, $limit)
    {
        $query = 'SELECT id, name, description FROM so_categories LIMIT :start, :limit';
        $statement = $this->db->prepare($query);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return !$result ? array() : $result;
    }
    
    /**
     * Counts category pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @return integer Result
     */
    public function countCategoriesPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM so_categories';
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
    
    //paginacja do view
    /**
     * Gets all categories on page.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @param integer $id Record Id
     * @retun array Result
     */
    public function getCategoriesAdsPage($page, $limit, $id)
    {
        $query = 'SELECT so_ads.id, title, text, postDate, category_id FROM so_ads INNER JOIN so_categories
            ON so_categories.id = so_ads.category_id where so_categories.id = :id LIMIT :start, :limit';
        $statement = $this->db->prepare($query);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return !$result ? array() : $result;
    }
    
    /**
     * Counts category pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @param integer $id Record Id
     * @return integer Result
     */
    public function countCategoriesAdsPages($limit, $id)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM so_ads INNER JOIN so_categories
            ON so_categories.id = so_ads.category_id where so_categories.id = ?';
        $result = $this->db->fetchAssoc($sql, array((int) $id));
        if ($result) {
            $pagesCount =  ceil($result['pages_count']/$limit);
        }
        return $pagesCount;
    }
}

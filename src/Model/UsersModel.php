<?php
/**
 * Users model.
 *
 * @author Wanda Sipel <katarzyna.sipel@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/users/
 * @copyright 2015 EPI
 */

namespace Model;

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * Class Users.
 *
 * @category Epi
 * @package Model
 * @use Silex\Application
 */
class UsersModel
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
     * Gets all users
     *
     * @access public
     * @return array Result
     */
    public function getAll()
    {
        $query = 'SELECT so_users.id, login, role_id, so_roles.name as role
            FROM so_users JOIN so_roles ON so_users.role_id = so_roles.id';
        $result = $this->db->fetchAll($query);
    }

    
    /**
     * Gets users phone number
     *
     * @access public
     * @param integer $id User Id
     * @return array Result
     */
    public function getPhone($id)
    {
        $query = 'SELECT phone_number FROM so_details WHERE user_id = ?';
        $result = $this->db->fetchAssoc($query, array((int) $id));
        return $result;
    }
    
    /**
     * Updates users phone number
     *
     * @access public
     * @param array $data Form data
     * @return array Result
     */
    public function updatePhone($data)
    {
        $query = 'UPDATE so_details SET phone_number = ? WHERE user_id = ?';
        $this->db->executeQuery(
            $query,
            array(
                $data['phone_number'],
                $data['id'],
            )
        );
    }
    
    
    /**
     * Gets all roles
     *
     * @access public
     * @return array Result
     */
    public function getRoles()
    {
        $query = 'SELECT id, name FROM so_roles';
        $result = $this->db->fetchAll($query);
        return $result;
    }
    
    /**
     * Gets roles id's and names
     *
     * @access public
     * @return array Result
     */
    public function getRolesList()
    {
        $roles = $this->getRoles();
        $data = array();
        foreach ($roles as $row) {
            $data[$row['id']] = $row['name'];
        }
        return $data;
    }


    /**
     * Counts admin users
     *
     * @access public
     * @return array Result
     */
    public function countAdmins()
    {
        $query = 'SELECT
                    COUNT(`role_id`) as admin
                FROM
                    `so_users`
                WHERE role_id = 1';
        $result = $this->db->fetchAssoc($query);
        return $result;
    }

    
    /**
     * Loads user by login.
     *
     * @access public
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        $user = $this->getUserByLogin($login);

        if (!$user || !count($user)) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        $roles = $this->getUserRoles($user['id']);

        if (!$roles || !count($roles)) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        return array(
            'login' => $user['login'],
            'password' => $user['password'],
            'roles' => $roles
        );

    }

    /**
     * Gets user data by login.
     *
     * @access public
     * @param string $login User login
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $query = '
              SELECT
                `id`, `login`, `password`, `role_id`
              FROM
                `so_users`
              WHERE
                `login` = :login
            ';
            $statement = $this->db->prepare($query);
            $statement->bindValue('login', $login, \PDO::PARAM_STR);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return !$result ? array() : current($result);
        } catch (\PDOException $e) {
            return array();
        }
    }

    /**
     * Gets user roles by User ID.
     *
     * @access public
     * @param integer $userId User ID
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = array();
        try {
            $query = '
                SELECT
                    `so_roles`.`name` as `role`
                FROM
                    `so_users`
                INNER JOIN
                    `so_roles`
                ON `so_users`.`role_id` = `so_roles`.`id`
                WHERE
                    `so_users`.`id` = :user_id
                ';
            $statement = $this->db->prepare($query);
            $statement->bindValue('user_id', $userId, \PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($result && count($result)) {
                $result = current($result);
                $roles[] = $result['role'];
            }
            return $roles;
        } catch (\PDOException $e) {
            return $roles;
        }
    }
    
    
    
     /**
     * Add new user
     *
     * @param array $data Form data
     * @param string $password User password
     * @access public
     * @retun mixed Result
     */
    public function addUser($data, $password)
    {
        $query = "INSERT INTO so_users
                  (login, password, role_id)
                  VALUES (?,?, ?)";
        $this->db->executeQuery(
            $query,
            array(
                $data['login'],
                $password,
                $data['role_id'],
            )
        );
    }

    /**
     * Add user's phone number
     *
     * @param array $data Form data
     * @param integer $id User Id
     * @access public
     * @retun mixed Result
     */
    public function addDetails($data, $id)
    {
        $query = "INSERT INTO `so_details` (`user_id`, `phone_number`) VALUES (?, ?)";
        $this->db->executeQuery(
            $query,
            array(
                $id,
                $data['phone_number'],
            )
        );
    }

    /**
     * Updates user
     *
     * @param array $data Form data
     * @param string $password User password
     *
     * @access public
     * @retun mixed Result
     */

    public function saveUser($data, $password)
    {
        if (isset(
            $data['id']) && ctype_digit((string)$data['id'])) {
            $sql = 'UPDATE so_users SET login = ?, password = ? WHERE id = ?';
            $this->db->executeQuery(
                $sql,
                array(
                    $data['login'],
                    $password,
                    $data['id']
                )
            );
        } else {
            $sql = 'INSERT INTO so_users (login, password) VALUES (?,?)';
            $this->db->executeQuery(
                $sql,
                array(
                    $data['login'],
                    $password
                )
            );
        }
    }

    /**
     * Deletes user
     *
     * @param integer $id Record Id
     * @access public
     * @retun mixed Result
     */
    public function deleteUser($id)
    {
        $sql = 'DELETE FROM so_users WHERE id = ?';
        $this->db->executeQuery($sql, array((int) $id));
    }
    
    
    /**
     * Deletes user phone
     *
     * @param integer $id User Id
     * @access public
     * @retun mixed Result
     */
    public function deletePhone($id)
    {
        $sql = 'DELETE FROM so_details WHERE user_id = ?';
        $this->db->executeQuery($sql, array((int) $id));
    }

    /**
     * Gets user by id
     *
     * @access public
     * @param integer $id Record Id
     * @return array users
     *
     */
    public function getUser($id)
    {
        if (($id != '') && ctype_digit((string)$id)) {
            $sql = 'SELECT so_users.id as id, login, password, role_id, so_roles.name as role
                FROM so_users JOIN so_roles ON so_users.role_id = so_roles.id WHERE so_users.id = ?';
            return $this->db->fetchAssoc($sql, array((int) $id));
        } else {
            return array();
        }
    }
    
    /**
     * Gets last added user
     *
     * @access public
     * @return array Result
     */
    public function getLastUser()
    {
        $sql = 'SELECT id
            FROM so_users
            ORDER BY id DESC
            LIMIT 1';
        $result = $this->db->fetchAssoc($sql);
        return $result;
    }


    /**
     * Updates users password
     *
     * @param array $data Form data
     * @param integer $id Record Id
     * @access public
     * @retun mixed Result
     */
    public function changePassword($data, $id)
    {
        $sql = 'UPDATE so_users SET password=? WHERE id= ?';
        $this->db->executeQuery($sql, array($data['new_password'], $id));
    }


    /**
     * Gets users list
     *
     * @access public
     * @return array Result
     */
    public function getUserList()
    {
        $sql = 'SELECT a.id as id, a.login, a.password, a.role_id, so_roles.name as role
            FROM so_users AS a
            JOIN so_roles ON a.role_id = so_roles.id
            ORDER BY a.id';
        $result = $this->db->fetchAll($sql);
        return $result;
    }
    
    
    /**
     * Counts all users ads
     *
     * @access public
     * @return array Result
     */
    public function countUserAds()
    {
        $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS ads_count 
            SELECT DISTINCT COUNT(so_ads.title) as count, so_users.login as login
            FROM so_ads RIGHT JOIN so_users ON so_ads.user_id = so_users.id
            GROUP BY so_users.login';
        $this->db->executeQuery($sql);
        $query = 'SELECT * FROM ads_count';
        $result = $this->db->fetchAll($query);
        return $result;
    }


    /**
     * Gets user information by id
     *
     * @param integer $id Record Id
     *
     * @access public
     * @return array Result
     */
    public function getUserById($id)
    {
        $sql = 'SELECT * FROM so_users WHERE id = ? Limit 1';
        return $this->db->fetchAssoc($sql, array((int)$id));
    }

    /**
     * Changes user's role
     *
     * @param  array $data Form data
     *
     * @access public
     * @retun mixed Result
     */
    public function changeRole($data)
    {
        $sql = 'UPDATE `so_users` SET `role_id`= ? WHERE `id`= ?;';
        $this->db->executeQuery($sql, array($data['role_id'], $data['id']));
    }

    /**
     * Gets current logged user id
     *
     * @param Silex\Application $app Silex application
     *
     * @access public
     * @return mixed
     */
    public function getIdCurrentUser($app)
    {
        $login = $this->getCurrentUser($app);
        $user = $this->getUserByLogin($login);

        return $user['id'];
    }

    /**
     * Gets information about logged user
     *
     * @param Silex\Application $app Silex application
     *
     * @access protected
     * @return mixed
     */
    protected function getCurrentUser($app)
    {
        $token = $app['security']->getToken();
        $roleTab = $token->getRoles();
        $role = $roleTab[0];

        if (null !== $token) {
            if ($role !== null) {
                $user = $token->getUser()->getUsername();
            }
        }
        return $user;
    }


    /**
     * Gets user information by ad id
     *
     * @param integer $id Ad Id
     *
     * @access public
     * @return array Result
     */
    public function getUserByAd($id)
    {
        $sql = 'SELECT login
                FROM so_users JOIN so_ads ON so_users.id=so_ads.user_id WHERE so_ads.id =?';
        return  $this->db->fetchAssoc($sql, array(($id)));
    }



    //paginacja adsów usera na koncie
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

    /**
     * Gets all ads on page.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @param integer $id Record Id
     * @retun array Result
     */
    public function getUsersAdsPage($page, $limit, $id)
    {
        $query = 'SELECT so_ads.id, title, text, postDate, category_id, user_id,
            so_categories.name as category, photo_id
            FROM so_ads INNER JOIN so_categories
            ON so_categories.id = so_ads.category_id where user_id = :id LIMIT :start, :limit';
        $statement = $this->db->prepare($query);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return !$result ? array() : $result;
    }
    
    /**
     * Counts ads pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @param integer $id Record Id
     * @return integer Result
     */
    public function countUsersAdsPages($limit, $id)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM so_ads INNER JOIN so_categories
            ON so_categories.id = so_ads.category_id where user_id = ?';
        $result = $this->db->fetchAssoc($sql, array((int) $id));
        if ($result) {
            $pagesCount =  ceil($result['pages_count']/$limit);
        }
        return $pagesCount;
    }
    
    
    //paginacja userów w panelu
    /**
     * Gets all users on page.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @retun array Result
     */
    public function getUsersPage($page, $limit)
    {
        $query = 'SELECT a.id as id, a.login, a.password, a.role_id, so_roles.name as role
            FROM so_users AS a
            LEFT JOIN so_roles ON a.role_id = so_roles.id
            ORDER BY a.id
            LIMIT :start, :limit';
        $statement = $this->db->prepare($query);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return !$result ? array() : $result;
    }
    
    /**
     * Counts users pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @return integer Result
     */
    public function countUsersPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM so_users';
        $result = $this->db->fetchAssoc($sql);
        if ($result) {
            $pagesCount =  ceil($result['pages_count']/$limit);
        }
        return $pagesCount;
    }
}

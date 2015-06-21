<?php

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20-06-2015
 * Time: 11:19
 */
class DB
{

    protected static $db_link;

    public function connect($host, $user, $pass, $database = null)
    {
        if (!isset(self::$db_link)) {
            self::$db_link = new mysqli($host, $user, $pass);
            if (!self::$db_link) {
                die('Database Connection Failed! :(');
            }
            if ($database != null) {
                self::$db_link->select_db($database) or die("Unable to select database! :(");
            }
        }
    }


    public function query($query)
    {
        $rows = array();
        if (isset(self::$db_link)) {
            $result = self::$db_link->query($query);
            if ($result !== false && is_object($result)) {
                while ($row = $result->fetch_object()) {
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }

    public function insert_into_fixtures($title, $home_team_id, $away_team_id, $venue_id, $date)
    {

        $query = "INSERT INTO fixtures (title, home_team_id, away_team_id, venue_id, date) VALUES(?, ?, ?, ?, ?)";

        $statement = self::$db_link->prepare($query);

        $statement->bind_param('siiis', $title, $home_team_id, $away_team_id, $venue_id, $date);

        if ($statement->execute()) {

        } else {
            die('Error : (' . self::$db_link->errno . ') ' . self::$db_link->error);
        }
        $statement->close();
    }

    public function disconnect()
    {
        if (isset(self::$db_link)) {
            self::$db_link->close();
        }
    }

}
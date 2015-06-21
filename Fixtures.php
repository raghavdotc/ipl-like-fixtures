<?php

require_once 'DB.php';

class Fixtures
{

    protected static $home_team;

    protected static $away_team;

    protected static $venue_id;

    protected static $number_of_teams = 8;

    protected static $today;

    protected static $yesterday;

    protected static $weekend;

    protected static $match_count = 1;

    protected static $teams_participating_datewise = [];

    protected static $eligible_teams_for_today;

    protected static $db_link;

    protected static $teams;

    protected static $home_matches_fixed;

    protected static $fixtures;

    protected static $second_match;

    private $weekends = [6, 7];

    public function set_tournament_starting_date($date)
    {
        self::$today = new DateTime($date);
    }


    public function load_teams()
    {
        $db = new DB();

        $db->connect('localhost', 'root', '', 'ipl');

        self::$teams = $db->query("SELECT * from teams inner join cities on cities.id = teams.home_city_id LIMIT " . self::$number_of_teams);

        $this->index_by_team_id();

    }

    private function obj_fixture($home_team, $away_team, $date_null = true)
    {
        $fixture = new stdClass();
        $fixture->title = $home_team->name . ' - vs - ' . $away_team->name;
        $fixture->home_team_id = $home_team->id;
        $fixture->away_team_id = $away_team->id;
        $fixture->venue_id = $home_team->home_city_id;
        $fixture->date = $date_null ? null : self::$today->format('Y-m-d');
        return $fixture;
    }

    private function index_by_team_id()
    {
        $indexed_teams = [];
        foreach (self::$teams as $team) {
            $team->dated_matches = 0;
            $indexed_teams[$team->id] = $team;
        }
        self::$teams = $indexed_teams;
    }

    private function populate_fixtures_without_dates()
    {
        foreach (self::$teams as $team_key => $team) {

            $other_teams = self::$teams;

            unset($other_teams[$team_key]);

            foreach ($other_teams as $other_team) {

                $participants = [$team->id, $other_team->id];

                $fixture_key = implode('-', $participants);

                if (!isset($team_fixtures[$fixture_key])) {

                    self::$fixtures[$fixture_key] = $this->obj_fixture($team, $other_team);

                }
                array_reverse($participants);

                $fixture_key = implode('-', $participants);

                if (!isset($team_fixtures[$fixture_key])) {

                    self::$fixtures[$fixture_key] = $this->obj_fixture($other_team, $team);

                }
            }
        }
    }

    private function set_eligible_teams_for_match()
    {
        self::$eligible_teams_for_today = self::$teams;
        $today_key = self::$today->format('Y-m-d');
        $yesterday_key = self::$yesterday;
        $dates = [$today_key, $yesterday_key];
        foreach ($dates as $date) {
            if (isset(self::$teams_participating_datewise[$date]) && !empty(self::$teams_participating_datewise[$date])) {
                foreach (self::$teams_participating_datewise[$date] as $team_id) {
                    unset(self::$eligible_teams_for_today[$team_id]);
                }
            }
        }
    }

    private function get_home_team_eligibles($all_teams)
    {
        $eligibles = [];
        foreach ($all_teams as $home_team) {
            if (!isset(self::$home_matches_fixed[$home_team->id]) || count(self::$home_matches_fixed[$home_team->id]) < 7) {
                $eligibles[$home_team->id] = $home_team;
            }
        }
        return $eligibles;
    }

    private function set_home_and_away_teams_for_the_match()
    {
        self::$home_team = null;
        self::$away_team = null;
        if (count(self::$eligible_teams_for_today) < 2) {
            return false;
        }
        $home_team_eligibles = $this->get_home_team_eligibles(self::$eligible_teams_for_today);

        if (count($home_team_eligibles) < 1) {

            return false;

        }

        $trials = 0;

        while (!isset(self::$away_team) && $trials < 7 && isset($home_team_eligibles) && !empty($home_team_eligibles)) {

            self::$home_team = array_rand($home_team_eligibles);

            unset(self::$eligible_teams_for_today[self::$home_team]);

            if (!empty(self::$eligible_teams_for_today)) {

                foreach (self::$eligible_teams_for_today as $away_team) {

                    if (!isset(self::$home_matches_fixed[self::$home_team][$away_team->id])) {

                        self::$away_team = $away_team->id;

                        break;
                    }

                }

            }
            if (!self::$away_team) {
                unset($home_team_eligibles[self::$away_team]);
            }
            $trials++;
        };

        if (!self::$away_team) {

            return false;

        }

        self::$venue_id = self::$teams[self::$home_team]->home_city_id;

        self::$home_matches_fixed[self::$home_team][self::$away_team] = true;

        return true;
    }

    private function save_fixture()
    {

        $fixture_key = self::$home_team . '-' . self::$away_team;

        if (!isset(self::$fixtures[$fixture_key])) {
            $break_here = "";
        }

        self::$fixtures[$fixture_key]->date = self::$today;

        $fixture = self::$fixtures[$fixture_key];

        $db = new DB();

        $db->connect('localhost', 'root', '', 'ipl');

        $db->insert_into_fixtures($fixture->title, $fixture->home_team_id, $fixture->away_team_id, $fixture->venue_id, $fixture->date->format('Y-m-d'));

        print self::$match_count . ' ==> ' . $fixture->date->format('d-M-Y, l') . " ===> ";

        print $fixture->title . " \n";

        self::$match_count++;

        $this->populate_teams_participating_datewise();

    }

    private function prepare_for_next_fixture()
    {
        $day_of_week = self::$today->format("N");
        if (in_array($day_of_week, $this->weekends)) {
            if (self::$second_match) {
                $this->advance_date();
            } else {
                self::$second_match = true;
                $this->set_eligible_teams_for_match();
                if (count(self::$eligible_teams_for_today) < 2) {
                    $this->advance_date();
                }
            }
        } else {
            $this->advance_date();
        }
    }

    private function advance_date()
    {
        self::$second_match = false;
        self::$yesterday = self::$today->format('Y-m-d');
        self::$today->add(new DateInterval('P1D'));
    }

    private function delete_from_fixtures()
    {
        $db = new DB();

        $db->connect('localhost', 'root', '', 'ipl');

        $db->query('DELETE from fixtures');

    }

    private function populate_yesterdays_teams()
    {
        self::$yesterdays_teams[self::$yesterday][] = self::$home_team;
        self::$yesterdays_teams[self::$yesterday][] = self::$away_team;
    }

    private function populate_teams_participating_datewise()
    {
        $date_key = self::$today->format('Y-m-d');
        self::$teams_participating_datewise[$date_key][] = self::$home_team;
        self::$teams_participating_datewise[$date_key][] = self::$away_team;
    }

    public function do_fixtures()
    {

        $this->delete_from_fixtures();

        $this->populate_fixtures_without_dates();

        while (self::$match_count <= 56) {

            $this->set_eligible_teams_for_match();

            if ($this->set_home_and_away_teams_for_the_match()) {

                $this->save_fixture();

                $this->prepare_for_next_fixture();

            } else {
                $this->advance_date();
            }
        }


    }

    public function roundrobin_fixtures()
    {



    }


}
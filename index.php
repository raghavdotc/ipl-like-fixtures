<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20-06-2015
 * Time: 11:18
 */
require_once 'DB.php';

define('NUMBER_OF_TEAMS', 8);

$db = new DB();

$db->connect('localhost', 'root', '', 'ipl');

list($success, $teams, $message) = $db->query("SELECT * from teams inner join cities on cities.id = teams.home_city_id LIMIT " . NUMBER_OF_TEAMS);

$indexed_teams = reindex_teams($teams);

$team_fixtures = get_team_fixtures_array($indexed_teams);

assign_dates_to_team_fixtures($team_fixtures, $indexed_teams);

$final_fixtures = assort_fixtures_datewise($team_fixtures);

insert_final_fixtures_to_db($db, $final_fixtures);


function insert_final_fixtures_to_db($db, $final_fixtures)
{
    $db->query('delete from fixtures');
    foreach ($final_fixtures as $single_day_fixtures) {
        foreach ($single_day_fixtures as $match) {
//            $db->query("INSERT INTO fixtures (home_team_id,away_team_id,venue_id,date)
//values({$match['home_team_id']},{$match['home_team_id']},{$match['home_team_id']},{$match['venue_id']},'{$match['date']}')");
        }
    }
}


function assort_fixtures_datewise($team_fixtures)
{
    $final_fixtures = [];
    $count = 0;
    foreach ($team_fixtures as $team_fixture) {
        $final_fixtures[$team_fixture['date']] = $team_fixture;
    }
    ksort($final_fixtures);
    return $final_fixtures;
}

function get_eligible_teams_for_the_day($all_teams, $previous_day_participants)
{
    $max_matches_per_team = (NUMBER_OF_TEAMS - 1) * 2;
    foreach ($all_teams as $team_id => $team) {
        if ($team->dated_matches >= $max_matches_per_team) {
            unset($all_teams[$team_id]);
        }
    }
    if (!empty($previous_day_participants)) {
        foreach ($previous_day_participants as $previous_day_participant) {
            if (isset($all_teams[$previous_day_participant])) {
                unset($all_teams[$previous_day_participant]);
            }
        }
    }
    return $all_teams;
}

function reindex_teams(&$teams)
{
    foreach ($teams as $team) {
        $team->dated_matches = 0;
        $indexed_teams[$team->id] = $team;
    }
    return $indexed_teams;
}

function get_team_fixtures_array($all_teams)
{
    foreach ($all_teams as $team_key => $team) {
        $other_teams = $all_teams;
        unset($other_teams[$team_key]);
        foreach ($other_teams as $other_team) {
            $participants = [$team->id, $other_team->id];
            $fixture_key = implode('-', $participants);
            if (!isset($team_fixtures[$fixture_key])) {
                $team_fixtures[$fixture_key] = [
                    'home_team_id' => $team->id,
                    'away_team_id' => $other_team->id,
                    'venue_id' => $team->home_city_id,
                    'venue' => $team->city,
                    'date' => null
                ];
            }
            array_reverse($participants);
            $fixture_key = implode('-', $participants);
            if (!isset($team_fixtures[$fixture_key])) {
                $team_fixtures[$fixture_key] = [
                    'home_team_id' => $other_team->id,
                    'away_team_id' => $team->id,
                    'venue_id' => $other_team->home_city_id,
                    'venue' => $other_team->city,
                    'date' => null
                ];
            }
        }
    }
    return $team_fixtures;
}


function assign_dates_to_team_fixtures(&$team_fixtures, &$teams)
{
    $amatch_count = 0;
    $participants_today = [];
    $combinations_to_ignore = [];
    $previous_day_participants = [];
    $today = new DateTime("now");
    $first_match_of_the_day = true;
    while ($amatch_count < count($team_fixtures)) {
        $participants = [];
        $day = $today->format("N");
        do {
            $eligible_teams = get_eligible_teams_for_the_day($teams, $previous_day_participants);
            if (count($eligible_teams) > 1) {
                $participant1 = array_rand($eligible_teams);
                $participant2 = pseudo_random_participating_team($eligible_teams, $participant1, $combinations_to_ignore);
                if ($participant1 && $participant2) {
                    $participants = [$participant1, $participant2];
                } else {
                    if (in_array($day, [6, 7])) {
                        if ($first_match_of_the_day) {
                            $previous_day_participants = array_merge($previous_day_participants, $participants_today);
                        } else {
                            $previous_day_participants = $participants_today;
                            $today->add(new DateInterval('P1D'));
                        }
                    } else {
                        $previous_day_participants = $participants;
                        $today->add(new DateInterval('P1D'));
                        $first_match_of_the_day = true;
                    }
                }
            } else {
                $previous_day_participants = [];
            }
        } while (empty($participants));

        if (in_array($day, [6, 7])) {
            if ($first_match_of_the_day) {
                $participants_today = $participants;
            } else {
                $participants_today = array_merge($participants_today, $participants);
            }
        }

        $fixture_key = implode('-', $participants);
        $team_fixtures[$fixture_key]['date'] = $today->format('Y-m-d');
        $combinations_to_ignore[$participants[0]][] = $participants[1];
        $teams[$participants[0]]->dated_matches++;
        $teams[$participants[1]]->dated_matches++;
        print ++$amatch_count . " ==> " . $today->format('d-M-Y, l') . " ==> " . $teams[$participants[0]]->name . ' -vs- ' . $teams[$participants[1]]->name . "\n";

        if (in_array($day, [6, 7])) {
            if ($first_match_of_the_day) {
                $previous_day_participants = array_merge($previous_day_participants, $participants_today);
                $first_match_of_the_day = false;
            } else {
                $previous_day_participants = $participants_today;
                $today->add(new DateInterval('P1D'));
                $first_match_of_the_day = true;
            }
        } else {
            $previous_day_participants = $participants_today;
            $today->add(new DateInterval('P1D'));
            $first_match_of_the_day = true;
        }

    }
}

function pseudo_random_participating_team($eligible_teams, $participant, $combinations_to_ignore)
{
    if (isset($eligible_teams[$participant])) {
        unset($eligible_teams[$participant]);
    }
    if (!empty($combinations_to_ignore[$participant])) {
        foreach ($eligible_teams as $team_id => $team) {
            if (in_array($team_id, $combinations_to_ignore[$participant])) {
                unset($eligible_teams[$team_id]);
            }
        }
    }
    return !empty($eligible_teams) ? array_rand($eligible_teams) : false;
}
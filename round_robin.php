<?php
/**
 * Created by PhpStorm.
 * User: Raghav
 * Date: 21-06-2015
 * Time: 18:49
 */

require_once 'Fixtures.php';

$fixtures = new Fixtures();

$fixtures->set_tournament_starting_date("now");

$fixtures->load_teams();

$fixtures->roundrobin_fixtures();
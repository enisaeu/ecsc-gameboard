<?php
    if (endsWith(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/scores.json")) {
        $callback = isset($_GET["callback"]) ? $_GET["callback"] : null;

        if (is_null($callback))
            header("Content-Type: application/json");
        else
            header("Content-Type: application/javascript");

        if (file_exists("scores.json")) {
            $_ = file_get_contents("scores.json");
            $result = json_decode($_, true);
        }
        else {
            $teams = getRankedTeams();
            $previous = -1;
            $place = 0;

            $result = array();
            foreach ($teams as $team_id) {
                $row = fetchAll("SELECT * FROM teams WHERE team_id=:team_id", array("team_id" => $team_id))[0];
                $scores = getScores($team_id);

                if ($scores["cash"] !== $previous) {
                    $place += 1;
                    $previous = $scores["cash"];
                }

                $_ = array("name" => $row["full_name"], "code" => $row["country_code"], "country" => array_key_exists($row["country_code"], COUNTRIES) ? COUNTRIES[$row["country_code"]] : "", "score" => $scores["cash"], "place" => $place);
                array_push($result, $_);
            }
        }

        $result = array_filter($result, function($value) {
            return !(($value["score"] === 0) && is_null($value["country"]));
        });

        if (is_null($callback))
            die(json_encode($result, JSON_PRETTY_PRINT));
        else
            die($callback . '(' . json_encode($result) . ');');
    }

    if (endsWith(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/scores.xml")) {
        header("Content-Type: application/xml; charset=utf-8");

        $teams = getRankedTeams();
        $previous = -1;
        $place = 0;

        $result = array();
        foreach ($teams as $team_id) {
            $row = fetchAll("SELECT * FROM teams WHERE team_id=:team_id", array("team_id" => $team_id))[0];
            $scores = getScores($team_id);

            if ($scores["cash"] !== $previous) {
                $place += 1;
                $previous = $scores["cash"];
            }

            $_ = array("name" => $row["full_name"], "code" => $row["country_code"], "country" => array_key_exists($row["country_code"], COUNTRIES) ? COUNTRIES[$row["country_code"]] : "", "score" => $scores["cash"], "place" => $place);
            array_push($result, $_);
        }

        $result = array_filter($result, function($value) {
            return !(($value["score"] === 0) && is_null($value["country"]));
        });

        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><scoreboard/>");
        foreach($result as $_) {
            $entry = $xml->addChild("entry");
            $entry->addAttribute("name", $_["name"]);
            $entry->addAttribute("code", $_["code"]);
            $entry->addAttribute("country", $_["country"]);
            $entry->addAttribute("score", $_["score"]);
            $entry->addAttribute("place", $_["place"]);
        }

        die($xml->asXML());
    }
?>
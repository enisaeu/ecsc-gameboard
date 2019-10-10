<?php
    require_once("common.php");

    $callback = isset($_GET["callback"]) ? preg_replace("/[^A-Za-z0-9 ]/", '', $_GET["callback"]) : null;

    if (endsWith(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/scores.json")) {
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

            $result = array_filter($result, function($value) {
                return !(($value["score"] === 0) && is_null($value["country"]));
            });
        }

        if (is_null($callback))
            die(json_encode($result, JSON_PRETTY_PRINT));
        else
            die($callback . '(' . json_encode($result) . ');');
    }

    else if (endsWith(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/scores.xml")) {
        header("Content-Type: application/xml; charset=utf-8");

        if (file_exists("scores.xml"))
            $output = file_get_contents("scores.xml");
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

            $result = array_filter($result, function($value) {
                return !(($value["score"] === 0) && is_null($value["country"]));
            });

            $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><scoreboard/>");
            foreach($result as $_) {
                $entry = $xml->addChild("entry");
                $entry->addAttribute("name", $_["name"]);
                $entry->addAttribute("code", $_["code"]);
                $entry->addAttribute("country", $_["country"]);
                $entry->addAttribute("score", (string) $_["score"]);
                $entry->addAttribute("place", (string) $_["place"]);
            }

    //         die($xml->asXML());

            # Reference: https://stackoverflow.com/a/17948879
            $domxml = new DOMDocument("1.0");
            $domxml->preserveWhiteSpace = false;
            $domxml->formatOutput = true;
            $domxml->loadXML($xml->asXML());
            $output = $domxml->saveXML();
        }

        die($output);
    }

    else if (endsWith(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/stats.json")) {
        if (is_null($callback))
            header("Content-Type: application/json");
        else
            header("Content-Type: application/javascript");

        if (file_exists("stats.json")) {
            $_ = file_get_contents("stats.json");
            $result = json_decode($_, true);
        }
        else {
            $result = array();

            $average = array();
            $rows = fetchAll("SELECT solved.task_id,tasks.contract_id,AVG(TIMESTAMPDIFF(SECOND,accepted.ts,solved.ts)) AS average_time FROM solved JOIN tasks ON solved.task_id=tasks.task_id JOIN accepted ON tasks.contract_id=accepted.contract_id AND accepted.team_id=solved.team_id GROUP BY task_id");
            foreach ($rows as $row) {
                $average[$row["task_id"]] = $row["average_time"];
            }

            if (isAdmin())
                $rows = fetchAll("SELECT tasks.task_id,GROUP_CONCAT(teams.login_name) AS solved_by,COUNT(teams.login_name) AS solved_count,tasks.title AS task_title,cash AS task_cash,tasks.contract_id,contracts.title AS contract_title FROM tasks LEFT JOIN solved ON tasks.task_id=solved.task_id LEFT JOIN teams ON solved.team_id=teams.team_id LEFT JOIN contracts ON tasks.contract_id=contracts.contract_id GROUP BY tasks.task_id ORDER BY solved_count DESC, task_cash ASC");
            else
                $rows = fetchAll("SELECT tasks.task_id,GROUP_CONCAT(teams.login_name) AS solved_by,COUNT(teams.login_name) AS solved_count,tasks.title AS task_title,cash AS task_cash,tasks.contract_id,contracts.title AS contract_title FROM tasks LEFT JOIN solved ON tasks.task_id=solved.task_id LEFT JOIN teams ON solved.team_id=teams.team_id LEFT JOIN contracts ON tasks.contract_id=contracts.contract_id WHERE contracts.hidden IS NOT TRUE GROUP BY tasks.task_id ORDER BY solved_count DESC, task_cash ASC");

            foreach ($rows as $row) {
                $_ = array("task" => $row["task_title"], "contract" => $row["contract_title"], "cash" => intval($row["task_cash"]), "solved_by" => ($row["solved_count"] > 0 ? explode(',', $row["solved_by"]) : []), "average_time" => isset($average[$row["task_id"]]) ? secondsToTime($average[$row["task_id"]]) : null);
                array_push($result, $_);
            }
        }

        if (is_null($callback))
            die(json_encode($result, JSON_PRETTY_PRINT));
        else
            die($callback . '(' . json_encode($result) . ');');
    }

    else if (endsWith(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/stats.xml")) {
        header("Content-Type: application/xml; charset=utf-8");

        if (file_exists("stats.xml"))
            $output = file_get_contents("stats.xml");
        else {
            $result = array();

            $average = array();
            $rows = fetchAll("SELECT solved.task_id,tasks.contract_id,AVG(TIMESTAMPDIFF(SECOND,accepted.ts,solved.ts)) AS average_time FROM solved JOIN tasks ON solved.task_id=tasks.task_id JOIN accepted ON tasks.contract_id=accepted.contract_id AND accepted.team_id=solved.team_id GROUP BY task_id");
            foreach ($rows as $row) {
                $average[$row["task_id"]] = $row["average_time"];
            }

            if (isAdmin())
                $rows = fetchAll("SELECT tasks.task_id,GROUP_CONCAT(teams.login_name) AS solved_by,COUNT(teams.login_name) AS solved_count,tasks.title AS task_title,cash AS task_cash,tasks.contract_id,contracts.title AS contract_title FROM tasks LEFT JOIN solved ON tasks.task_id=solved.task_id LEFT JOIN teams ON solved.team_id=teams.team_id LEFT JOIN contracts ON tasks.contract_id=contracts.contract_id GROUP BY tasks.task_id ORDER BY solved_count DESC, task_cash ASC");
            else
                $rows = fetchAll("SELECT tasks.task_id,GROUP_CONCAT(teams.login_name) AS solved_by,COUNT(teams.login_name) AS solved_count,tasks.title AS task_title,cash AS task_cash,tasks.contract_id,contracts.title AS contract_title FROM tasks LEFT JOIN solved ON tasks.task_id=solved.task_id LEFT JOIN teams ON solved.team_id=teams.team_id LEFT JOIN contracts ON tasks.contract_id=contracts.contract_id WHERE contracts.hidden IS NOT TRUE GROUP BY tasks.task_id ORDER BY solved_count DESC, task_cash ASC");

            foreach ($rows as $row) {
                $_ = array("task" => $row["task_title"], "contract" => $row["contract_title"], "cash" => intval($row["task_cash"]), "solved_by" => ($row["solved_count"] > 0 ? explode(',', $row["solved_by"]) : []), "average_time" => isset($average[$row["task_id"]]) ? secondsToTime($average[$row["task_id"]]) : null);
                array_push($result, $_);
            }

            $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><scoreboard/>");
            foreach($result as $_) {
                $entry = $xml->addChild("entry");
                $entry->addAttribute("task", $_["task"]);
                $entry->addAttribute("contract", $_["contract"]);
                $entry->addAttribute("cash", (string) $_["cash"]);
                $entry->addAttribute("solved_by", implode(',', $_["solved_by"]));
                $entry->addAttribute("average_time", $_["average_time"]);
            }

    //         die($xml->asXML());

            # Reference: https://stackoverflow.com/a/17948879
            $domxml = new DOMDocument("1.0");
            $domxml->preserveWhiteSpace = false;
            $domxml->formatOutput = true;
            $domxml->loadXML($xml->asXML());
            $output = $domxml->saveXML();
        }

        die($output);
    }
?>

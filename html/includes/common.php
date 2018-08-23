<?php
    define("DEBUG", file_exists("../.debug"));
    define("MYSQL_SERVER", "localhost");
    define("MYSQL_USERNAME", "ecsc");
    define("MYSQL_PASSWORD", "<blank>");
    define("MYSQL_DATABASE", "ecsc");
    define("PATHDIR", dirname(htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8")));
    define("ANSWER_TIME_LIMIT", 10);
    define("MOMENTUM_STEPS", 30);
    define("ADMIN_LOGIN_NAME", "admin");
    define("TITLE", "ECSC " . date("Y"));
    define("CHAT_FILEPATH", "/var/run/shm/chat.htm");

    session_start();

    if (!isset($_SESSION["token"]))
        $_SESSION["token"] = preg_replace('/[^0-9a-zA-Z]/', '', base64_encode(openssl_random_pseudo_bytes(32)));

    abstract class NotificationCategories
    {
        const everybody = "light";
        const finished_contract = "success";
        const sent_private = "info";
        const received_private = "primary";
    }

    // PHP5 compatibility (can't use arrays in defines)
    const PREDEFINED_COLORS = array("Offensive" => "0078c4", "Web" => "41c4dc", "Crypto" => "8fc33f", "Reverse-engineering" => "fedd00", "Forensics" => "f8972a", "Networking" => "e3124f", "Multiple options" => "b01c91");

    if (DEBUG) {
        ini_set("display_startup_errors", 1);
        ini_set("display_errors", 1);
        error_reporting(-1);
    }

    $conn = new PDO("mysql:host=" . MYSQL_SERVER . ";dbname=" . MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $_SESSION["conn_error"] = "";

    function isAdmin() {
        return (isset($_SESSION["login_name"]) && ($_SESSION["login_name"] === ADMIN_LOGIN_NAME));
    }

    $VALID_PAGES = isAdmin() ? array("teams", "contracts", "notifications") : array("rankings", "jobboard", "contracts", "notifications");

    function getLastUpdateTimestamp() {
        return fetchScalar("SELECT last_update()");
    }

    function format($str, $args) {
        foreach ($args as $key => $value) {
            $str = str_replace('{' . $key . '}', $value, $str);
        }
        return $str;
    }

    function generateValuesHtml($cash, $awareness) {
        if (is_numeric($cash))
            $result = format('<span class="badge {appearance} border mr-2">&euro; {cash}</span>', array("cash" => number_format($cash), "appearance" => ($cash > 0 ? "badge-success" : ($cash < 0 ? "badge-danger" : "badge-light"))));
        else
            $result = format('<span class="badge {appearance} border mr-2">&euro; {cash}</span>', array("cash" => $cash, "appearance" => "badge-light"));
        if (is_numeric($awareness))
            $result .= format('<span class="badge {appearance} border mr-2">{awareness} <i class="fas fa-eye"></i></span>', array("awareness" => number_format($awareness), "appearance" => ($awareness > 0 ? "badge-success" : ($awareness < 0 ? "badge-danger" : "badge-light"))));
        else
            $result .= format('<span class="badge {appearance} border mr-2">{awareness} <i class="fas fa-eye"></i></span>', array("awareness" => $awareness, "appearance" => "badge-light"));
        return $result;
    }

    function getFinishedContracts($team_id) {
        $result = array();
        $solved = fetchAll("SELECT COUNT(*) AS total, contracts.contract_id AS contract_id FROM contracts JOIN (teams, solved, tasks) ON tasks.task_id=solved.task_id AND contracts.contract_id=tasks.contract_id AND solved.team_id=teams.team_id WHERE teams.team_id=:team_id GROUP BY(contracts.contract_id)", array("team_id" => $team_id));

        foreach ($solved as $row) {
            if ($row["total"] == fetchScalar("SELECT COUNT(*) FROM tasks WHERE contract_id=:contract_id", array("contract_id" => $row["contract_id"])))
                array_push($result, $row["contract_id"]);
        }

        return $result;
    }

    function getSolvedTasks($team_id) {
        return fetchAll("SELECT task_id FROM solved WHERE team_id=:team_id ORDER BY ts ASC", array("team_id" => $team_id), PDO::FETCH_COLUMN);
    }

    function getTimeStatus($team_id, $ts) {
        $result = array();
        $row = fetchAll("SELECT SUM(cash) AS cash, SUM(awareness) AS awareness FROM solved JOIN tasks ON solved.task_id=tasks.task_id WHERE team_id=:team_id AND UNIX_TIMESTAMP(solved.ts)<=:ts", array("team_id" => $team_id, "ts" => $ts))[0];
        $result["cash"] = floatval($row["cash"]);
        $result["awareness"] = floatval($row["awareness"]);

        $_ = fetchScalar("SELECT SUM(cash) FROM privates WHERE from_id=:team_id AND UNIX_TIMESTAMP(ts)<=:ts", array("team_id" => $team_id, "ts" => $ts));
        $result["cash"] -= is_null($_) ? 0 : $_;

        $_ = fetchScalar("SELECT SUM(cash) FROM privates WHERE to_id=:team_id AND UNIX_TIMESTAMP(ts)<=:ts", array("team_id" => $team_id, "ts" => $ts));
        $result["cash"] += is_null($_) ? 0 : $_;

        return $result;
    }

    function getMomentum_new($team_id=null) {
        $result = array();
        $teams = is_null($team_id) ? getRankedTeams() : array($team_id);

        foreach ($teams as $team_id) {
            $team_name = fetchScalar("SELECT full_name FROM teams WHERE team_id=:team_id", array("team_id" => $team_id));
            $result[$team_name] = array("cash" => array(), "awareness" => array());

            $first = true;
            $timestamps = fetchAll("SELECT ts FROM (SELECT UNIX_TIMESTAMP(ts) AS ts FROM solved WHERE team_id=:team_id UNION ALL SELECT UNIX_TIMESTAMP(ts) AS ts FROM privates WHERE cash IS NOT NULL AND (from_id=:team_id OR to_id=:team_id)) AS result ORDER BY ts ASC", array("team_id" => $team_id), PDO::FETCH_COLUMN);
            foreach ($timestamps as $timestamp) {
                $current = getTimeStatus($team_id, $timestamp);
                if ($first) {
                    array_push($result[$team_name]["cash"], array("x" => intval($timestamp) - 1, "y" => 0));
                    array_push($result[$team_name]["awareness"], array("x" => intval($timestamp) - 1, "y" => 0));
                    $first = false;
                }
                array_push($result[$team_name]["cash"], array("x" => intval($timestamp), "y" => $current["cash"]));
                array_push($result[$team_name]["awareness"], array("x" => intval($timestamp), "y" => $current["awareness"]));
            }
        }

        return $result;
    }

    function getMomentum($team_id=null) {
        $min = fetchScalar("SELECT IFNULL(UNIX_TIMESTAMP(MIN(ts)), UNIX_TIMESTAMP()) FROM accepted");
        $max = 1 + fetchScalar("SELECT UNIX_TIMESTAMP()"); // +1 because of send cash + refresh quirk
        $n = MOMENTUM_STEPS;
        $step = ($max - $min) / $n;
        $result = array();

        $teams = is_null($team_id) ? getRankedTeams() : array($team_id);

        foreach ($teams as $team_id) {
            $team_name = fetchScalar("SELECT full_name FROM teams WHERE team_id=:team_id", array("team_id" => $team_id));
            $result[$team_name] = array("cash" => array(), "awareness" => array());

            array_push($result[$team_name]["cash"], array("x" => intval($min), "y" => 0));
            array_push($result[$team_name]["awareness"], array("x" => intval($min), "y" => 0));

            if ($step) {
                $timestamp = (($min - 1) / $step) * $step;

                while ($timestamp <= $max) {
                    $current = getTimeStatus($team_id, $timestamp);
                    array_push($result[$team_name]["cash"], array("x" => intval($timestamp), "y" => $current["cash"]));
                    array_push($result[$team_name]["awareness"], array("x" => intval($timestamp), "y" => $current["awareness"]));
                    $timestamp += $step;
                }
            }
            else {
                array_push($result[$team_name]["cash"], array("x" => intval($max), "y" => 0));
                array_push($result[$team_name]["awareness"], array("x" => intval($max), "y" => 0));
            }
        }

        return $result;
    }

    function getConstraintedContracts($team_id) {
        $result = array();
        $constraints = fetchAll("SELECT * FROM constraints");

        if (count($constraints) > 0) {
            $scores = getScores($team_id);
            foreach ($constraints as $constraint) {
                if (!is_null($constraint["min_cash"]) && ($constraint["min_cash"] > $scores["cash"]))
                    array_push($result, $constraint["contract_id"]);
                else if (!is_null($constraint["min_awareness"]) && ($constraint["min_awareness"] > $scores["awareness"]))
                    array_push($result, $constraint["contract_id"]);
            }
        }

        return $result;
    }

    function getNotifications($team_id) {
        return fetchAll("SELECT notification_id FROM notifications WHERE team_id IS NULL OR team_id=:team_id ORDER BY notification_id DESC", array("team_id" => $team_id), PDO::FETCH_COLUMN);
    }

    function getVisibleNotifications($team_id) {
        $notifications = getNotifications($team_id);
        $hidden = fetchAll("SELECT notification_id FROM hide WHERE team_id=:team_id", array("team_id" => $team_id), PDO::FETCH_COLUMN);
        return array_diff($notifications, $hidden);
    }

    function getAllContracts() {
        return fetchAll("SELECT contract_id FROM contracts", null, PDO::FETCH_COLUMN);
    }

    function getAvailableContracts($team_id) {
        $_ = getFinishedContracts($team_id);
        $__ = getActiveContracts($team_id);
        $___ = getConstraintedContracts($team_id);
        $____ = fetchAll("SELECT contract_id FROM contracts", null, PDO::FETCH_COLUMN);
        return array_diff($____, $___, $__, $_);
    }

    function getScores($team_id) {
        $result = array("cash" => 0, "awareness" => 0);
        $_ = getSolvedTasks($team_id);
        if (count($_) > 0) {
            foreach ($_ as $task_id) {
                $task = fetchAll("SELECT * FROM tasks WHERE task_id=:task_id", array("task_id" => $task_id))[0];
                $result["cash"] += floatval($task["cash"]);
                $result["awareness"] += floatval($task["awareness"]);
            }
        }

        $_ = fetchScalar("SELECT SUM(cash) FROM privates WHERE from_id=:team_id", array("team_id" => $team_id));
        $result["cash"] -= is_null($_) ? 0 : $_;

        $_ = fetchScalar("SELECT SUM(cash) FROM privates WHERE to_id=:team_id", array("team_id" => $team_id));
        $result["cash"] += is_null($_) ? 0 : $_;

        return $result;
    }

    function getRankedTeams() {
        $result = array();
        $teams = getTeams();

        $rankings = array();
        foreach ($teams as $team_id) {
            $scores = getScores($team_id);
            $row = fetchAll("SELECT full_name FROM teams WHERE team_id=:team_id", array("team_id" => $team_id))[0];
            $ranking = array("team_id" => $team_id, "full_name" => $row["full_name"], "cash" => $scores["cash"], "awareness" => $scores["awareness"]);
            array_push($rankings, $ranking);
        }

        usort($rankings, function ($team1, $team2) {
            if ($team1["cash"] < $team2["cash"])
                return 1;
            else if ($team1["cash"] > $team2["cash"])
                return -1;
            else if ($team1["awareness"] < $team2["awareness"])
                return 1;
            else if ($team1["awareness"] > $team2["awareness"])
                return -1;
            else if ($team1["full_name"] < $team2["full_name"])
                return -1;
            else if ($team1["full_name"] > $team2["full_name"])
                return 1;
            else
                return 0;
        });

        foreach ($rankings as $ranking)
            array_push($result, $ranking["team_id"]);

        return $result;
    }

    function getPlaces($team_id) {
        $result = array();
        $teams = getTeams();
        $all = array("cash" => array(), "awareness" => array());

        foreach ($teams as $_) {
            $current = getScores($_);
            array_push($all["cash"], $current["cash"]);
            array_push($all["awareness"], $current["awareness"]);
        }

        $current = getScores($team_id);
        foreach ($all as $key => $_) {
            $previous = -1;
            $place = 0;
            rsort($all[$key]);
            foreach ($all[$key] as $_) {
                if ($_ !== $previous) {
                    $place += 1;
                    $previous = $_;
                }
                if ($current[$key] === $_)
                    break;
            }
            $result[$key] = $place;
        }

        return $result;
    }

    function getTeams() {
        $result = array();
        $_ = fetchAll("SELECT team_id FROM teams WHERE login_name!=:admin_login_name ORDER BY team_id ASC", array("admin_login_name" => ADMIN_LOGIN_NAME), PDO::FETCH_COLUMN);
        foreach ($_ as $team_id)
            array_push($result, $team_id);
        return $result;
    }

    function getActiveContracts($team_id) {
        $_ = getFinishedContracts($team_id);
        $__ = fetchAll("SELECT contract_id FROM accepted WHERE team_id=:team_id", array("team_id" => $team_id), PDO::FETCH_COLUMN);
        return array_diff($__, $_);
    }

    function getContrastYIQ($hexcolor) {
        if ($hexcolor[0] === "#")
            $hexcolor = substr($hexcolor, 1);

        $r = hexdec(substr($hexcolor, 0, 2));
        $g = hexdec(substr($hexcolor, 2, 2));
        $b = hexdec(substr($hexcolor, 4, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return ($yiq >= 128) ? "black" : "white";
    }

    function generateCategoriesHtml($categories) {
        $result = "";

        foreach ($categories as $category) {
            $color = array_key_exists($category, PREDEFINED_COLORS) ? PREDEFINED_COLORS[$category] : substr(md5($category), 0, 6);
            $result .= '<span class="badge badge-light border mr-1" style="color:' . getContrastYIQ($color) . '; background-color: #' . $color . '">' . $category . '</span>';
        }
        return $result;
    }

    function fetchScalar($sql, $args=null) {
        $row = fetchAll($sql, $args, PDO::FETCH_COLUMN);

        if ($row)
            return $row[0];
        else
            return null;
    }

    function fetchAll($sql, $args=null, $fetch_style=PDO::FETCH_BOTH) {
        global $conn;

        $stmt = $conn->prepare($sql);

        if ($args !== null)
            foreach ($args as $key => $value) {
                $key = ":" . $key;
                $stmt->bindValue($key, $value); 
            }

        $stmt->execute();

        if ($stmt->errorCode() != 0) {
            $_SESSION["conn_error"] .= $stmt->errorInfo()[2] . "\n";
            return null;
        }
        else
            return $stmt->fetchAll($fetch_style);
    }

    function execute($sql, $args=null) {
        global $conn;

        $retval = true;
        $stmt = $conn->prepare($sql);

        if ($args !== null)
            foreach ($args as $key => $value) {
                $key = ":" . $key;
                $stmt->bindValue($key, $value); 
            }

        try {
            $stmt->execute();
        }
        catch( PDOException $Exception ) {
            $retval = false;
        }

        if ($stmt->errorCode() != 0)
            $_SESSION["conn_error"] .= $stmt->errorInfo()[2] . "\n";

        return $retval;
    }

    // Reference: https://stackoverflow.com/a/15575293
    function joinPaths() {
        $paths = array();

        foreach (func_get_args() as $arg)
            if ($arg !== '') { $paths[] = $arg; }

        return preg_replace('#/+#','/',join('/', $paths));
    }

    // Reference: https://stackoverflow.com/a/3110033
    function ordinal($number) {
        $ends = array("th", "st", "nd", "rd", "th", "th", "th", "th", "th", "th");
        if ((($number % 100) >= 11) && (($number%100) <= 13))
            return $number. "th";
        else
            return $number. $ends[$number % 10];
    }

    // Reference: https://codeblogmoney.com/validate-json-string-using-php/
    function json_validator($data=NULL) {
        if (!empty($data)) {
            @json_decode($data);
            return (json_last_error() === JSON_ERROR_NONE);
        }
        return false;
    }

    function deleteContract($contract_id) {
        return execute("DELETE FROM contracts WHERE contract_id=:contract_id", array("contract_id" => $contract_id));
    }

    function deleteTeam($team_id) {
        return execute("DELETE FROM teams WHERE team_id=:team_id", array("team_id" => $team_id));
    }

    function deleteTask($task_id) {
        return execute("DELETE FROM tasks WHERE task_id=:task_id", array("task_id" => $task_id));
    }

    function cleanReflectedValue($value) {
        return htmlspecialchars($value, ENT_QUOTES, "utf-8");
    }

    function breakLongWords($value) {
        return preg_replace("/[^\s]{80}/", "$0\n", $value);
    }
?>

<?php
    require_once("includes/common.php");

    if (endsWith(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/scores.json")) {
        $callback = isset($_GET["callback"]) ? $_GET["callback"] : null;

        if (is_null($callback))
            header("Content-Type: application/json");
        else
            header("Content-Type: application/javascript");

        if (file_exists("scores.json")) {
            $_ = file_get_contents("scores.json");
            $scores = json_decode($_, true);
        }
        else {
            $teams = getTeams();

            $scores = array();
            foreach ($teams as $team_id) {
                $row = fetchAll("SELECT * FROM teams WHERE team_id=:team_id", array("team_id" => $team_id))[0];
                $_ = array("name" => $row["full_name"], "code" => $row["country_code"], "country" => array_key_exists($row["country_code"], COUNTRIES) ? COUNTRIES[$row["country_code"]] : "", "score" => getScores($team_id)["cash"]);
                array_push($scores, $_);
            }
        }

        $scores = array_filter($scores, function($value) {
            return !(($value["score"] === 0) && is_null($value["country"]));
        });

        if (is_null($callback))
            die(json_encode($scores, JSON_PRETTY_PRINT));
        else
            die($callback . '(' . json_encode($scores) . ');');
    }

    if (!isset($_SESSION["team_id"])) {
        if (empty($_SERVER["HTTP_X_REQUESTED_WITH"]))
            include_once("includes/signin.php");
        else
            header("HTTP/1.1 401 Unauthorized");
        die();
    }
    else
        $result = fetchAll("SELECT * FROM teams WHERE team_id=:team_id", array("team_id" => $_SESSION["team_id"]));

    if (isset($_GET["signout"]) || (count($result) === 0)) {
        if (isset($_GET["signout"])) {
            $login_name = fetchScalar("SELECT login_name FROM teams WHERE team_id=:team_id", array("team_id" => $_SESSION["team_id"]));
            logMessage("Sign out", LogLevel::DEBUG);
        }

        session_unset();
        session_destroy();
        session_write_close();
        setcookie(session_name(), '', 0, '/');
        header("Location: " . PATHDIR);
        die();
    }
    else
        $_SESSION["team"] = $result[0];

    preg_match('/\/?([a-z]+)/', str_replace(PATHDIR, "/", $_SERVER["REQUEST_URI"]), $matches);

    if (!$matches || !in_array($matches[1], $VALID_PAGES))
        define("PAGE", $VALID_PAGES[0]);
    else
        define("PAGE", $matches[1]);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && isset($_POST["action"]) && isset($_SESSION["token"]) && isset($_POST["token"])) {
        if ($_POST["token"] === $_SESSION["token"])
            require_once("includes/action.php");
        else
            header("HTTP/1.1 400 Bad Request");
        die();
    }

    require_once("includes/header.php");

    if (DEBUG) {
        print <<<END
        <script>
            window.onerror = function(err, url, line) {
                alert(err + ' Script: ' + url + ' Line: ' + line);
            };
        </script>

END;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST["token"])) {
            if ($_POST["token"] !== $_SESSION["token"]) {
                logMessage("Wrong token", LogLevel::DEBUG);
                header("Location: " . cleanReflectedValue($_SERVER["REQUEST_URI"]));
                die();
            }

            else if (isset($_POST["contract_id"])) {
                if (isAdmin()) {
                    if (isset($_POST["edit"])) {
                        // handled in contracts.php
                    }
                }
                else if (in_array($_POST["contract_id"], getAvailableContracts($_SESSION["team_id"]))) {
                    $success = execute("INSERT INTO accepted(team_id, contract_id) VALUES(:team_id, :contract_id)", array("team_id" => $_SESSION["team_id"], "contract_id" => $_POST["contract_id"]));
                    if ($success) {
                        $title = fetchScalar("SELECT title FROM contracts WHERE contract_id=:contract_id", array("contract_id" => $_POST["contract_id"]));
                        print sprintf('<script>showMessageBox("Information", "You just accepted the contract \'%s\'");</script>', $title);
                        logMessage("Contract accepted", LogLevel::INFO, $title);
                    }
                }
            }

            else if (isset($_POST["answer"]) && isset($_POST["task_id"])) {
                if (isset($_SESSION["last_error"])) {
                    $delay = ANSWER_TIME_LIMIT - (time() - $_SESSION["last_error"]);
                    if ($delay > 0)
                        sleep($delay);
                }

                $result = fetchAll("SELECT * FROM options WHERE task_id=:task_id", array("task_id" => $_POST["task_id"]));

                if ($result)
                    $options = $result[0];
                else
                    $options = array("note" => "", "is_regex" => false, "ignore_case" => false, "ignore_order" => false);

                $answer = $_POST["answer"];
                $correct = fetchScalar("SELECT answer FROM tasks WHERE task_id=:task_id", array("task_id" => $_POST["task_id"]));
                $task_title = fetchScalar("SELECT title FROM tasks WHERE task_id=:task_id", array("task_id" => $_POST["task_id"]));

                if ($options["ignore_case"]) {
                    $answer = strtoupper($answer);
                    $correct = strtoupper($correct);
                }
                $success = $correct === $answer;
                $success |= $options["is_regex"] && preg_match("/" . $correct . "/", $answer);
                $success |= $options["ignore_order"] && wordMatch($correct, $answer);

                if (!$success) {
                    logMessage("Wrong answer", LogLevel::WARNING, "'" . $answer . "' => '" . $task_title . "'");
                }

                if (!checkStartEndTime()) {
                    logMessage("Correct answer, but out of time", LogLevel::DEBUG, "'" . $answer . "' => '" . $task_title . "'");
                    $success = false;
                }

                if ($success) {
                    if (getSetting("dynamic_scoring") == "true") {
                        $penalty = 0;  // TODO: calculate
                    }
                    else {
                        $penalty = 0;
                    }
                    $previous = getFinishedContracts($_SESSION["team_id"]);
                    $success = execute("INSERT INTO solved(task_id, team_id, penalty) VALUES(:task_id, :team_id, :penalty)", array("task_id" => $_POST["task_id"], "team_id" => $_SESSION["team_id"], "penalty" => $penalty));
                    if ($success) {
                        $result = fetchAll("SELECT contracts.title AS contract_title, tasks.title AS task_title FROM contracts, tasks WHERE tasks.task_id=:task_id AND contracts.contract_id=tasks.contract_id", array("task_id" => $_POST["task_id"]));
                        print sprintf('<script>showMessageBox("Success", "Congratulations! You have completed the task \'%s\'", "success");</script>', $result[0]["task_title"]);
                        logMessage("Task completed", LogLevel::INFO, $result[0]["task_title"]);
                        if (count(getFinishedContracts($_SESSION["team_id"])) > count($previous)) {
                            execute("INSERT INTO notifications(team_id, content, category) VALUES(:team_id, :content, :category)", array("team_id" => $_SESSION["team_id"], "content" => "You successfully finished contract '" . $result[0]["contract_title"] . "'", "category" => NotificationCategories::finished_contract));
                        }
                    }
                }
                else {
                    $_SESSION["last_error"] = time();
                    $html = <<<END
        <script>
            $(document).ready(function() {
                var element = $("[name=task_id][value=%s]").parent().parent();
                wrongValueEffect(element);
            });
        </script>

END;
                        print sprintf($html, cleanReflectedValue($_POST["task_id"]), cleanReflectedValue($_POST["task_id"]));
                }
            }
        }
    }
?>
    </head>
    <body>
        <div class="container mt-1" id="main_container">
            <div class="container">
                <div class="row">
                    <div class="col-md-9">
                        <div style="overflow: auto;">
                            <h1 class="display-4 float-left" style="font-family: 'Agency FB'; font-size: 64px; letter-spacing: 4px;"><?php echo TITLE . " platform"; ?></h1>
                            <img id="logo" src="<?php echo joinPaths(PATHDIR, '/resources/logo.jpg');?>" class="ml-3" width="70px"/>
                        </div>

                        <!-- BEGIN: navigation bar -->
                        <div id="navigation_bar" class="mb-3 mt-2">
                            <ul class="nav nav-tabs">
<?php
    if (!isAdmin()) {
        $html = <<<END
                                <li class="nav-item small">
                                    <a class="nav-link%s" href="%s">Rankings</a>
                                </li>
                                <li class="nav-item small">
                                    <a class="nav-link%s" href="%s">Job board <span class="badge badge-light border counter" id="available_count">%d</span></a>
                                </li>
                                <li class="nav-item small">
                                    <a class="nav-link%s" href="%s">Contracts <span class="badge badge-light border counter" id="active_count">%d</span></a>
                                </li>
END;
        echo sprintf($html, (PAGE === "rankings" ? " active" : ""), joinPaths(PATHDIR, '/rankings/'), (PAGE === "jobboard" ? " active" : ""), joinPaths(PATHDIR, '/jobboard/'), count(getAvailableContracts($_SESSION["team_id"])), (PAGE === "contracts" ? " active" : ""), joinPaths(PATHDIR, '/contracts/'), count(array_diff(array_merge(getActiveContracts($_SESSION["team_id"]), getFinishedContracts($_SESSION["team_id"])), getHiddenContracts())));
    }
    else {
        $html = <<<END
                                <li class="nav-item small">
                                    <a class="nav-link%s" href="%s">Teams</a>
                                </li>
                                <li class="nav-item small">
                                    <a class="nav-link%s" href="%s">Contracts <span class="badge badge-light border counter" id="contracts_count">%d</span></a>
                                </li>
END;
        echo sprintf($html, (PAGE === "teams" ? " active" : ""), joinPaths(PATHDIR, '/teams/'), (PAGE === "contracts" ? " active" : ""), joinPaths(PATHDIR, '/contracts/'), count(getAllContracts()));
    }
?>
                                <li class="nav-item small">
                                    <a class="nav-link<?php echo (PAGE === "notifications" ? " active" : "") ?>" href="<?php echo joinPaths(PATHDIR, '/notifications/');?>">Notifications <span class="badge badge-light border counter" id="notification_count"><?php echo count(getVisibleNotifications($_SESSION["team_id"]));?></span></a>
                                </li>
                                <li class="nav-item small">
                                    <a class="nav-link<?php echo (PAGE === "logs" ? " active" : "") ?>" href="<?php echo joinPaths(PATHDIR, '/logs/');?>">Logs <span class="badge badge-light border counter" id="log_count"><?php echo fetchScalar("SELECT COUNT(*) FROM logs");?></span></a>
                                </li>
                                <li class="nav-item small ml-3">
                                    <a class="nav-link btn-info" style="color: white; cursor: pointer; text-shadow: 1px 1px 1px #555" onclick="signOut()">Sign out</a>
                                </li>
<?php
    if (isAdmin()) {
        $html = <<<END
                                <li class="nav-item small">
                                    <a class="nav-link btn-danger ml-1" style="color: white; cursor: pointer; text-shadow: 1px 1px 1px #555" onclick="showResetBox()">Reset</a>
                                </li>

END;
        echo sprintf($html, cleanReflectedValue($_SERVER["REQUEST_URI"]));
    }
?>
                            </ul>
                        </div>
                        <!-- END: navigation bar -->

                        <!-- BEGIN: main content -->
                        <div id="main_content">
                            <div class="container">
<?php
    $path = sprintf("includes/%s.php", PAGE);
    if (isAdmin() && (PAGE == "teams"))
        $path = str_replace("/teams.php", "/rankings.php", $path);
    require_once($path);
?>
                            </div>
                        </div>
                        <!-- END: main content -->
                    </div>

                    <!-- BEGIN: side bar -->
                    <div id="side_bar" class="col-md-3 mt-5">
                        <div style="text-align: center"><h4 id="team_name" class="text-center mt-2" style="display: inline; vertical-align: middle"><?php echo cleanReflectedValue($_SESSION["team"]["full_name"]); ?></h4><span id="team_flag" class="flag-icon flag-icon-<?php echo cleanReflectedValue(strtolower($_SESSION["team"]["country_code"])); ?> ml-2" data-toggle="tooltip" title="<?php echo cleanReflectedValue(strtoupper($_SESSION["team"]["country_code"])); ?>" style="width: 20px; display: inline-block; vertical-align: middle"></span><i class="fas fa-lock ml-2" style="display: inline-block; vertical-align: middle; cursor: pointer" data-toggle="tooltip" title="Change password" onclick="showChangePasswordBox()"></i></div>

<?php
    if (!isAdmin()) {
        $html = <<<END
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-users" data-toggle="tooltip" title="Team information"></i> <span class="badge badge-light" style="float:right; line-height: 1.5; color: gray">(info)</span>
                            </div>
                            <div class="card-body" style="font-size: 12px">
                                <table id="info_table">
                                    <tr><td>Cash: </td><td><b>%s</b> &euro; (%s%s)</td></tr>
                                    <tr><td>Awareness: </td><td><b>%s</b> (%s%s)</td></tr>
                                    <tr><td>Last progress: </td><td><b>%s</b></td></tr>
                                    <tr><td>Active contracts: </td><td><abbr title="%s"><b>%d</b></abbr></td></tr>
                                    <tr><td>Finished contracts: </td><td><abbr title="%s"><b>%d</b></abbr></td></tr>
                                </table>
                            </div>
                        </div>


END;
        $scores = getScores($_SESSION["team_id"]);
        $places = getPlaces($_SESSION["team_id"]);
        $medals = array(1 => "first.jpg", 2 => "second.jpg", 3 => "third.jpg");
        $active = getActiveContracts($_SESSION["team_id"]);
        $finished = getFinishedContracts($_SESSION["team_id"]);
        $active_ = $active ? fetchScalar("SELECT GROUP_CONCAT(title ORDER BY title ASC) FROM contracts WHERE contract_id IN (" . implode(",", $active) . ")") : "-";
        $finished_ = $finished ? fetchScalar("SELECT GROUP_CONCAT(title ORDER BY title ASC) FROM contracts WHERE contract_id IN (" . implode(",", $finished) . ")") : "-";
        $last = fetchScalar("SELECT MAX(ts) FROM (SELECT UNIX_TIMESTAMP(ts) AS ts FROM solved WHERE team_id=:team_id UNION ALL SELECT UNIX_TIMESTAMP(ts) AS ts FROM privates WHERE cash IS NOT NULL AND (from_id=:team_id OR to_id=:team_id)) AS result", array("team_id" => $_SESSION["team_id"]));
        echo sprintf($html, number_format($scores["cash"]), ordinal($places["cash"]), $places["cash"] <= 3 ? ' <img src="' . joinPaths(PATHDIR, '/resources/' . $medals[$places["cash"]]) . '" height="16">' : "", number_format($scores["awareness"]), ordinal($places["awareness"]), $places["awareness"] <= 3 ? ' <img src="' . joinPaths(PATHDIR, '/resources/' . $medals[$places["awareness"]]) . '" height="16">' : "", $last ? sprintf("<span ts='%d'></span>", $last) : "-", str_replace(",", ", ", $active_), count($active), str_replace(",", ", ", $finished_), count($finished));
    }
    else {
        $html = <<<END
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-cogs" data-toggle="tooltip" title="Platform settings"></i> <span class="badge badge-light" style="float:right; line-height: 1.5; color: gray">(settings)</span>
                            </div>
                            <div class="card-body" style="font-size: 12px">
                                <table id="settings_table">%s
                                </table>
                            </div>
                        </div>


END;
        $settings = "";
        $settings .= "\n" . sprintf('                                    <tr><td>%s: </td><td><input id="%s" type="checkbox"%s></td></tr>', "Cash transfers", "transfers", getSetting("transfers") !== "false" ? " checked" : "");
        $settings .= "\n" . sprintf('                                    <tr><td>%s: </td><td><input id="%s" type="checkbox"%s></td></tr>', "Dynamic scoring", "dynamic_scoring", getSetting("dynamic_scoring") == "true" ? " checked" : "");
        $settings .= "\n" . sprintf('                                    <tr><td>Start time (optional): </td><td><input id="datetime_start" type="text" value="%s" size="18"></td></tr>', getSetting("datetime_start"));
        $settings .= "\n" . sprintf('                                    <tr><td>End time (optional): </td><td><input id="datetime_end" type="text" value="%s" size="18"></td></tr>', getSetting("datetime_end"));

        echo sprintf($html, $settings);
    }
?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-comments" data-toggle="tooltip" title="Public chat"></i> <span class="badge badge-light" style="float:right; line-height: 1.5; color: gray">(chat)</span>
                            </div>
                            <div id="chat_messages" class="scroll-box">
                            </div>
                            <div style="text-align:center">
                                <form onsubmit="return pushMessage()">
                                    <input id="chat_message" type="text" placeholder="<?php echo $_SESSION["login_name"];?> says:" autocomplete="off"/>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- END: side bar -->
                </div>
            </div>
        </div>

        <!-- BEGIN: prompt box -->
        <div class="modal" id="prompt-box" tabindex="-1" role="dialog" autocomplete="off">
            <div class="modal-dialog" role="document" style="width: 350px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Send</button>
                        <button class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- END: prompt box -->

        <!-- BEGIN: message box -->
        <div class="modal" id="message-box" tabindex="-1" role="dialog" autocomplete="off">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div style="margin: 10px">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- END: message box -->
        <script>
<?php
$value = "";
for ($i = 0; $i < strlen($_SESSION["token"]); $i++) {
    if ($value)
        $value .= ",";
    $value .= ord($_SESSION["token"][$i]);
}
echo "              document.token = String.fromCharCode(" . $value . ");\n";
?>
        </script>
        <noscript>
            Javascript is disabled in your browser. You must have Javascript enabled to utilize the functionality of this page!
        </noscript>
    </body>
</html>

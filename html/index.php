<?php
    require_once("includes/common.php");
    require_once("includes/exports.php");

//     require_once("includes/report.php");
//
//     generateReport();
//     die();

    if(preg_match("/\/captcha\?\d+$/", $_SERVER["REQUEST_URI"])) {
        include_once("includes/captcha.php");
        die();
    }

    if(preg_match("/\/api\/\w+/", $_SERVER["REQUEST_URI"])) {
        include_once("includes/api.php");
        die();
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
        signOut();
    }
    else
        $_SESSION["team"] = $result[0];

    preg_match('/\/?([a-z]+)/', str_replace(PATHDIR, "/", $_SERVER["REQUEST_URI"]), $matches);

    if (!$matches || !in_array($matches[1], $VALID_PAGES))
        define("PAGE", $VALID_PAGES[0]);
    else
        define("PAGE", $matches[1]);

    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_FILES)) && isset($_POST["action"]) && isset($_SESSION["token"]) && isset($_POST["token"])) {
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
                if (isset($_SESSION["last_wrong_time"])) {
                    $delay = (is_numeric(getSetting(Setting::GUESS_DELAY)) ? intval(getSetting(Setting::GUESS_DELAY)) : 0) - (time() - $_SESSION["last_wrong_time"]);
                    if ($delay > 0)
                        sleep($delay);
                }

                $result = fetchAll("SELECT * FROM options WHERE task_id=:task_id", array("task_id" => $_POST["task_id"]));

                if ($result)
                    $options = $result[0];
                else
                    $options = array("note" => "", "is_regex" => false, "ignore_case" => false, "ignore_order" => false);

                $answer = $_POST["answer"];
                $correct = fetchScalar("SELECT answer FROM tasks JOIN contracts ON tasks.contract_id=contracts.contract_id WHERE task_id=:task_id AND contracts.hidden IS NOT TRUE", array("task_id" => $_POST["task_id"]));
                $task_title = fetchScalar("SELECT title FROM tasks WHERE task_id=:task_id", array("task_id" => $_POST["task_id"]));

                if (!is_null($correct)) {
                    if ($options["ignore_case"]) {
                        $answer = strtoupper($answer);
                        $correct = strtoupper($correct);
                    }

                    $correct = preg_replace("/\s+/", "", $correct);
                    $answer = preg_replace("/\s+/", "", $answer);

                    $success = $correct === $answer;
                    $success |= $options["is_regex"] && preg_match("/" . $correct . "/", $answer);
                    $success |= $options["ignore_order"] && wordMatch($correct, $answer);
                }
                else
                    $success = false;

                if (!$success) {
                    logMessage("Wrong answer", LogLevel::DEBUG, "'" . $_POST["answer"] . "' => '" . $task_title . "'");

                    if (isset($_SESSION["last_wrong_taskid"]) && ($_SESSION["last_wrong_taskid"] == $_POST["task_id"])) {
                        $_SESSION["last_wrong_counter"] = (isset($_SESSION["last_wrong_counter"]) ? $_SESSION["last_wrong_counter"] : 0) + 1;
                    }
                    else
                        $_SESSION["last_wrong_counter"] = 0;

                    $_SESSION["last_wrong_taskid"] = $_POST["task_id"];
                }

                if (isset($_SESSION["last_wrong_counter"]) && is_numeric(getSetting(Setting::GUESS_LOGOUT)) && (intval(getSetting(Setting::GUESS_LOGOUT)) > 0)) {
                    if ($_SESSION["last_wrong_counter"] >= intval(getSetting(Setting::GUESS_LOGOUT))) {
                        logMessage("Guess prevention", LogLevel::WARNING, "Potential brute-force detected");
                        signOut();
                    }
                }

                if (!checkStartEndTime() && $success) {
                    logMessage("Correct answer, but out of time", LogLevel::DEBUG, "'" . $_POST["answer"] . "' => '" . $task_title . "'");
                    $success = false;
                }

                if ($success) {
                    $leader = getRankedTeams()[0];
                    $previous = getFinishedContracts($_SESSION["team_id"]);
                    $success = execute("INSERT INTO solved(task_id, team_id) VALUES(:task_id, :team_id)", array("task_id" => $_POST["task_id"], "team_id" => $_SESSION["team_id"]));
                    if ($success) {
                        $result = fetchAll("SELECT contracts.title AS contract_title, tasks.title AS task_title FROM contracts, tasks WHERE tasks.task_id=:task_id AND contracts.contract_id=tasks.contract_id", array("task_id" => $_POST["task_id"]));
                        print sprintf('<script>showMessageBox("Success", "Congratulations! You have completed the task \'%s\'", "success");</script>', $result[0]["task_title"]);
                        logMessage("Task completed", LogLevel::INFO, $result[0]["contract_title"] . ':' . $result[0]["task_title"]);
                        if (count(getFinishedContracts($_SESSION["team_id"])) > count($previous)) {
                            execute("INSERT INTO notifications(team_id, content, category) VALUES(:team_id, :content, :category)", array("team_id" => $_SESSION["team_id"], "content" => "You successfully finished contract '" . $result[0]["contract_title"] . "'", "category" => NotificationCategory::FINISHED_CONTRACT));
                        }
                        $_ = getRankedTeams()[0];
                        if ($_ != $leader) {
                            $team_name = fetchScalar("SELECT full_name FROM teams WHERE team_id=:team_id", array("team_id" => $_));
                            logMessage("Leader changed", LogLevel::INFO, $team_name);
                        }
                    }
                }
                else {
                    $_SESSION["last_wrong_time"] = time();
                    $html = <<<END
        <script>
            $(document).ready(function() {
                var element = $("[name=task_id][value=%s]");

                $([document.documentElement]).animate({scrollTop: $(element.closest(".card")).offset().top}, "fast", function() {
                    wrongValueEffect($(element).closest(".card-body"));
                });
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
        <div class="container mt-1<?php echo ((PAGE === "scoreboard") || (PAGE === "chat")) ? " hidden" : ""?>" id="main_container">
            <div class="container">
                <div class="row">
                    <div class="col-md-9">
                        <div style="overflow: auto;">
                            <h1 class="display-4 float-left" style="font-family: 'Agency FB'; font-size: 64px; letter-spacing: 4px;"><?php echo TITLE . " platform"; ?></h1>
                            <img id="logo" src="<?php echo joinPaths(PATHDIR, '/resources/logo.jpg');?>" class="ml-3" width="70" height="70"/>
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
<?php
    if (isAdmin()) {
        $html = <<<END
                                <li class="nav-item small">
                                    <a class="nav-link%s" href="%s">Logs <span class="badge badge-light border counter" id="log_count">%d</span></a>
                                </li>
                                <li class="nav-item small">
                                    <a class="nav-link%s" href="%s">Stats</a>
                                </li>
END;
        echo sprintf($html, (PAGE === "logs" ? " active" : ""), joinPaths(PATHDIR, '/logs/'), fetchScalar("SELECT COUNT(*) FROM logs"), (PAGE === "stats" ? " active" : ""), joinPaths(PATHDIR, '/stats/'));
    }
?>
                                <li class="nav-item small ml-1">
                                    <a class="nav-link btn-info" style="color: white; cursor: pointer; text-shadow: 1px 1px 1px #555" onclick="signOut()">Sign out</a>
                                </li>
<?php
    if (isAdmin()) {
        $html = <<<END
                                <li class="nav-item small">
                                    <a class="nav-link btn-danger ml-1" style="color: white; cursor: pointer; text-shadow: 1px 1px 1px #555" onclick="showResetBox()">Reset</a>
                                </li>
                                <li class="nav-item small">
                                    <a class="nav-link btn-warning ml-1" style="color: white; cursor: pointer; text-shadow: 1px 1px 1px #555" onclick="showDatabaseBox()">Database</a>
                                </li>
                                <li class="nav-item small">
                                    <a class="nav-link btn-success ml-1" style="color: white; cursor: pointer; text-shadow: 1px 1px 1px #555" onclick="getReport()">Report</a>
                                </li>

END;
        echo sprintf($html, cleanReflectedValue($_SERVER["REQUEST_URI"]));
    }
    else {
        $html = <<<END
                                <li class="nav-item small">
                                    <a class="nav-link btn-secondary ml-1" style="color: white; cursor: pointer; text-shadow: 1px 1px 1px #555" href="%s">Rules</a>
                                </li>

END;
        echo sprintf($html, OFFICIAL_RULES_URL);
    }
?>
                            </ul>
                        </div>
                        <!-- END: navigation bar -->

                        <!-- BEGIN: main content -->
                        <div id="main_content">
                            <div class="container container-<?php echo PAGE;?>">
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
                        <div style="text-align: center"><h4 id="team_name" class="text-center mt-2" style="display: inline; vertical-align: middle"><?php echo cleanReflectedValue($_SESSION["team"]["full_name"]); ?></h4><span id="team_flag" class="flag-icon flag-icon-<?php echo cleanReflectedValue(strtolower($_SESSION["team"]["country_code"])); ?> ml-2" data-toggle="tooltip" title="<?php echo cleanReflectedValue(strtoupper($_SESSION["team"]["country_code"])); ?>" style="width: 20px; display: inline-block; vertical-align: middle"></span><i class="fas fa-key fa-sm ml-2" style="display: <?php echo (isAdmin() ? "inline-block" : "none");?>; vertical-align: middle; cursor: pointer" data-toggle="tooltip" title="Change password"></i></div>

<?php
    if (!isAdmin()) {
        $html = <<<END
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-users" data-toggle="tooltip" title="Team information"></i> <span class="badge badge-light" style="float:right; line-height: 1.5; color: gray">(info)</span>
                            </div>
                            <div class="card-body" style="font-size: 12px">
                                <table id="info_table">
                                    <tr><td>Cash: </td><td><b>%s</b> <i class="currency"></i> (%s%s)</td></tr>
                                    <tr class="awareness"><td>Awareness: </td><td><b>%s</b> (%s%s)</td></tr>
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
        $active = array_diff(getActiveContracts($_SESSION["team_id"]), getHiddenContracts());
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
                                <i class="fas fa-cogs"></i> <span class="badge badge-light" style="float:right; line-height: 1.5; color: gray">(settings)</span>
                            </div>
                            <div class="card-body" style="font-size: 12px">
                                <table id="settings_table">%s
                                </table>
                            </div>
                        </div>


END;
        $settings = "";
        $settings .= "\n" . sprintf('                                    <tr><td data-toggle="tooltip" title="Enable/disable cash transfers between teams">%s: </td><td><input id="%s" type="checkbox"%s></td></tr>', "Cash transfers", "cash_transfers", parseBool(getSetting(Setting::CASH_TRANSFERS)) ? " checked" : "");
        $settings .= "\n" . sprintf('                                    <tr><td data-toggle="tooltip" title="Enable/disable dynamic scoring (Note: linear task cash value decrease (&alpha;=%s) based on number of solvers)">%s: </td><td><input id="%s" type="checkbox"%s></td></tr>', DYNAMIC_DECAY_PER_SOLVE, "Dynamic scoring", "dynamic_scoring", parseBool(getSetting(Setting::DYNAMIC_SCORING)) ? " checked" : "");
        $settings .= "\n" . sprintf('                                    <tr><td data-toggle="tooltip" title="Enable/disable private messages between teams">%s: </td><td><input id="%s" type="checkbox"%s></td></tr>', "Private messages", "private_messages", parseBool(getSetting(Setting::PRIVATE_MESSAGES)) ? " checked" : "");
        $settings .= "\n" . sprintf('                                    <tr><td data-toggle="tooltip" title="(optional) Enable/disable sending of support (i.e. private) messages to Administrator">%s: </td><td><input id="%s" type="checkbox"%s></td></tr>', "Support messages", "support_messages", parseBool(getSetting(Setting::SUPPORT_MESSAGES)) ? " checked" : "");
        $settings .= "\n" . sprintf('                                    <tr><td data-toggle="tooltip" title="(optional) Number of seconds for a deliberate delay between potential task guessing attempts">Guess attempt penalty (secs): </td><td><input id="guess_delay" type="number" min="0" value="%s"></td></tr>', is_numeric(getSetting(Setting::GUESS_DELAY)) ? getSetting(Setting::GUESS_DELAY) : "0");
        $settings .= "\n" . sprintf('                                    <tr><td data-toggle="tooltip" title="(optional) Number of wrong potential (sequential) task guessing attempts before an abrupt logout">Guess logout after attempts: </td><td><input id="guess_logout" type="number" min="0" value="%s"></td></tr>', is_numeric(getSetting(Setting::GUESS_LOGOUT)) ? getSetting(Setting::GUESS_LOGOUT) : "");
        $settings .= "\n" . sprintf('                                    <tr><td data-toggle="tooltip" title="(optional) Starting datetime of the competition">Start time (optional): </td><td><input id="datetime_start" type="text" value="%s" size="18"></td></tr>', getSetting(Setting::DATETIME_START));
        $settings .= "\n" . sprintf('                                    <tr><td data-toggle="tooltip" title="(optional) Ending datetime of the competition">End time (optional): </td><td><input id="datetime_end" type="text" value="%s" size="18"></td></tr>', getSetting(Setting::DATETIME_END));

        echo sprintf($html, $settings);
    }
?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-comments"></i> <select id="chat_room" class="badge" data-toggle="tooltip" title="Current chat room (Note: room #<?php echo PRIVATE_ROOM;?> is visible only to the same team members)"><option value="general">#general</option><option value="news">#news</option><option value="random">#random</option><option value="team">#team &#x1F512;</option></select> <span class="badge badge-light" style="float:right; line-height: 1.5; color: gray">(chat)</span> <i class="fas fa-external-link-alt" style="position: absolute; right: 7px; top: 8px" data-toggle="tooltip" title="Open in new tab" onclick="openInNewTab('<?php echo joinPaths(PATHDIR, '/chat');?>')"></i>
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

<?php
    require_once("includes/footer.php");
?>

<?php
    function fatal_handler() {
        $error = error_get_last();

        if($error !== NULL)
            logMessage("Fatal error", LogLevel::CRITICAL, $error["file"] . ":" . $error["line"] . " " . $error["message"]);
    }

    // Reference: https://stackoverflow.com/a/2146171
    register_shutdown_function("fatal_handler");

    define("DEBUG", file_exists("../.debug"));
    define("MYSQL_SERVER", "localhost");
    define("MYSQL_USERNAME", "ecsc");
    define("MYSQL_PASSWORD", "changeme!");
    define("MYSQL_DATABASE", "ecsc");
    define("PATHDIR", dirname(preg_replace("/[^a-zA-Z0-9\/._-]/", "", $_SERVER["PHP_SELF"])));
    define("MOMENTUM_STEPS", 30);
    define("MIN_CASH_VALUE", 0);
    define("ADMIN_LOGIN_NAME", "admin");
    define("TITLE", "ECSC " . date("Y"));
    define("DEFAULT_ROOM", "general");
    define("PRIVATE_ROOM", "team");
    define("FLAG_REGEX", "/ECSC\{[^\}]+\}?/i");
    define("FLAG_REDACTED", "ECSC{...}");
    define("GD_INSTALLED", extension_loaded("gd"));     // Note: had problems on Ubuntu 18.04 when PHP has been updated to 7.2, while the 7.0 had still been used (hence, problems with GD arised) - thus, had to do the "apt remove php7.0" to get GD up and running
    define("CAPTCHA_ENABLED", true && GD_INSTALLED);
    define("NOTIFICATIONS_HIDE_ENABLED", false);        // Enable ability for users to hide individual notifications
    define("HEARTBEAT_POINTS", 24);
    define("DETAILS_TRUNCATE_LENGTH", 200);
    define("DETAILS_WRAP_LENGTH", 30);
    define("CHAT_TRUNCATE_LENGTH", 128);                // Note: maximum chat message length for non-admin
    define("PRIVATE_TRUNCATE_LENGTH", 256);             // Note: maximum private message length for non-admin
    define("OFFICIAL_RULES_URL", "https://ecsc.eu/about/ecscrules.pdf/download");
    define("TOKEN_LIFE", 4 * 24 * 3600);
    define("SAME_CASH_SAME_RANK", false);
    define("DEFAULT_INITIAL_AVAILABILITY", 100000);
    define("DEFAULT_DYNAMIC_SOLVE_THRESHOLD", 20);
    define("DEFAULT_DYNAMIC_MAXIMUM_DECAY", 50);

    if (isset($_SERVER['REMOTE_ADDR']))
        // Reference: https://stackoverflow.com/a/2886224
        if ((!empty($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) !== "off") || $_SERVER["SERVER_PORT"] == 443)
            ini_set("session.cookie_secure", "1");

    session_start();

    if (!isset($_SESSION["token"]))
        $_SESSION["token"] = preg_replace('/[^0-9a-zA-Z]/', '', base64_encode(openssl_random_pseudo_bytes(32)));

    abstract class NotificationCategory {
        const EVERYBODY = "light";
        const FINISHED_CONTRACT = "success";
        const SENT_PRIVATE = "info";
        const RECEIVED_PRIVATE = "warning";
        const AWARDED = "success";
        const PENALIZED = "danger";
    }

    abstract class LogLevel {
        const DEBUG = "debug";
        const INFO = "info";
        const WARNING = "warning";
        const ERROR = "error";
        const CRITICAL = "critical";
    }

    abstract class Setting {
        const DYNAMIC_SCORING = "dynamic_scoring";
        const DATETIME_START = "datetime_start";
        const DATETIME_END = "datetime_end";
        const GUESS_DELAY = "guess_delay";
        const GUESS_LOGOUT = "guess_logout";
        const CASH_TRANSFERS = "cash_transfers";
        const PRIVATE_MESSAGES = "private_messages";
        const SUPPORT_MESSAGES = "support_messages";
        const USE_AWARENESS = "use_awareness";
        const CTF_STYLE = "ctf_style";
        const INITIAL_AVAILABILITY = "initial_availability";
        const EXPLICIT_START_STOP = "explicit_start_stop";
        const DYNAMIC_SOLVE_THRESHOLD = "dynamic_solve_threshold";
        const DYNAMIC_MAXIMUM_DECAY = "dynamic_maximum_decay";
    }

    abstract class Cache {
        const MOMENTUM = "momentum";
    }

    // PHP5 compatibility (can't use arrays in defines)
    const PREDEFINED_COLORS = array("Offensive" => "0078c4", "Web" => "41c4dc", "Crypto" => "8fc33f", "Reverse-engineering" => "fedd00", "Forensics" => "f8972a", "Networking" => "e3124f", "Multiple options" => "b01c91");

    const LOG_COLORS = array("debug" => "#8ba8c0", "info" => "#00cc00", "warning" => "#f0ad4e", "error" => "#d9534f", "critical" => "#cc33ff");

    // Note: same as in main.js
    const COUNTRIES = array("AF" => "Afghanistan", "AX" => "Aland Islands", "AL" => "Albania", "DZ" => "Algeria", "AS" => "American Samoa", "AD" => "Andorra", "AO" => "Angola", "AI" => "Anguilla", "AG" => "Antigua and Barbuda", "AR" => "Argentina", "AM" => "Armenia", "AW" => "Aruba", "AU" => "Australia", "AT" => "Austria", "AZ" => "Azerbaijan", "BS" => "Bahamas", "BH" => "Bahrain", "BD" => "Bangladesh", "BB" => "Barbados", "BY" => "Belarus", "BE" => "Belgium", "BZ" => "Belize", "BJ" => "Benin", "BM" => "Bermuda", "BT" => "Bhutan", "BO" => "Bolivia", "BQ" => "Bonaire", "BA" => "Bosnia and Herzegovina", "BW" => "Botswana", "BR" => "Brazil", "BN" => "Brunei Darussalam", "BG" => "Bulgaria", "BF" => "Burkina Faso", "BI" => "Burundi", "CV" => "Cabo Verde", "KH" => "Cambodia", "CM" => "Cameroon", "CA" => "Canada", "KY" => "Cayman Islands", "CF" => "Central African Republic", "TD" => "Chad", "CL" => "Chile", "CN" => "China", "CX" => "Christmas Island", "CC" => "Cocos (Keeling) Islands", "CO" => "Colombia", "KM" => "Comoros", "CK" => "Cook Islands", "CR" => "Costa Rica", "HR" => "Croatia", "CU" => "Cuba", "CW" => "Curaçao", "CY" => "Cyprus", "CZ" => "Czech Republic", "CI" => "Cote d'Ivoire", "CD" => "Congo", "DK" => "Denmark", "DJ" => "Djibouti", "DM" => "Dominica", "DO" => "Dominican Republic", "EC" => "Ecuador", "EG" => "Egypt", "SV" => "El Salvador", "GQ" => "Equatorial Guinea", "ER" => "Eritrea", "EE" => "Estonia", "ET" => "Ethiopia", "FK" => "Falkland Islands", "FO" => "Faroe Islands", "FM" => "Micronesia", "FJ" => "Fiji", "FI" => "Finland", "MK" => "FYR Macedonia", "FR" => "France", "GF" => "French Guiana", "PF" => "French Polynesia", "GA" => "Gabon", "GM" => "Gambia", "GE" => "Georgia", "DE" => "Germany", "GH" => "Ghana", "GI" => "Gibraltar", "GR" => "Greece", "GL" => "Greenland", "GD" => "Grenada", "GP" => "Guadeloupe", "GU" => "Guam", "GT" => "Guatemala", "GG" => "Guernsey", "GN" => "Guinea", "GW" => "Guinea-Bissau", "GY" => "Guyana", "HT" => "Haiti", "VA" => "Holy See", "HN" => "Honduras", "HK" => "Hong Kong", "HU" => "Hungary", "IS" => "Iceland", "IN" => "India", "ID" => "Indonesia", "IR" => "Iran", "IQ" => "Iraq", "IE" => "Ireland", "IM" => "Isle of Man", "IL" => "Israel", "IT" => "Italy", "JM" => "Jamaica", "JP" => "Japan", "JE" => "Jersey", "JO" => "Jordan", "KZ" => "Kazakhstan", "KE" => "Kenya", "KI" => "Kiribati", "KW" => "Kuwait", "KG" => "Kyrgyzstan", "LA" => "Laos", "LV" => "Latvia", "LB" => "Lebanon", "LS" => "Lesotho", "LR" => "Liberia", "LY" => "Libya", "LI" => "Liechtenstein", "LT" => "Lithuania", "LU" => "Luxembourg", "MO" => "Macau", "MG" => "Madagascar", "MW" => "Malawi", "MY" => "Malaysia", "MV" => "Maldives", "ML" => "Mali", "MT" => "Malta", "MH" => "Marshall Islands", "MQ" => "Martinique", "MR" => "Mauritania", "MU" => "Mauritius", "YT" => "Mayotte", "MX" => "Mexico", "MD" => "Moldova", "MC" => "Monaco", "MN" => "Mongolia", "ME" => "Montenegro", "MS" => "Montserrat", "MA" => "Morocco", "MZ" => "Mozambique", "MM" => "Myanmar", "NA" => "Namibia", "NR" => "Nauru", "NP" => "Nepal", "NL" => "Netherlands", "NC" => "New Caledonia", "NZ" => "New Zealand", "NI" => "Nicaragua", "NE" => "Niger", "NG" => "Nigeria", "NU" => "Niue", "NF" => "Norfolk Island", "KP" => "North Korea", "MP" => "Northern Mariana Islands", "NO" => "Norway", "OM" => "Oman", "PK" => "Pakistan", "PW" => "Palau", "PA" => "Panama", "PG" => "Papua New Guinea", "PY" => "Paraguay", "PE" => "Peru", "PH" => "Philippines", "PN" => "Pitcairn", "PL" => "Poland", "PT" => "Portugal", "PR" => "Puerto Rico", "QA" => "Qatar", "CG" => "Republic of the Congo", "RO" => "Romania", "RU" => "Russia", "RW" => "Rwanda", "RE" => "Réunion", "BL" => "Saint Barthélemy", "SH" => "Saint Helena", "KN" => "Saint Kitts and Nevis", "LC" => "Saint Lucia", "MF" => "Saint Martin", "WS" => "Samoa", "SM" => "San Marino", "ST" => "Sao Tome and Principe", "SA" => "Saudi Arabia", "SN" => "Senegal", "RS" => "Serbia", "SC" => "Seychelles", "SL" => "Sierra Leone", "SG" => "Singapore", "SX" => "Sint Maarten", "SK" => "Slovakia", "SI" => "Slovenia", "SB" => "Solomon Islands", "SO" => "Somalia", "ZA" => "South Africa", "KR" => "South Korea", "SS" => "South Sudan", "ES" => "Spain", "LK" => "Sri Lanka", "PS" => "State of Palestine", "SD" => "Sudan", "SR" => "Suriname", "SJ" => "Svalbard and Jan Mayen", "SZ" => "Swaziland", "SE" => "Sweden", "CH" => "Switzerland", "SY" => "Syrian Arab Republic", "TW" => "Taiwan", "TJ" => "Tajikistan", "TZ" => "Tanzania", "TH" => "Thailand", "TL" => "Timor-Leste", "TG" => "Togo", "TK" => "Tokelau", "TO" => "Tonga", "TT" => "Trinidad and Tobago", "TN" => "Tunisia", "TR" => "Turkey", "TM" => "Turkmenistan", "TC" => "Turks and Caicos Islands", "TV" => "Tuvalu", "UG" => "Uganda", "UA" => "Ukraine", "AE" => "United Arab Emirates", "GB" => "United Kingdom", "US" => "United States of America", "UY" => "Uruguay", "UZ" => "Uzbekistan", "VU" => "Vanuatu", "VE" => "Venezuela", "VN" => "Vietnam", "VG" => "Virgin Islands (British)", "VI" => "Virgin Islands (U.S.)", "WF" => "Wallis and Futuna", "EH" => "Western Sahara", "YE" => "Yemen", "ZM" => "Zambia", "ZW" => "Zimbabwe");

    if (DEBUG) {
        ini_set("display_startup_errors", '1');
        ini_set("display_errors", '1');
        error_reporting(E_ALL);
    }

    $conn = new PDO("mysql:host=" . MYSQL_SERVER . ";dbname=" . MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $_SESSION["conn_error"] = "";

    function isAdmin() {
        return (isset($_SESSION["login_name"]) && ($_SESSION["login_name"] === ADMIN_LOGIN_NAME));
    }

    $VALID_PAGES = isAdmin() ? array("teams", "contracts", "notifications", "logs", "stats", "chat", "scoreboard") : array("rankings", "jobboard", "contracts", "notifications", "chat", "scoreboard");

    function getLastUpdateTimestamp() {
        return fetchScalar("SELECT last_update()");
    }

    function generateRandomString($length=10) {
        $pool = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $retval = '';
        for ($i = 0; $i < $length; $i++) {
            $retval .= $pool[rand(0, strlen($pool) - 1)];
        }
        return $retval;
    }

    // Reference: https://stackoverflow.com/a/40582472
    function getAuthorizationHeader(){
            $headers = null;
            if (isset($_SERVER['Authorization'])) {
                $headers = trim($_SERVER["Authorization"]);
            }
            else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
                $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
            } elseif (function_exists('apache_request_headers')) {
                $requestHeaders = apache_request_headers();
                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
                //print_r($requestHeaders);
                if (isset($requestHeaders['Authorization'])) {
                    $headers = trim($requestHeaders['Authorization']);
                }
            }
            return $headers;
        }

    function getBearerToken() {
        $headers = getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    function format($str, $args) {
        foreach ($args as $key => $value) {
            $str = str_replace('{' . $key . '}', $value, $str);
        }
        return $str;
    }

    function generateValuesHtml($cash, $awareness, $dynamic=null) {
        if (is_numeric($cash))
            $result = format('<span class="badge {appearance} border mr-2"><i class="currency"></i> {cash}</span>', array("cash" => number_format($cash), "appearance" => ($cash > 0 ? "badge-success" : ($cash < 0 ? "badge-danger" : "badge-light"))));
        else
            $result = format('<span class="badge {appearance} border mr-2"><i class="currency"></i> {cash}</span>', array("cash" => $cash, "appearance" => "badge-light"));

        if (!is_null($dynamic) && ($dynamic != $cash))
            $result = str_replace('</span>', '/' . $dynamic . '</span>', $result);

        if (is_numeric($awareness))
            $result .= format('<span class="badge {appearance} border mr-2 awareness">{awareness} <i class="fas fa-eye"></i></span>', array("awareness" => number_format($awareness), "appearance" => ($awareness > 0 ? "badge-success" : ($awareness < 0 ? "badge-danger" : "badge-light"))));
        else
            $result .= format('<span class="badge {appearance} border mr-2 awareness">{awareness} <i class="fas fa-eye"></i></span>', array("awareness" => $awareness, "appearance" => "badge-light"));
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

    function getSolvedTasks($team_id, $ts=null) {
        return fetchAll("SELECT task_id FROM solved WHERE team_id=:team_id AND UNIX_TIMESTAMP(ts)<=COALESCE(:ts, UNIX_TIMESTAMP(NOW())) ORDER BY ts ASC", array("team_id" => $team_id, "ts" => $ts), PDO::FETCH_COLUMN);
    }

    function getScores($team_id, $ts=null) {
        $result = array("cash" => 0, "awareness" => 0);
        $solved = getSolvedTasks($team_id, $ts);
        if (count($solved) > 0) {
            $tasks = array();
            $_ = fetchAll("SELECT task_id, cash, awareness FROM tasks");
            foreach ($_ as $row)
                $tasks[$row["task_id"]] = $row;

            foreach ($solved as $task_id) {
                $task = $tasks[$task_id];
                $result["cash"] += (parseBool(getSetting(Setting::DYNAMIC_SCORING)) ? getDynamicScore($task_id, null, true) : floatval($task["cash"]));
                $result["awareness"] += floatval($task["awareness"]);
            }
        }

        $_ = fetchScalar("SELECT SUM(cash) FROM privates WHERE from_id=:team_id AND UNIX_TIMESTAMP(ts)<=COALESCE(:ts, UNIX_TIMESTAMP(NOW()))", array("team_id" => $team_id, "ts" => $ts));
        $result["cash"] -= is_null($_) ? 0 : $_;

        $_ = fetchScalar("SELECT SUM(cash) FROM privates WHERE to_id=:team_id AND UNIX_TIMESTAMP(ts)<=COALESCE(:ts, UNIX_TIMESTAMP(NOW()))", array("team_id" => $team_id, "ts" => $ts));
        $result["cash"] += is_null($_) ? 0 : $_;

        $result["cash"] = max($result["cash"], MIN_CASH_VALUE);

        $_ = fetchAll("SELECT flag_score, availability_score FROM attack_defense WHERE team_id=:team_id", array("team_id" => $team_id), PDO::FETCH_ASSOC);
        $initial_availability = is_null(getSetting(Setting::INITIAL_AVAILABILITY)) ? DEFAULT_INITIAL_AVAILABILITY: getSetting(Setting::INITIAL_AVAILABILITY);
        if (count($_) == 1) {
            $_ = $_[0];
            $result["flags"] = is_null($_["flag_score"]) ? 0 : $_["flag_score"];
            $result["availability"] = is_null($_["availability_score"]) ? $initial_availability : $_["availability_score"];
        }
        else {
            $result["flags"] = 0;
            $result["availability"] = $initial_availability;
        }

        return $result;
    }

    function getMomentum($team_id=null) {
        $result = array();
        $teams = is_null($team_id) ? getRankedTeams() : array($team_id);

        foreach ($teams as $team_id) {
            $_ = fetchAll("SELECT full_name, guest FROM teams WHERE team_id=:team_id", array("team_id" => $team_id));
            $team = array("name" => $_[0]["full_name"], "guest" => $_[0]["guest"], "cash" => array(), "awareness" => array());

            $show = false;
            $first = true;
            $timestamps = fetchAll("SELECT ts FROM (SELECT UNIX_TIMESTAMP(ts) AS ts FROM solved WHERE team_id=:team_id UNION ALL SELECT UNIX_TIMESTAMP(ts) AS ts FROM privates WHERE cash IS NOT NULL AND (from_id=:team_id OR to_id=:team_id)) AS result ORDER BY ts ASC", array("team_id" => $team_id), PDO::FETCH_COLUMN);

            foreach ($timestamps as $timestamp) {
                $current = getScores($team_id, $timestamp);

                if ($first) {
                    array_push($team["cash"], array("x" => intval($timestamp) - 1, "y" => 0));
                    array_push($team["awareness"], array("x" => intval($timestamp) - 1, "y" => 0));
                    $first = false;
                }

                array_push($team["cash"], array("x" => intval($timestamp), "y" => $current["cash"]));
                array_push($team["awareness"], array("x" => intval($timestamp), "y" => $current["awareness"]));

                $show |= $current["cash"] > 0;
            }

            if (!$show)
                $team["cash"] = array();

            array_push($result, $team);
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

    function getHiddenContracts() {
        return fetchAll("SELECT contract_id FROM contracts WHERE hidden=TRUE", null, PDO::FETCH_COLUMN);
    }

    function getAvailableContracts($team_id) {
        $_ = getFinishedContracts($team_id);
        $__ = getActiveContracts($team_id);
        $___ = getConstraintedContracts($team_id);
        $____ = fetchAll("SELECT contract_id FROM contracts WHERE hidden=FALSE", null, PDO::FETCH_COLUMN);
        return array_diff($____, $___, $__, $_);
    }

    function getDynamicScore($task_id=null, $contract_id=null, $solver=false) {
        $total_penalty = 0;

        if (is_null($contract_id)) {
            $original = fetchScalar("SELECT cash FROM tasks WHERE task_id=:task_id", array("task_id" => $task_id));
            $task_ids = array($task_id);
        }
        else {
            $original = fetchScalar("SELECT SUM(cash) FROM tasks WHERE contract_id=:contract_id", array("contract_id" => $contract_id));
            $task_ids = fetchAll("SELECT task_id FROM tasks WHERE contract_id=:contract_id", array("contract_id" => $contract_id), PDO::FETCH_COLUMN);
        }

        if (parseBool(getSetting(Setting::DYNAMIC_SCORING))) {
            foreach ($task_ids as $task_id) {
                $task_cash = fetchScalar("SELECT cash FROM tasks WHERE task_id=:task_id", array("task_id" => $task_id));
                $task_penalty = 0;

                // Reference: https://github.com/CTFd/DynamicValueChallenge
                if (parseBool(getSetting(Setting::DYNAMIC_SCORING))) {
                    $solves = fetchScalar("SELECT COUNT(*) FROM solved WHERE task_id=:task_id", array("task_id" => $task_id));
                    if (($solves > 0) && $solver)
                        $solves -= 1;
                    $threshold = is_numeric(getSetting(Setting::DYNAMIC_SOLVE_THRESHOLD)) ? getSetting(Setting::DYNAMIC_SOLVE_THRESHOLD) : DEFAULT_DYNAMIC_SOLVE_THRESHOLD;
                    $min_percentage = 100.0 - (is_numeric(getSetting(Setting::DYNAMIC_MAXIMUM_DECAY)) ? getSetting(Setting::DYNAMIC_MAXIMUM_DECAY) : DEFAULT_DYNAMIC_MAXIMUM_DECAY);
                    $max_task_penalty = intval(((100.0 - $min_percentage) / 100.0) * $task_cash);
                    $task_penalty = intval(1.0 * $max_task_penalty * ($solves ** 2) / ($threshold ** 2));
                    $task_penalty = min($task_penalty, $max_task_penalty);
                }

// NOTE: old formula stuff
//                     define("DYNAMIC_DECAY_PER_SOLVE", 0.04);            // Decaying per-solve ratio (0 <= _ <= 1) used for calculating penalty from initial (task) score (e.g. if initial score 100, after 4 solves with decay ratio 0.1 penalty will become 40 -> effective score 60)
//                     define("DYNAMIC_DECAY_MAX_PENALTY", 0.8);           // Decaying max-penalty ratio (0 <= _ <= 1) used for calculating maximum penalty from initial (task) score (e.g. if initial score 100, with max-penalty ratio 0.20 maximum penalty will become 20 -> effective score 80)
//
//                 if (DYNAMIC_DECAY_PER_SOLVE > 0) {
//                     $solves = fetchScalar("SELECT COUNT(*) FROM solved WHERE task_id=:task_id", array("task_id" => $task_id));
//                     if (($solves > 0) && $solver)
//                         $solves -= 1;
//                     $task_penalty += intval($task_cash * (DYNAMIC_DECAY_PER_SOLVE * $solves));
//                     $task_penalty = min($task_penalty, DYNAMIC_DECAY_MAX_PENALTY * $task_cash);
//                 }

                $total_penalty += $task_penalty;
            }
        }

        return max(0, $original - $total_penalty);
    }

    function getRankedTeams($details=false) {
        $result = array();
        $teams = array();
        $rankings = array();

        if (getSetting(Setting::CTF_STYLE) === "ad") {
            $rows = fetchAll("SELECT teams.team_id,teams.full_name FROM teams WHERE teams.login_name!=:admin_login_name", array("admin_login_name" => ADMIN_LOGIN_NAME));
            foreach ($rows as $row)
                $teams[$row["team_id"]] = array("full_name" => $row["full_name"]);

            foreach ($teams as $team_id => $team) {
                $scores = getScores($team_id);
                $ranking = array("team_id" => $team_id, "full_name" => $team["full_name"], "flags" => $scores["flags"], "availability" => $scores["availability"]);
                array_push($rankings, $ranking);
            }

            usort($rankings, function ($team1, $team2) {
                if ($team1["flags"] + $team1["availability"] < $team2["flags"] + $team2["availability"])
                    return 1;
                else if ($team1["flags"] + $team1["availability"] > $team2["flags"] + $team2["availability"])
                    return -1;
                else if ($team1["full_name"] < $team2["full_name"])
                    return -1;
                else if ($team1["full_name"] > $team2["full_name"])
                    return 1;
                else
                    return 0;
            });
        }
        else {
            $rows = fetchAll("SELECT teams.team_id,teams.full_name,UNIX_TIMESTAMP(x.ts) AS ts FROM teams LEFT JOIN (SELECT team_id,MAX(ts) AS ts FROM solved GROUP BY team_id)x ON teams.team_id=x.team_id WHERE teams.login_name!=:admin_login_name ORDER BY x.ts DESC", array("admin_login_name" => ADMIN_LOGIN_NAME));
            foreach ($rows as $row)
                $teams[$row["team_id"]] = array("full_name" => $row["full_name"], "ts" => $row["ts"]);

            foreach ($teams as $team_id => $team) {
                $scores = getScores($team_id);
                $ranking = array("team_id" => $team_id, "full_name" => $team["full_name"], "cash" => $scores["cash"], "awareness" => $scores["awareness"], "ts" => $team["ts"]);
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
                else if ($team1["ts"] > $team2["ts"])
                    return 1;
                else if ($team1["ts"] < $team2["ts"])
                    return -1;
                else if ($team1["full_name"] < $team2["full_name"])
                    return -1;
                else if ($team1["full_name"] > $team2["full_name"])
                    return 1;
                else
                    return 0;
            });
        }

        if ($details)
            $result = $rankings;
        else {
            foreach ($rankings as $ranking)
                array_push($result, $ranking["team_id"]);
        }

        return $result;
    }

    function getPlaces($team_id) {
        $result = array();
        $teams = getTeams();

        if (getSetting(Setting::CTF_STYLE) === "ad") {
            $all = array("score" => array());

            foreach ($teams as $_) {
                $current = getScores($_);
                array_push($all["score"], $current["flags"] + $current["availability"]);
            }

            $current = getScores($team_id);
            $current["score"] = $current["flags"] + $current["availability"];

            $result["cash"] = 1;
            $result["awareness"] = 1;
        }
        else {
            $all = array("cash" => array(), "awareness" => array());

            foreach ($teams as $_) {
                $current = getScores($_);
                array_push($all["cash"], $current["cash"]);
                array_push($all["awareness"], $current["awareness"]);
            }

            $current = getScores($team_id);
        }

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
            if ($category) {
                $color = array_key_exists($category, PREDEFINED_COLORS) ? PREDEFINED_COLORS[$category] : substr(md5($category), 0, 6);
                $result .= '<span class="badge badge-light border mr-1" style="color:' . getContrastYIQ($color) . '; background-color: #' . $color . '">' . $category . '</span>';
            }
        }

        if (!$result)
            $result = "&nbsp;";

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
    function json_validator($data=null) {
        if (!empty($data)) {
            @json_decode($data);
            return (json_last_error() === JSON_ERROR_NONE);
        }
        return false;
    }

    function getSetting($name, $default=null) {
        static $memo = array();

        if (array_key_exists($name, $memo))
            $result = $memo[$name];
        else {
            $result = fetchScalar("SELECT value FROM settings WHERE name=:name", array("name" => $name));
            $memo[$name] = $result;
        }

        if (!is_null($default) && is_null($result))
            $result = $default;

        return $result;
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
        return nl2br(htmlspecialchars($value, ENT_QUOTES, "utf-8"), false);
    }

    function breakLongWords($value) {
        return preg_replace("/[^\s]{80}/", "$0\n", $value);
    }

    // Reference: https://stackoverflow.com/a/834355
    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function logMessage($message, $level=LogLevel::INFO, $details=null) {
        $team_id = isset($_SESSION["team_id"]) ? $_SESSION["team_id"] : null;
        $remote_ip = $_SERVER["REMOTE_ADDR"];
        return execute("INSERT INTO logs(level, team_id, remote_ip, message, details) VALUES(:level, :team_id, :remote_ip, :message, :details)", array("level" => $level, "team_id" => $team_id, "remote_ip" => $remote_ip, "message" => $message, "details" => $details));
    }

    function checkStartEndTime() {
        $running = false;
        $timed = false;

        if (strtotime(getSetting(Setting::DATETIME_START)) !== false) {
            if (strtotime(getSetting(Setting::DATETIME_START)) <= time()) {
                $running = true;
                $timed = true;
            }
        }

        if (strtotime(getSetting(Setting::DATETIME_END)) !== false) {
            if (strtotime(getSetting(Setting::DATETIME_END)) <= time()) {
                $running = false;
                $timed = true;
            }
        }

        if (!$timed)
            if (!is_null(getSetting(Setting::EXPLICIT_START_STOP)))
                $running = parseBool(getSetting(Setting::EXPLICIT_START_STOP));

        if (!$running) {
            if (isset($_SESSION["_login_name"])) {
                $_ = fetchScalar("SELECT endtime FROM teams WHERE login_name=:login_name", array("login_name" => $_SESSION["_login_name"]));
                if (strtotime($_) !== false) {
                    if (strtotime($_) > time())
                        $running = true;
                }
            }
        }

        return $running;
    }

    function wordMatch($a, $b) {
        $wordsA = array();
        $wordsB = array();

        preg_match_all('/\w+/', $a, $matchesA);
        preg_match_all('/\w+/', $b, $matchesB);

        return (count($matchesA[0]) === count($matchesB[0])) && (count(array_intersect($matchesA[0], $matchesB[0])) === count($matchesA[0]));
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    // Reference: https://stackoverflow.com/a/43956977
    function secondsToTime($inputSeconds) {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        // Extract days
        $days = floor($inputSeconds / $secondsInADay);

        // Extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // Extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // Extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        // Format and return
        $timeParts = [];
        $sections = [
            'day' => (int)$days,
            'hour' => (int)$hours,
            'minute' => (int)$minutes,
            'second' => (int)$seconds,
        ];

        foreach ($sections as $name => $value){
            if ($value > 0){
                $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }

        return implode(', ', $timeParts);
    }

    function signOut() {
        session_unset();
        session_destroy();
        session_write_close();
        setcookie(session_name(), '', 0, '/');
        header("Location: " . PATHDIR);
        die();
    }

    function truncate($string, $max_length) {
        if (!empty($string))
            $result = (strlen($string) > $max_length) ? substr($string, 0, $max_length) . "..." : $string;
        else
            $result = $string;

        return $result;
    }

    function parseBool($value) {
        $retval = false;

        if (is_bool($value))
            $retval = $value;
        elseif (is_numeric($value) && ($value > 0))
            $retval = true;
        elseif (is_string($value) && (strtolower($value) == "true"))
            $retval = true;

        return $retval;
    }
?>

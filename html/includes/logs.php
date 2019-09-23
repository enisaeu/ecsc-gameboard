<?php
    require_once("common.php");

    if (!isAdmin())
        die();
?>

                                <table id="logs_table" class="table table-hover table-condensed small mt-4 hidden">
                                    <thead>
                                        <tr><th>Timestamp</th><th>Level</th><th>Message</th><th>Details</th><th>Team name</th><th>Country</th><th>IP</th></tr>
                                    </thead>
                                    <tbody>
<?php
    $rows = fetchAll("SELECT *, UNIX_TIMESTAMP(logs.ts) AS _ts FROM logs LEFT JOIN teams ON logs.team_id=teams.team_id ORDER BY logs.ts DESC");
    foreach ($rows as $row) {
        echo format("                                        <tr><td>{timestamp}</td><td><span class='log-level' style='background-color: {color}'>{level}</span></td><td>{message}</td><td>{details}</td><td>{team}</td><td class='min'>{country}</td><td>{remote_ip}</td></tr>\n", array("timestamp" => date("Y/m/d H:i:s", $row["_ts"]), "color" => LOG_COLORS[$row["level"]], "level" => $row["level"], "message" => cleanReflectedValue($row["message"]), "details" => cleanReflectedValue($row["details"]), "team" => is_null($row["full_name"]) ? "" : cleanReflectedValue($row["full_name"]) . " <sup>(" . cleanReflectedValue($row["login_name"]) . ")</sup>", "country" => "<span class='flag-icon flag-icon-" . cleanReflectedValue(strtolower($row["country_code"])) . " ml-1' data-toggle='tooltip' title='" . cleanReflectedValue(strtoupper($row["country_code"])) . "'></span>", "remote_ip" => $row["remote_ip"]));
    }
?>
                                    </tbody>
                                </table>

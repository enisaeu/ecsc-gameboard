<?php
    if (!isAdmin())
        die();
?>

                                <table id="logs" class="table table-hover table-condensed small mt-4">
                                    <thead><tr><th>Timestamp</th><th>Level</th><th>Message</th><th>Details</th><th>Team</th><th>IP</th></tr></thead>
                                    <tbody>
<?php
    $rows = fetchAll("SELECT *, UNIX_TIMESTAMP(logs.ts) AS _ts FROM logs LEFT JOIN teams ON logs.team_id=teams.team_id ORDER BY logs.ts DESC");
    foreach ($rows as $row) {
        echo format("<tr><td>{timestamp}</td><td>{level}</td><td>{message}</td><td>{details}</td><td>{team}</td><td>{remote_ip}</td></tr>\n", array("timestamp" => date("Y/d/m H:i:s", $row["_ts"]), "level" => $row["level"], "message" => $row["message"], "details" => $row["details"], "team" => $row["full_name"], "remote_ip" => $row["remote_ip"]));
    }
?>
                                    </tbody>
                                </table>

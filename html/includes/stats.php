<?php
    require_once("common.php");

    if (!isAdmin())
        die();
?>

                                <table id="stats_table" class="table table-hover table-condensed small mt-4 hidden">
                                    <thead>
                                        <tr><th>Task</th><th>Contract</th><th>Cash value (&euro;)</th><th>Teams solved</th><th>Heartbeat</th><th>Avg. solve time</th></tr>
                                    </thead>
                                    <tbody>
<?php
    $average = array();
    $rows = fetchAll("SELECT solved.task_id,tasks.contract_id,AVG(TIMESTAMPDIFF(SECOND,accepted.ts,solved.ts)) AS average_time FROM solved JOIN tasks ON solved.task_id=tasks.task_id JOIN accepted ON tasks.contract_id=accepted.contract_id AND accepted.team_id=solved.team_id GROUP BY task_id");
    foreach ($rows as $row) {
        $average[$row["task_id"]] = $row["average_time"];
    }

    $heartbeats = array();
    $timestamps = array();
    $rows = fetchAll("SELECT task_id, GROUP_CONCAT(UNIX_TIMESTAMP(ts) ORDER BY ts ASC) AS times FROM solved GROUP BY task_id");
    $min = PHP_INT_MAX;
    $max = 0;

    foreach ($rows as $row) {
        $timestamps[$row["task_id"]] = explode(',', $row["times"]);
        $min = min($min, min($timestamps[$row["task_id"]]));
//         $max = max($max, max($timestamps[$row["task_id"]]));
    }

    $max = time();
    foreach ($timestamps as $task_id => $times) {
        $heartbeats[$task_id] = array();
        $heartbeats[$task_id] = array_pad($heartbeats[$task_id], HEARTBEAT_POINTS, 0);

        foreach ($times as $time) {
            $bucket = intval(($time - $min) / (1 + $max - $min) * HEARTBEAT_POINTS);
            $heartbeats[$task_id][$bucket] += 1;
        }
    }

    $rows = fetchAll("SELECT tasks.task_id,GROUP_CONCAT(DISTINCT teams.login_name ORDER BY teams.login_name ASC SEPARATOR ', ') AS solved_by,COUNT(teams.login_name) AS solved_count,tasks.title AS task_title,cash AS task_cash,tasks.contract_id,contracts.title AS contract_title FROM tasks LEFT JOIN solved ON tasks.task_id=solved.task_id LEFT JOIN teams ON solved.team_id=teams.team_id LEFT JOIN contracts ON tasks.contract_id=contracts.contract_id GROUP BY tasks.task_id ORDER BY solved_count DESC, task_cash ASC");
    foreach ($rows as $row) {
        if (!isset($heartbeats[$row["task_id"]])) {
            $task_id = $row["task_id"];
            $heartbeats[$task_id] = array();
            $heartbeats[$task_id] = array_pad($heartbeats[$task_id], HEARTBEAT_POINTS, 0);
        }

        echo format("                                        <tr><td>{task}</td><td>{contract}</td><td>{cash}</td><td data-order={solved_count}>{solved_more}</td><td class='heartbeat'>{heartbeat}</td><td data-order={average_seconds}>{average_time}</td></tr>\n", array("task" => $row["task_title"], "contract" => $row["contract_title"], "cash" => $row["task_cash"], "solved_count" => $row["solved_count"], "solved_more" => ($row["solved_count"] > 0 ? $row["solved_by"] : '-'), "heartbeat" => implode(',', $heartbeats[$row["task_id"]]), "average_seconds" => isset($average[$row["task_id"]]) ? intval($average[$row["task_id"]]) : 0, "average_time" => isset($average[$row["task_id"]]) ? secondsToTime($average[$row["task_id"]]) : '-'));
    }
?>
                                    </tbody>
                                </table>

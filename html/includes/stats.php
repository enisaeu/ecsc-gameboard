<?php
    if (!isAdmin())
        die();
?>

                                <table id="stats_table" class="table table-hover table-condensed small mt-4">
                                    <thead><tr><th>Task</th><th>Contract</th><th>Cash value (&euro;)</th><th>Teams solved</th><th>Avg. solve time</th></tr></thead>
                                    <tbody>
<!--                                         <tr><td>1</td><td>1</td><td>1</td><td>1</td><td>1</td></tr> -->
<?php
    $average = array();
    $rows = fetchAll("SELECT solved.task_id,tasks.contract_id,AVG(TIMESTAMPDIFF(SECOND,accepted.ts,solved.ts)) AS average_time FROM solved JOIN tasks ON solved.task_id=tasks.task_id JOIN accepted ON tasks.contract_id=accepted.contract_id AND accepted.team_id=solved.team_id GROUP BY task_id");
    foreach ($rows as $row) {
        $average[$row["task_id"]] = $row["average_time"];
    }

    $rows = fetchAll("SELECT tasks.task_id,GROUP_CONCAT(teams.login_name) AS solved_by,COUNT(teams.login_name) AS solved_count,tasks.title AS task_title,cash AS task_cash,tasks.contract_id,contracts.title AS contract_title FROM tasks LEFT JOIN solved ON tasks.task_id=solved.task_id LEFT JOIN teams ON solved.team_id=teams.team_id LEFT JOIN contracts ON tasks.contract_id=contracts.contract_id GROUP BY tasks.task_id ORDER BY solved_count DESC, task_cash ASC");
    foreach ($rows as $row) {
        echo format("<tr><td>{task}</td><td>{contract}</td><td>{cash}</td><td>{solved_count} {solved_more}</td><td>{average_time}</td></tr>", array("task" => $row["task_title"], "contract" => $row["contract_title"], "cash" => $row["task_cash"], "solved_count" => $row["solved_count"], "solved_more" => ($row["solved_count"] > 0 ? "<i class='far fa-question-circle' title=" . $row["solved_by"] . "></i>" : ""), "average_time" => isset($average[$row["task_id"]]) ? secondsToTime($average[$row["task_id"]]) : '-'));
    }
?>
                                    </tbody>
                                </table>

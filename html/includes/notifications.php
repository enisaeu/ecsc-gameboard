<?php
    $template = file_get_contents("templates/notification.html");
    $visible = getVisibleNotifications($_SESSION["team_id"]);

    if (isAdmin()) {
        $template = str_replace("Hide", "Delete", $template);
        $template = str_replace("hide", "delete", $template);
        $template = str_replace("×", "<i class='far fa-trash-alt ml-1 mb-1' style='font-size: 13px; line-height: 24px; vertical-align: middle; height: 100%' title='Delete'></i>", $template);
    }

    if (count($visible) > 0)
        foreach ($visible as $notification_id) {
            $row = fetchAll("SELECT notification_id, content, category, UNIX_TIMESTAMP(ts) AS ts FROM notifications WHERE notification_id=:notification_id ORDER BY notification_id DESC", array("notification_id" => $notification_id))[0];
            echo format($template, array("category" => (is_null($row["category"]) ? "info" : $row["category"]), "content" => cleanReflectedValue($row["content"]), "notification_id" => $notification_id, "time" => $row["ts"]));
        }

    if (isAdmin()) {
        echo '<div class="ml-4 mb-3"><button class="btn btn-info" onclick="showSendNotificationBox()">New notification</button></div>';
    }
?>
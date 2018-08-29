<?php
    if (isAdmin() && ($_POST["action"] === "reset")) {
        $success = true;

        if ($_POST["teams"] == "true")
            $success &= execute("DELETE FROM teams WHERE login_name!=:login_name", array("login_name" => ADMIN_LOGIN_NAME));

        if ($_POST["contracts"] == "true")
            $success &= execute("DELETE FROM contracts");

        if ($_POST["chat"] == "true")
            $success &= execute("DELETE FROM chat");

        if ($_POST["privates"] == "true")
            $success &= execute("DELETE FROM privates");

        if ($_POST["auxiliary"] == "true") {
            $success &= execute("DELETE FROM solved");
            $success &= execute("DELETE FROM accepted");
            $success &= execute("DELETE FROM notifications");
            $success &= execute("DELETE FROM hide");
        }

        if ($success)
            die("OK");
        else {
            header("HTTP/1.1 500 Internal Server Error");
            die(DEBUG ? $_SESSION["conn_error"] : null);
        }
    }
    else if (isAdmin() && ($_POST["action"] === "delete")) {
        $success = false;

        if (isset($_POST["task_id"]))
            $success = deleteTask($_POST["task_id"]);
        else if (isset($_POST["contract_id"]))
            $success = deleteContract($_POST["contract_id"]);
        else if (isset($_POST["team_id"]))
            $success = deleteTeam($_POST["team_id"]);
        else if (isset($_POST["login_name"]))
            $success = execute("DELETE FROM teams WHERE login_name=:login_name", array("login_name" => $_POST["login_name"]));
        else if (isset($_POST["notification_id"]))
            $success = execute("DELETE FROM notifications WHERE notification_id=:notification_id", array("notification_id" => $_POST["notification_id"]));

        if ($success)
            die("OK");
        else {
            header("HTTP/1.1 500 Internal Server Error");
            die(DEBUG ? $_SESSION["conn_error"] : null);
        }
    }
    else if (isAdmin() && ($_POST["action"] === "notification") && isset($_POST["message"])) {
        $success = execute("INSERT INTO notifications(content, category) VALUES(:message, :category)", array("message" => $_POST["message"], "category" => NotificationCategories::everybody));

        if ($success)
            die("OK");
        else {
            header("HTTP/1.1 500 Internal Server Error");
            die(DEBUG ? $_SESSION["conn_error"] : null);
        }
    }
    else if ($_POST["action"] === "hide") {
        $success = execute("INSERT INTO hide(notification_id, team_id) VALUES(:notification_id, :team_id)", array("notification_id" => $_POST["notification_id"], "team_id" => $_SESSION["team_id"]));
        if ($success)
            die("OK");
        else {
            header("HTTP/1.1 500 Internal Server Error");
            die(DEBUG ? $_SESSION["conn_error"] : null);
        }
    }
    else if ($_POST["action"] === "update") {
        global $conn;

        if (isset($_POST["password"])) {
            $success = execute("UPDATE teams SET password_hash=:password_hash WHERE team_id=:team_id", array("team_id" => $_SESSION["team_id"], "password_hash" => password_hash($_POST["password"], PASSWORD_BCRYPT)));

            if ($success)
                die("OK");
            else {
                header("HTTP/1.1 500 Internal Server Error");
                die(DEBUG ? $_SESSION["conn_error"] : null);
            }
        }
        else if (isAdmin() && isset($_POST["contract"])) {
            $contract = json_decode($_POST["contract"], true);

            $success = true;
            $conn->beginTransaction();

            if ($contract["contract_id"] != -1) {
                $success &= execute("UPDATE contracts SET title=:title, description=:description, categories=:categories WHERE contract_id=:contract_id", array("title" => $contract["title"], "description" => $contract["description"], "categories" => $contract["categories"], "contract_id" => $contract["contract_id"]));
                $contract_id = $contract["contract_id"];
            }
            else {
                $success &= execute("INSERT INTO contracts(title, description, categories) VALUES (:title, :description, :categories)", array("title" => $contract["title"], "description" => $contract["description"], "categories" => $contract["categories"]));
                $contract_id = fetchScalar("SELECT MAX(contract_id) FROM contracts WHERE title=:title", array("title" => $contract["title"]));
            }

            $existing = fetchAll("SELECT task_id FROM tasks WHERE contract_id=:contract_id", array("contract_id" => $contract_id), PDO::FETCH_COLUMN);
            $updated = array();
            foreach ($contract["tasks"] as $task)
                array_push($updated, $task["task_id"]);

            $deleted = array_diff($existing, $updated);
            foreach ($deleted as $task_id)
                $success &= deleteTask($task_id);

            foreach ($contract["tasks"] as $task) {
                if ($task["task_id"] != -1)
                    $success &= execute("UPDATE tasks SET title=:title, description=:description, answer=:answer, cash=:cash, awareness=:awareness WHERE task_id=:task_id", array("title" => $task["title"], "description" => $task["description"], "answer" => $task["answer"], "cash" => $task["cash"], "awareness" => $task["awareness"], "task_id" => $task["task_id"]));
                else
                    $success &= execute("INSERT INTO tasks(contract_id, title, description, answer, cash, awareness) VALUES(:contract_id, :title, :description, :answer, :cash, :awareness)", array("contract_id" => $contract_id, "title" => $task["title"], "description" => $task["description"], "answer" => $task["answer"], "cash" => $task["cash"], "awareness" => $task["awareness"]));
            }

            execute("DELETE FROM constraints WHERE contract_id=:contract_id", array("contract_id" => $contract["contract_id"]));
            if (isset($contract["constraints"])) {
                $min_cash = empty($contract["constraints"]["min_cash"]) ? null : $contract["constraints"]["min_cash"];
                $min_awareness = empty($contract["constraints"]["min_awareness"]) ? null : $contract["constraints"]["min_awareness"];

                if (!is_null($min_cash) || !is_null($min_awareness))
                    $success &= execute("INSERT INTO constraints(contract_id, min_cash, min_awareness) VALUES(:contract_id, :min_cash, :min_awareness)", array("contract_id" => $contract["contract_id"], "min_cash" => $min_cash, "min_awareness" => $min_awareness));
            }

            if ($success)
                $conn->commit();
            else
                $conn->rollback();

            if ($success)
                die("OK");
            else {
                header("HTTP/1.1 500 Internal Server Error");
                die(DEBUG ? $_SESSION["conn_error"] : null);
            }
        }
        else if (isAdmin() && isset($_POST["team"])) {
            $team = json_decode($_POST["team"], true);

            if ($team["team_id"] == -1) {
                $success = execute("INSERT INTO teams(login_name, full_name, country_code, email, password_hash) VALUES(:login_name, :full_name, :country_code, :email, :password_hash)", array("login_name" => $team["login_name"], "full_name" => $team["full_name"], "country_code" => $team["country_code"], "email" => $team["email"], "password_hash" => password_hash($team["password"], PASSWORD_BCRYPT)));
            }
            else {
                if (isset($team["password"]) && ($team["password"] != ""))
                    $success = execute("UPDATE teams SET full_name=:full_name, country_code=:country_code, email=:email, password_hash=:password_hash WHERE team_id=:team_id", array("team_id" => $team["team_id"], "full_name" => $team["full_name"], "country_code" => $team["country_code"], "email" => $team["email"], "password_hash" => password_hash($team["password"], PASSWORD_BCRYPT)));
                else
                    $success = execute("UPDATE teams SET full_name=:full_name, country_code=:country_code, email=:email WHERE team_id=:team_id", array("team_id" => $team["team_id"], "full_name" => $team["full_name"], "country_code" => $team["country_code"], "email" => $team["email"]));
            }

            if ($success)
                die("OK");
            else {
                header("HTTP/1.1 500 Internal Server Error");
                die(DEBUG ? $_SESSION["conn_error"] : null);
            }
        }
    }
    else if ($_POST["action"] === "status") {
        if (isAdmin()) {
            $result = array();
        }
        else {
            $result = array("progress" => array("cash" => array(0), "awareness" => array(0)), "places" => array());
            $_ = array_values(getMomentum($_SESSION["team_id"]))[0];

            foreach (array("cash", "awareness") as $key) {
                foreach ($_[$key] as $current) {
                    array_push($result["progress"][$key], $current["y"]);
                }
            }

            foreach (getPlaces($_SESSION["team_id"]) as $key => $value)
                $result["places"][$key] = $value;
        }

        $result["team_name"] = fetchScalar("SELECT full_name FROM teams WHERE team_id=:team_id", array("team_id" => $_SESSION["team_id"]));
        $result["notifications"] = getNotifications($_SESSION["team_id"]);

        echo json_encode($result);
    }

    else if ($_POST["action"] === "momentum") {
        echo json_encode(getMomentum_new());
    }
    else if (($_POST["action"] === "push") && (isset($_POST["message"]))) {
        $success = execute("INSERT INTO chat(team_id, content) VALUES(:team_id, :content)", array("team_id" => $_SESSION["team_id"], "content" => $_POST["message"]));
        if ($success)
            die("OK");
        else {
            header("HTTP/1.1 500 Internal Server Error");
            die(DEBUG ? $_SESSION["conn_error"] : null);
        }
    }
    else if (($_POST["action"] === "private") && (isset($_POST["to"])) && (isset($_POST["message"]) || isset($_POST["cash"]))) {
        $cash = (isset($_POST["cash"]) && is_numeric($_POST["cash"])) ? intval($_POST["cash"]) : NULL;
        $message = (isset($_POST["message"])) ? $_POST["message"] : NULL;
        $to_id = fetchScalar("SELECT team_id FROM teams WHERE login_name=:login_name", array("login_name" => $_POST["to"]));
        $max = getScores($_SESSION["team_id"])["cash"];

        if (!is_null($to_id) && ($_SESSION["team_id"] !== $to_id) && !(!is_null($cash) && ($cash > $max)) && !((is_null($cash) || $cash === 0) && (is_null($message) || $message === ""))) {
            $from_name = fetchScalar("SELECT full_name FROM teams WHERE team_id=:team_id", array("team_id" => $_SESSION["team_id"]));
            $to_name = fetchScalar("SELECT full_name FROM teams WHERE team_id=:team_id", array("team_id" => $to_id));
            $success = execute("INSERT INTO privates(from_id, to_id, cash, message) VALUES(:from_id, :to_id, :cash, :message)", array("from_id" => $_SESSION["team_id"], "to_id" => $to_id, "cash" => $cash, "message" => $message));
            if ($success) {
                if ($cash) {
                    execute("INSERT INTO notifications(team_id, content, category) VALUES(:team_id, :content, :category)", array("team_id" => $to_id, "content" => "Team '" . $from_name . "' sent you " . $cash . "€" . ($message ? " with a message '" . $message . "'" : ""), "category" => NotificationCategories::received_private));
                    execute("INSERT INTO notifications(team_id, content, category) VALUES(:team_id, :content, :category)", array("team_id" => $_SESSION["team_id"], "content" => "You sent " . $cash . "€" . ($message ? " with a message '" . $message . "'" : "") . " to team '" . $to_name . "'", "category" => NotificationCategories::sent_private));
                }
                else {
                    execute("INSERT INTO notifications(team_id, content, category) VALUES(:team_id, :content, :category)", array("team_id" => $to_id, "content" => "Team '" . $from_name . "' sent you a private message '" . $message . "'", "category" => NotificationCategories::received_private));
                    execute("INSERT INTO notifications(team_id, content, category) VALUES(:team_id, :content, :category)", array("team_id" => $_SESSION["team_id"], "content" => "You sent to a private message '" . $message . "' to team '" . $to_name . "'", "category" => NotificationCategories::sent_private));
                }
            }
        }
        else
            $success = false;

        if ($success)
            die("OK");
        else {
            header("HTTP/1.1 500 Internal Server Error");
            die(DEBUG ? $_SESSION["conn_error"] : null);
        }
    }
    else if ($_POST["action"] === "pull") {
        $result = array("chat" => array(), "notifications" => 0);

        $chat_id = isset($_POST["chat_id"]) ? intval($_POST["chat_id"]) : 0;
        $private_id = isset($_POST["private_id"]) ? intval($_POST["private_id"]) : 0;
        $chat = fetchAll("SELECT message_id, login_name, country_code, content, UNIX_TIMESTAMP(chat.ts) AS ts FROM chat JOIN teams ON chat.team_id=teams.team_id WHERE message_id>:message_id ORDER BY ts ASC", array("message_id" => $chat_id));

        foreach ($chat as $row) {
            $_ = array("id" => $row["message_id"], "team" => $row["login_name"], "country" => $row["country_code"], "content" => $row["content"], "ts" => $row["ts"]);
            array_push($result["chat"], $_);
        }

        $result["notifications"] = count(getVisibleNotifications($_SESSION["team_id"]));

        echo json_encode($result);
    }
?>
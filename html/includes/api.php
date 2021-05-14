<?php
    require_once("includes/common.php");
    require_once("includes/exports.php");

    preg_match('/\/api\/(.+)/', $_SERVER["REQUEST_URI"], $matches);
    $parts = explode('/', $matches[1]);
    $method = $parts[0];
    $args = array_slice($parts, 1);
    $body = json_decode(file_get_contents("php://input"), true);
    $authorized = False;

    # access_token, expires_in (JSON)
    # Authorization: Bearer

    execute("DELETE FROM tokens WHERE TIMESTAMPDIFF(SECOND,ts,CURRENT_TIMESTAMP())>:token_life", array("token_life" => TOKEN_LIFE));

    if ($method === "auth") {
        if ($args[0] === "token") {
            if ($body["username"] === ADMIN_LOGIN_NAME) {
                $rows = fetchAll("SELECT password_hash FROM teams WHERE login_name=:login", array("login" => ADMIN_LOGIN_NAME));
                if (count($rows) === 1)
                    if (password_verify($body["password"], $rows[0]["password_hash"])) {
                        $token = generateRandomString(32);
                        execute("INSERT INTO tokens(value) VALUES(:value)", array("value" => $token));
                        header("Content-Type: application/json");
                        die(json_encode(array("access_token" => $token, "expires_in" => TOKEN_LIFE)));
                    }
            }
        }
    }

    if ((getBearerToken() !== null) && (count(fetchAll("SELECT * FROM tokens WHERE value=:value", array("value" => getBearerToken()))) > 0)) {
        $authorized = True;
    }

    if (!$authorized) {
        header("HTTP/1.1 401 Unauthorized");
        die();
    }

    if ($method === "export") {
        if ($args[0] === "logs") {
        }
        else if ($args[0] === "stats") {
        }
        echo 1;
    }
    else if ($method === "action") {
    }
?>

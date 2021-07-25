<?php
    session_regenerate_id(true);

    require_once("common.php");

    $error = false;
    $time_out = false;
    $wrong_captcha = false;

    if (isset($_POST["token"]) && isset($_SESSION["token"]) && ($_POST["token"] === $_SESSION["token"])) {
        if (isset($_POST["login"]) && isset($_POST["password"])) {

            if ($_POST["login"] !== ADMIN_LOGIN_NAME)
                $time_out = $error = !checkStartEndTime();

            if (CAPTCHA_ENABLED) {
                if (!isset($_POST["captcha"]) || !isset($_SESSION["captcha_text"]) || strtoupper($_POST["captcha"]) !== strtoupper($_SESSION["captcha_text"]))
                    $wrong_captcha = $error = true;
                
                if (isset($_SESSION["captcha_text"]))
                    unset($_SESSION["captcha_text"]);
            }

            if (!$error) {
                $rows = fetchAll("SELECT team_id, full_name, password_hash, guest FROM teams WHERE login_name=:login", array("login" => $_POST["login"]));
                if (count($rows) === 1)
                    if (password_verify($_POST["password"], $rows[0]["password_hash"])) {
                        $_SESSION["login_name"] = $_POST["login"];
                        $_SESSION["full_name"] = $rows[0]["full_name"];
                        $_SESSION["team_id"] = $rows[0]["team_id"];
                        $_SESSION["guest"] = $rows[0]["guest"];
                        logMessage("Login success", LogLevel::DEBUG);
                        header("Location: " . PATHDIR);
                        die();
                    }
            }

            if (strlen($_POST["password"]) > 2)
                $masked = substr($_POST["password"], 0, 1) . str_repeat('*', strlen($_POST["password"]) - 2) . substr($_POST["password"], -1);
            else
                $masked = str_repeat('*', strlen($_POST["password"]));

            $msg = $_POST["login"] . ":" . $masked;

            if ($time_out)
                $msg = "Out of time boundaries ('" . $msg . "')";
            else if ($wrong_captcha)
                $msg = "Wrong captcha ('" . $msg . "')";

            logMessage("Login failed", LogLevel::WARNING, $msg);

            $error = true;
        }
    }

    require_once("header.php");

    if ($time_out) {
        echo <<<END
        <script>
            alert("Competition is stopped");
        </script>

END;
    }
    else if ($wrong_captcha) {
        echo <<<END
        <script>
            alert("Wrong captcha");
        </script>

END;
    }
    else if ($error) {
        echo <<<END
        <script>
            $(document).ready(function() {
                $("input").effect("highlight", {color: "red"});
            });
        </script>

END;
    }
?>
        <script>
            $(document).ready(function() {
                dialog = $("#login-box");
                dialog.on('shown.bs.modal', function () {
                    $(this).find('[name=login]').focus();
                });
                dialog.modal("show");
            });
        </script>
    </head>
    <body>
        <!-- Modal HTML -->
        <div id="login-box" class="modal" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-login">
                <div class="modal-content">
                    <div class="modal-header h-100" style="display:block">
                         <img src="<?php echo joinPaths(PATHDIR, '/resources/logo_small.png');?>" class="img-fluid">
                    </div>
                    <div class="modal-body">
                        <form method="post">
                            <input name="token" value="" type="hidden">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fas fa-user mt-2"></i></span>
                                    <input type="text" class="form-control" name="login" placeholder="Login account" required="required" autofocus>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fas fa-lock mt-2"></i></span>
                                    <input type="password" class="form-control" name="password" placeholder="Password" required="required" autocomplete="off">
                                </div>
                            </div>
<?php
    if (CAPTCHA_ENABLED) {
        $html = <<<END
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fas fa-image mt-2"></i></span>
                                    <input type="text" class="form-control" name="captcha" placeholder="Captcha text" required="required" autocomplete="off">
                                </div>
                            </div>
                            <div class="mb-4">
                                <img id="captcha-image" src="%s" alt="Captcha image" class="center" title="Click to change" onclick="$(this).attr('src', $(this).attr('src').split('?')[0] + '?' + Date.now())">
                            </div>
END;
        echo sprintf($html, joinPaths(PATHDIR, '/captcha') . '?' . time());
    }
?>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer"><?php echo TITLE; ?> platform</div>
                </div>
            </div>
        </div>

<?php
    require_once("includes/footer.php");
?>

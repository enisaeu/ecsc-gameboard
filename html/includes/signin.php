<?php
    $error = false;
    if (isset($_POST["token"]) && isset($_SESSION["token"]) && ($_POST["token"] === $_SESSION["token"])) {
        if (isset($_POST["login"]) && isset($_POST["password"])) {
            $error = false;

            if ($_POST["login"] !== ADMIN_LOGIN_NAME) {
                $error = !checkStartEndTime();
            }

            if (!$error) {
                $rows = fetchAll("SELECT team_id, full_name, password_hash FROM teams WHERE login_name=:login", array("login" => $_POST["login"]));
                if (count($rows) === 1)
                    if (password_verify($_POST["password"], $rows[0]["password_hash"])) {
                        $_SESSION["login_name"] = $_POST["login"];
                        $_SESSION["full_name"] = $rows[0]["full_name"];
                        $_SESSION["team_id"] = $rows[0]["team_id"];
                        header("Location: " . PATHDIR);
                        die();
                    }
            }

            $error = true;
        }
    }

    require_once("header.php");

    if ($error)
        echo <<<END
        <script>
            $(document).ready(function() {
                $("input").effect("highlight", {color: "red"});
            });
        </script>
END;
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
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
                            </div>
                        </form>
                    </div>
        <!--            <div class="modal-footer">Don't have an account?&nbsp;<a href="#">Create one</a></div>-->
                    <div class="modal-footer"><?php echo TITLE; ?> platform</div>
                </div>
            </div>
        </div>
        <script>
<?php
$value = "";
for ($i = 0; $i < strlen($_SESSION["token"]); $i++) {
    if ($value)
        $value .= ",";
    $value .= ord($_SESSION["token"][$i]);
}
echo "              document.token = String.fromCharCode(" . $value . ");\n";
?>
        </script>
        <noscript>
            Javascript is disabled in your browser. You must have Javascript enabled to utilize the functionality of this page!
        </noscript>
    </body>
</html>

<?php
        if (isset($_POST["login_name"]) && isset($_POST["edit"])) {
            $existing = $_POST["login_name"] != "";

            if ($existing)
                $team = fetchAll("SELECT * FROM teams WHERE login_name=:login_name", array("login_name" => $_POST["login_name"]))[0];
            else
                $team = array("team_id" => "-1", "login_name" => "", "full_name" => "", "country_code" => "", "email" => "");

            $template = file_get_contents("templates/team.html");
            $html = format($template, array("action" => $existing ? "Update" : "Create", "team_id" => $team["team_id"], "login_name" => $team["login_name"], "full_name" => $team["full_name"], "email" => $team["email"]));

            if (!$existing)
                $html = preg_replace('/placeholder="[^"]+" /', "", $html);

            echo $html;

            $script = <<<END

            <script>
                $(document).ready(function() {
                    var existing = %s;
                    $(".active").text(existing ? "Edit team" : "New team");

                    $("#team_editor label:contains('Login name'),label:contains('Full name')").prev().change(validator).keyup(validator).focusout(validator).each(validator);

                    if (existing)
                        $("#team_editor label:contains('Login name')").prev().prop("disabled", true);

                    countriesDropdown(".country");

                    $(".country").val("%s");

                    $("#team_editor input").keypress(function(e) {
                        if(e.which == 13) {
                            $("#team_editor .btn-primary").click();
                        }
                    });

                    var _ = function() {
                        var password = $("#team_editor label:contains('Password')").prev().val();
                        var reenter = $("#team_editor label:contains('Re-enter password')").prev().val();

                        if (password) {
                            if (reenter != password)
                                $("#team_editor label:contains('Re-enter password')").prev().addClass("is-invalid");
                            else {
                                $("#team_editor label:contains('Password'),label:contains('Re-enter password')").prev().removeClass("is-invalid");
                            }
                        }
                        else if (!existing) {
                            $("#team_editor label:contains('Password')").prev().addClass("is-invalid");

                            if (reenter == "")
                                $("#team_editor label:contains('Re-enter password')").prev().addClass("is-invalid");
                            else
                                $("#team_editor label:contains('Re-enter password')").prev().removeClass("is-invalid");
                        }
                        else if (reenter == "")
                            $("#team_editor label:contains('Password'),label:contains('Re-enter password')").prev().removeClass("is-invalid");
                    }

                    $("#team_editor").find("label:contains('Password'),label:contains('Re-enter password')").prev().change(_).keyup(_).focusout(_).each(_);

                    $("#team_editor .btn-primary").click(function() {
                        invalid = $(".is-invalid").first();

                        if (invalid.length) {
                            $([document.documentElement, document.body]).animate({scrollTop: $(invalid).offset().top}, "fast", function() {
                                $(invalid).focus()
                            });
                        }
                        else {
                            var team = {};

                            team["team_id"] = $("#team_editor [name=team_id]").prop("value");
                            team["login_name"] = $("#team_editor label:contains('Login name')").prev().val();
                            team["full_name"] = $("#team_editor label:contains('Full name')").prev().val();
                            team["country_code"] = $("#team_editor label:contains('Country')").prev().val();
                            team["email"] = $("#team_editor label:contains('Email')").prev().val();
                            team["password"] = $("#team_editor label:contains('Password')").prev().val();

                            $.post(window.location.href.split('#')[0], {token: document.token, action: "update", team: JSON.stringify(team)}, function(content) {
                                if (content !== "OK")
                                    alert("Something went wrong ('" + content + "')!");
                                else
                                    reload();
                            }).fail(function(xhr, status, error) {
                                alert("Something went wrong ('" + (xhr.responseText || (xhr.status + " " + error)) + "')!");
                            });
                        }
                    });

                });
            </script>

END;
            echo sprintf($script, $existing ? "true" : "false", $team["country_code"]);

            return;
        }

?>

                                <div id="line_momentum" style="height: 370px; width: 100%;"></div>
                                <table id="scoreboard" class="table table-hover table-condensed small mt-4">
                                    <thead><tr><th>#</th><th>Team name</th><th>Country</th><th style="white-space:nowrap">Cash (&euro;)</th><th>Awareness</th><th>Actions</th></tr></thead>
                                    <?php
                                        $previous = -1;
                                        $place = 0;
                                        $teams = getRankedTeams();
                                        $counter = 0;

                                        foreach ($teams as $team_id) {
                                            $counter += 1;

                                            $row = fetchAll("SELECT login_name, full_name, country_code FROM teams WHERE team_id=:team_id", array("team_id" => $team_id))[0];
                                            $scores = getScores($team_id);

                                            if ($scores["cash"] !== $previous) {
                                                $place += 1;
                                                $previous = $scores["cash"];
                                                $_ = $place;
                                            }
                                            else
                                                $_ = "=";

                                            echo "<tr" . ($_SESSION["full_name"] == $row["full_name"] ? " class='current'" : "") . "><td value='" . $counter . "' class='min'><span>" . $_ . "</span></td><td class='full_name'>" . cleanReflectedValue($row["full_name"]) . " <sup>(" . cleanReflectedValue($row["login_name"]) . ")</sup></td><td class='min'><span class='flag-icon flag-icon-" . cleanReflectedValue(strtolower($row["country_code"])) . " ml-1' data-toggle='tooltip' title='" . cleanReflectedValue(strtoupper($row["country_code"])) . "'></span></td><td>" . number_format($scores["cash"]) . "</td><td>". number_format($scores["awareness"]) . "</td><td class='min actions'>" . ($_SESSION["full_name"] == $row["full_name"] ? "<i class='fas fa-sign-out-alt ml-1' data-toggle='tooltip' title='Sign out' onclick='signOut()'></i>" : (ENABLE_PRIVATE_MESSAGES ? "<i class='far fa-envelope ml-1' data-toggle='tooltip' style='vertical-align: middle' title='Send private message'></i>" : "") . (isAdmin() ? "<i class='fas fa-hand-holding-usd ml-1' data-toggle='tooltip' title='Award cash'></i>" : "<i class='fas fa-money-bill-wave ml-1' data-toggle='tooltip' title='Send cash'></i>") . (isAdmin() ? "<i class='far fa-edit ml-1' data-toggle='tooltip' title='Edit team'></i>" : "") . (isAdmin() ? "<i class='far fa-trash-alt ml-1' data-toggle='tooltip' title='Delete team'></i>" : "")). "</td></tr>";
                                        }

                                        if (isAdmin())
                                            echo "<tr><td value='" . ($counter + 1) . "' class='min'><span>?</span></td><td class='full_name'>??? <sup>(???)</sup></td><td class='min'><span class='flag-icon ml-1' data-toggle='tooltip' title='?'></span></td><td>?</td><td>?</td><td class='min actions'><i class='far fa-file' data-toggle='tooltip' title='New team'></i></td></tr>";
                                    ?>

                                </table>
<?php
    if (isAdmin()) {
        $script = <<<END
                                <script>
                                    $(document).ready(function() {
                                        function deleteTeam(login_name) {
                                            $.post(window.location.href.split('#')[0], {token: document.token, action: "delete", login_name: login_name}, function(content) {
                                                if (content !== "OK")
                                                    alert("Something went wrong ('" + content + "')!");
                                                else
                                                    reload();
                                            });
                                        }

                                        $(".fa-edit").click(function() {
                                            var row = $(this).closest("tr");
                                            var login_name = row.find("sup").text().substr(1).slice(0, -1);
                                            var full_name = row.find("td:nth-child(2)").html().replace(/ <sup>.+/, "").replace(/<span.+<\/span>/, "");

                                            var form = $('<form method="post"></form>');
                                            $('<input name="token" value="' + document.token + '" type="hidden">').appendTo(form);
                                            $('<input name="login_name" value="' + login_name + '" type="hidden">').appendTo(form);
                                            $('<input name="edit" value="true" type="hidden">').appendTo(form);
                                            form.appendTo("body").submit();
                                        });

                                        $(".fa-file").click(function() {
                                            var form = $('<form method="post"></form>');
                                            $('<input name="token" value="' + document.token + '" type="hidden">').appendTo(form);
                                            $('<input name="login_name" value="" type="hidden">').appendTo(form);
                                            $('<input name="edit" value="true" type="hidden">').appendTo(form);
                                            form.appendTo("body").submit();
                                        });

                                        $(".fa-trash-alt").click(function() {
                                            var row = $(this).closest("tr");
                                            var login_name = row.find("sup").text().substr(1).slice(0, -1);
                                            var full_name = row.find("td:nth-child(2)").html().replace(/ <sup>.+/, "").replace(/<span.+<\/span>/, "");

                                            showYesNoWarningBox("Are you sure that you want to delete team '" + full_name + "'?", function() {deleteTeam(login_name);});
                                        });
                                    });
                                </script>

END;
        echo $script;
    }
    else {
        if (getSetting("transfers") === "false") {
        $script = <<<END
                                <script>
                                    $(document).ready(function() {
                                        $(".fa-money-bill-wave").addClass("fa-disabled");
                                    });
                                </script>

END;
        echo $script;
        }
    }
?>

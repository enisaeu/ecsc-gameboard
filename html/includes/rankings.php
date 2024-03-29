<?php
    require_once("common.php");

    if (isAdmin() && isset($_POST["login_name"]) && isset($_POST["edit"])) {
        $existing = $_POST["login_name"] != "";

        if ($existing)
            $team = fetchAll("SELECT * FROM teams WHERE login_name=:login_name", array("login_name" => $_POST["login_name"]))[0];
        else
            $team = array("team_id" => "-1", "login_name" => "", "full_name" => "", "country_code" => "", "email" => "", "endtime" => "", "guest" => "0");

        $template = file_get_contents("templates/team.html");
        $html = format($template, array("action" => $existing ? "Update" : "Create", "team_id" => $team["team_id"], "login_name" => $team["login_name"], "full_name" => $team["full_name"], "email" => $team["email"], "endtime" => $team["endtime"], "guest_checked" => ($team["guest"] ? " checked" : "")));

        if (!$existing)
            $html = preg_replace('/placeholder="[^"]+" /', "", $html);

        echo $html;

        $script = <<<END

            <script>
                $(document).ready(function() {
                    var existing = %s;
                    $("a.nav-link.active").text(existing ? "Edit team" : "New team");

                    $("#team_editor label:contains('Login name'),label:contains('Full name'),label:contains('Email')").prev().change(validator).keyup(validator).focusout(validator).each(validator);

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
                            team["endtime"] = $("#team_editor label:contains('End time')").prev().val();
                            team["guest"] = $("#team_editor label:contains('Guest')").prev().prop("checked");

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

<?php
    if (getSetting(Setting::CTF_STYLE) !== "ad") {
        $html = <<<END
                                <div id="tab_line_momentum jeopardy" align="right">
                                    <i class="fas fa-external-link-alt jeopardy" data-toggle="tooltip" title="Open in new tab" onclick="openInNewTab('%s')"></i>
                                </div>
                                <div id="line_momentum" style="width: 100%%; height: 370px"></div>
END;
        $html = sprintf($html, joinPaths(PATHDIR, '/scoreboard'));
        echo $html;
    }
?>
                                <table id="scoreboard" class="table table-hover table-condensed small mt-4">
<?php
    if (getSetting(Setting::CTF_STYLE) !== "ad")
        $html = <<<END
                                    <thead><tr><th>#</th><th>Team name</th><th>Country</th><th style="white-space:nowrap">Cash (<i class="currency"></i>)</th><th class="awareness">Awareness</th><th>Actions</th></tr></thead>
END;
    else
        $html = <<<END
                                    <thead><tr><th>#</th><th>Team name</th><th>Country</th><th style="white-space:nowrap">Flags</th><th>Availability</th><th>Actions</th></tr></thead>
END;
        echo $html;
?>

                                    <?php
                                        $previous = -1;
                                        $place = 0;
                                        $teams = getRankedTeams();
                                        $counter = 0;
                                        $initial_availability = is_null(getSetting(Setting::INITIAL_AVAILABILITY)) ? DEFAULT_INITIAL_AVAILABILITY: getSetting(Setting::INITIAL_AVAILABILITY);

                                        $solves = array();
                                        $rows = fetchAll("SELECT team_id, GROUP_CONCAT(REPLACE(title, ', ', ',') ORDER BY 1 ASC SEPARATOR ', ') AS titles FROM solved JOIN tasks ON solved.task_id=tasks.task_id GROUP BY team_id");
                                        foreach ($rows as $row)
                                            $solves[$row["team_id"]] = $row["titles"];

                                        foreach ($teams as $team_id) {
                                            $counter += 1;

                                            $row = fetchAll("SELECT login_name, full_name, country_code, guest FROM teams WHERE team_id=:team_id", array("team_id" => $team_id))[0];

//                                             if (!isAdmin() && $row["guest"] != $_SESSION["guest"])
//                                                 continue;
//
                                            $scores = getScores($team_id);

                                            if ($row["guest"]) {
                                                $_ = "";
                                            }
                                            else {
                                                if (SAME_CASH_SAME_RANK) {
                                                    if ($scores["cash"] !== $previous) {
                                                        $place += 1;
                                                        $previous = $scores["cash"];
                                                        $_ = $place;
                                                    }
                                                    else
                                                        $_ = "=";
                                                }
                                                else {
                                                    $place += 1;
                                                    $_ = $place;
                                                }
                                            }

                                            $html = "<tr" . ($_SESSION["full_name"] == $row["full_name"] ? " class='current-team'" : "") . "><td value='" . $counter . "' class='min'><span>" . $_ . "</span></td><td class='full_name' title='" . (isset($solves[$team_id]) ? $solves[$team_id] : "" ) . "'>" . cleanReflectedValue($row["full_name"]) . " <sup>(" . cleanReflectedValue($row["login_name"]) . ")</sup>" . ($row["guest"] ? "<i class='fas fa-couch ml-2' data-toggle='tooltip' title='Guest team'></i>" : "") . "</td><td class='min'><span class='flag-icon flag-icon-" . cleanReflectedValue(strtolower($row["country_code"])) . " ml-1' data-toggle='tooltip' title='" . cleanReflectedValue(strtoupper($row["country_code"])) . "'></span></td><td class='cash'>" . number_format($scores["cash"]) . "</td><td class='awareness'>". number_format($scores["awareness"]) . "</td><td class='min actions'>" . ($_SESSION["full_name"] == $row["full_name"] ? ("<i class='fas fa-key ml-1' data-toggle='tooltip' style='vertical-align: middle' title='Change password'></i>")  . "<i class='far fa-life-ring ml-1' data-toggle='tooltip' style='vertical-align: middle' title='Send message to support'></i><i class='fas fa-sign-out-alt ml-1' data-toggle='tooltip' title='Sign out' onclick='signOut()'></i>" : "<i class='far fa-envelope ml-1' data-toggle='tooltip' style='vertical-align: middle' title='Send private message'></i>" . (isAdmin() ? "<i class='fas fa-hand-holding-usd ml-1' data-toggle='tooltip' title='Award/penalize cash'></i>" : "<i class='fas fa-money-bill-wave ml-1' data-toggle='tooltip' title='Send cash'></i>") . (isAdmin() ? "<i class='far fa-edit ml-1' data-toggle='tooltip' title='Edit team'></i>" : "") . (isAdmin() ? "<i class='far fa-trash-alt ml-1' data-toggle='tooltip' title='Delete team'></i>" : "")). "</td></tr>";

                                            if (getSetting(Setting::CTF_STYLE) === "ad") {
                                                $_ = fetchAll("SELECT flag_score, availability_score FROM attack_defense WHERE team_id=:team_id", array("team_id" => $team_id));
                                                if (count($_) == 1) {
                                                    $_ = $_[0];
                                                    $flags = is_null($_["flag_score"]) ? 0 : $_["flag_score"];
                                                    $availability = is_null($_["availability_score"]) ? $initial_availability : $_["availability_score"];
                                                }
                                                else {
                                                    $flags = 0;
                                                    $availability = $initial_availability;
                                                }
                                                $html = preg_replace('/<td[^>]+class=.cash[^>]+>[^<]*<\/td>/', "<td>" . number_format($flags) . "</td>", $html);
                                                $html = preg_replace('/<td[^>]+class=.awareness[^>]+>[^<]*<\/td>/', "<td>" . number_format($availability) . "</td>", $html);
//                                                 $html = preg_replace('/<td[^>]+class=.min actions[^>]+>.*?<\/td>/', "", $html);
                                            }

                                            if (!isAdmin() && !parseBool(getSetting(Setting::CASH_TRANSFERS)) || getSetting(Setting::CTF_STYLE) === "ad")
                                                $html = preg_replace("/<i[^>]+fa-money-bill-wave[^>]+><\/i>/", "", $html);
                                            if (!isAdmin() && !parseBool(getSetting(Setting::PRIVATE_MESSAGES)) || getSetting(Setting::CTF_STYLE) === "ad")
                                                $html = preg_replace("/<i[^>]+fa-envelope[^>]+><\/i>/", "", $html);
                                            if (!isAdmin() && !parseBool(getSetting(Setting::SUPPORT_MESSAGES)) || getSetting(Setting::CTF_STYLE) === "ad")
                                                $html = preg_replace("/<i[^>]+fa-life-ring[^>]+><\/i>/", "", $html);
                                            if (getSetting(Setting::CTF_STYLE) === "ad")
                                                $html = preg_replace("/<i[^>]+fa-hand-holding[^>]+><\/i>/", "", $html);

                                            echo $html;
                                        }

                                        if (isAdmin())
                                            echo "<tr><td value='" . ($counter + 1) . "' class='min'><span>?</span></td><td class='full_name'>??? <sup>(???)</sup></td><td class='min'><span class='flag-icon ml-1' data-toggle='tooltip' title='?'></span></td><td>?</td><td" . (getSetting(Setting::CTF_STYLE) !== "ad" ? " class='awareness'" : "") . ">?</td><td class='min actions'><i class='far fa-file' data-toggle='tooltip' title='New team'></i></td></tr>";
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
?>

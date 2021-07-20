<?php
    require_once("common.php");

    if (!isAdmin()) {
        $active = getActiveContracts($_SESSION["team_id"]);
        $finished = getFinishedContracts($_SESSION["team_id"]);
        $hidden = getHiddenContracts();
        $visible = array_diff(array_merge($active, $finished), $hidden);

        if (count($visible) > 0) {
            echo "                                <div id=\"accordion\">\n";

            foreach ($visible as $contract_id) {
                $contract = fetchAll("SELECT contracts.title, contracts.description, contracts.categories, SUM(tasks.cash) AS cash, SUM(tasks.awareness) AS awareness FROM contracts JOIN tasks ON contracts.contract_id=tasks.contract_id WHERE contracts.contract_id=:contract_id GROUP BY(contracts.contract_id)", array("contract_id" => $contract_id))[0];
                $template = file_get_contents("templates/accepted.html");
                $accepted = format($template, array("title" => $contract["title"] . (in_array($contract_id, $finished) ? ' <i class="far fa-check-circle" title="Finished" data-toggle="tooltip"></i>' : ""), "categories" => generateCategoriesHtml(explode(',', $contract["categories"])), "awareness" => number_format($contract["awareness"]), "description" => $contract["description"], "contract_id" => $contract_id));

                $solved = getSolvedTasks($_SESSION["team_id"]);
                $rows = fetchAll("SELECT * FROM tasks WHERE contract_id=:contract_id ORDER BY task_id ASC", array("contract_id" => $contract_id));
                $tasks = "";
                $total = 0;

                foreach ($rows as $row) {
                    $result = fetchAll("SELECT * FROM options WHERE task_id=:task_id", array("task_id" => $row["task_id"]));

                    if ($result)
                        $options = $result[0];
                    else
                        $options = array("note" => "", "is_regex" => false, "ignore_case" => false, "ignore_order" => false);

                    $template = file_get_contents("templates/task.html");
                    $task = format($template, array("title" => $row["title"], "description" => $row["description"], "task_id" => $row["task_id"]));

                    if (in_array($row["task_id"], $solved)) {
                        $cash = getDynamicScore($row["task_id"], null, true);
                        $task = format($task, array("values" => generateValuesHtml($cash, $row["awareness"])));
                        $task = str_replace("<div class=\"task card ", "<div class=\"task card solved ", $task);
                        $task = str_replace("Task answer", "Solved", $task);
                        $task = str_replace(" autocomplete", " disabled=\"disabled\" autocomplete", $task);
                        $task = str_replace(" type=\"submit\"", " disabled=\"disabled\" style=\"pointer-events: none\"", $task);
                        $task = str_replace("class=\"form-control\"", "class=\"form-control disabled\"", $task);
                    }
                    else {
                        $cash = getDynamicScore($row["task_id"]);
                        $task = format($task, array("values" => generateValuesHtml($cash, $row["awareness"])));

                        if ($options["note"])
                            $task = str_replace("Task answer", cleanReflectedValue($options["note"]), $task);
                    }

                    if (!checkStartEndTime()) {
                        $task = str_replace(" autocomplete", " disabled=\"disabled\" autocomplete", $task);
                        $task = str_replace(" type=\"submit\"", " disabled=\"disabled\" style=\"pointer-events: none\"", $task);
                        $task = str_replace("class=\"form-control\"", "class=\"form-control disabled\"", $task);
                        $task = str_replace("success", "", $task);
                        $accepted = preg_replace('/style="[^"]+"/', "", $accepted);
                    }

                    $total += $cash;
                    $tasks = $tasks . ($tasks ? "\n" : "") . $task;
                }

                $accepted = format($accepted, array("cash" => $total, "tasks" => $tasks));
                echo $accepted;
            }

            echo "                                </div>\n";
        }
        else
            echo '                                <script>showMessageBox("Information", "There are no active contracts");</script>' . "\n";
    }
    else {
        if (isset($_POST["contract_id"]) && isset($_POST["edit"])) {
            if ($_POST["contract_id"] != -1)
                $contract = fetchAll("SELECT * FROM contracts WHERE contracts.contract_id=:contract_id", array("contract_id" => $_POST["contract_id"]))[0];
            else
                $contract = array("title" => "", "description" => "", "categories" => "", "hidden" => false);

            $result = fetchAll("SELECT min_cash, min_awareness FROM constraints WHERE contract_id=:contract_id", array("contract_id" => $_POST["contract_id"]));

            if ($result)
                $constraints = $result[0];
            else
                $constraints = array("min_cash" => "", "min_awareness" => "");

            $title = "<input name='contract_id' value='" . cleanReflectedValue($_POST["contract_id"]) . "' type='hidden'><input name='title' value='" . cleanReflectedValue($contract["title"]) . "' class='form-control' style='display: block'><label class='info-label'>Contract title</label>";
            $categories = "<input name='categories' value='" . cleanReflectedValue($contract["categories"]) . "' class='form-control'><label class='info-label'>Contract categories (Note: comma-splitted)</label><div class='custom-control custom-checkbox'><input type='checkbox' class='custom-control-input' id='hidden'><label class='custom-control-label' for='hidden'>Hidden</label></div><div class='custom-control custom-checkbox'><input type='checkbox' class='custom-control-input' id='constraints_checkbox'><label class='custom-control-label' for='constraints_checkbox'>Constraints</label></div>";
            $description = "<textarea name='description' class='form-control' style='display: block'>" . cleanReflectedValue($contract["description"]) . "</textarea><label class='info-label'>Contract description</label>";

            $template = file_get_contents("templates/accepted.html");
            $html = format($template, array("title" => $title, "categories" => $categories, "cash" => "", "awareness" => "", "description" => $description, "contract_id" => cleanReflectedValue($_POST["contract_id"])));
            $html = str_replace('<div class="card ', '<div id="contract_editor" class="card ', $html);
            $html = str_replace('col-sm-8 ', '', $html);
            $html = str_replace('<div class="row">', '<div>', $html);
            $html = preg_replace("/<div class=\"col-sm-4 text-right hover-hidden\">.+?<\/div>/s", "", $html);
            $html = str_replace('data-toggle="collapse" ', "", $html);
            $html = str_replace('<span class="h3">', "<span>", $html);
            $html = str_replace('card mb-3', "card", $html);
//             $html = str_replace('<div class="card-header">', '<div class="card-header"><button class="close" data-dismiss="modal" aria-label="Delete contract" title="Delete contract" data-toggle="tooltip"><span aria-hidden="true">×</span></button>', $html);

            $tasks = "";
            $rows = fetchAll("SELECT * FROM tasks WHERE contract_id=:contract_id ORDER BY task_id ASC", array("contract_id" => $_POST["contract_id"]));
            array_push($rows, array("title" => "", "description" => "", "cash" => 0, "awareness" => 0, "answer" => "", "task_id" => -1));
//             array_push($rows, array("task_id" => -1, "title" => "", "description" => "", "cash" => 0, "awareness" => 0, "answer" => ""));
            foreach ($rows as $row) {
                $result = fetchAll("SELECT * FROM options WHERE task_id=:task_id", array("task_id" => $row["task_id"]));

                if ($result)
                    $options = $result[0];
                else
                    $options = array("note" => "", "is_regex" => false, "ignore_case" => false, "ignore_order" => false);

                $template = file_get_contents("templates/task.html");
                $title = "<input name='title' value='" . cleanReflectedValue($row["title"]) . "' class='form-control' style='display: block'><label class='info-label'>Task title</label>";
                $description = "<textarea name='description' class='form-control' style='display: block'>" . cleanReflectedValue($row["description"]) . "</textarea><label class='info-label'>Task description</label>";
                $values = sprintf("</div><div><input type='number' min='0' value='%s' class='form-control' style='width: initial'><label class='info-label'>Task cash</label><input type='number' value='%s' class='form-control awareness' style='width: initial'><label class='info-label awareness'>Task awareness</label>", $row["cash"], $row["awareness"]);
                $task = format($template, array("title" => $title, "values" => $values, "description" => $description, "task_id" => $row["task_id"]));
                $task = preg_replace("/.*<input name=\"token.+/", "", $task);
                $task = preg_replace("/\s*<button.+<\/button>\s*/", "", $task);
                $task = preg_replace('/<input name="answer".+/', "<input name='answer' value='" . cleanReflectedValue($row["answer"]) . "' class='form-control' style='display: block'><label class='info-label'>Task answer</label>", $task);

                $additional = <<<END
                                                                    <input name='note' value='{note}' class='form-control' style='display: block'>
                                                                    <label class='info-label'>Task note (optional)</label>
                                                                    <div style='margin-top: 5px; border: 1px solid rgba(0,0,0,.125); width: 100%; padding: 5px'>
                                                                        <div class='custom-control custom-checkbox'>
                                                                            <input type='checkbox' class='custom-control-input options-checkbox checkbox-success' id='regex{task_id}_checkbox'{regex_checked}><label class='custom-control-label' for='regex{task_id}_checkbox'>Regular expression</label>
                                                                        </div>
                                                                        <div class='custom-control custom-checkbox'>
                                                                            <input type='checkbox' class='custom-control-input options-checkbox' id='ignorecase{task_id}_checkbox'{ignorecase_checked}><label class='custom-control-label' for='ignorecase{task_id}_checkbox'>Ignore character case</label>
                                                                        </div>
                                                                        <div class='custom-control custom-checkbox'>
                                                                            <input type='checkbox' class='custom-control-input options-checkbox' id='ignoreorder{task_id}_checkbox'{ignoreorder_checked}><label class='custom-control-label' for='ignoreorder{task_id}_checkbox'>Ignore word order</label>
                                                                        </div>
                                                                    </div>
                                                                    <label class="info-label">Task options</label>

END;

                $additional = format($additional, array("task_id" => $row["task_id"], "note" => cleanReflectedValue($options["note"]), "regex_checked" => ($options["is_regex"] ? " checked": ""), "ignorecase_checked" => ($options["ignore_case"] ? " checked": ""), "ignoreorder_checked" => ($options["ignore_order"] ? " checked": "")));
                $task = preg_replace("/Task answer<\/label>/", "\\0" . $additional, $task);
                $task = str_replace("input-group", "", $task);

                $task = preg_replace("/\s*<\/?form.*>\s*/", "", $task);
                if ($row["task_id"] == -1)
                    $task = str_replace('<div class="task ', '<div class="task new-task ', $task);
                $task = str_replace('<div class="card-header">', '<div class="card-header"><button class="close" data-dismiss="modal" aria-label="Delete task" title="Delete task" data-toggle="tooltip"><span aria-hidden="true">×</span></button>', $task);
                $tasks = $tasks . ($tasks ? "\n" : "") . $task;
            }

            $html = format($html, array("tasks" => $tasks));

            $footer = sprintf('<div class="ml-4 mb-3"><button class="btn btn-info">New task</button></div><div class="modal-footer"><button class="btn btn-primary">%s</button><button class="btn btn-secondary" data-dismiss="modal" onclick="reload()">Cancel</button></div>', $_POST["contract_id"] != -1 ? "Update" : "Create");
            $html = preg_replace("/(<\/div>\s*)$/", $footer . "$1", $html);

            echo $html;

            $script = <<<END
                                    <script>
                                        $(document).ready(function() {
                                            var setupValidation = function(element) {
                                                $(element).find("label:contains('Contract title'),label:contains('Task title'),label:contains('Contract description'),label:contains('Task description'),label:contains('Task answer'),label:contains('Task cash'),label:contains('Task awareness')").prev().change(validator).keyup(validator).focusout(validator).each(validator);
                                            };

                                            $(".active").text("%s contract");

                                            $("#contract_editor input").keypress(function(e) {
                                                if(e.which == 13) {
                                                    $("#contract_editor .btn-primary").click();
                                                }
                                            });

                                            $(".close").click(function() {
                                                var contract = $(this).closest(".card").addClass("closed");
                                            });

                                            $(".btn-info:contains('New task')").click(function() {
                                                var task = $(".new-task").clone();
                                                $(task).removeClass("new-task");
                                                $(task).addClass("created-task");
                                                $(task).html($(task).html().replace(/\-1/g, "-" + ($(".created-task").length + 2)));
                                                $(".new-task").parent().append(task);
                                                setupValidation(task);
                                                $(task).find(".close").prop("title", "Delete task").click(function() {
                                                    $(task).remove();
                                                }).hover(
                                                    function() { $(this).closest(".card").addClass("highlight") },
                                                    function() { $(this).closest(".card").removeClass("highlight") }
                                                );
                                            });

                                            var options = JSON.parse('%s');

                                            $("#constraints_checkbox").change(function() {
                                                if ($(this).prop("checked")) {
                                                    var constraints_inputs = $("<div id='constraints_inputs'><input type='number' min='0' value='%s' class='form-control' style='width: initial'><label class='info-label'>Minimum cash</label><input type='number' min='0' value='%s' class='form-control awareness' style='width: initial'><label class='info-label awareness'>Minimum awareness</label></div>");
                                                    $(this).closest(".custom-control").before(constraints_inputs);
                            //                         setupValidation($("#constraints_inputs"));  // disabled because constraints can be unset individually
                                                }
                                                else
                                                    $("#constraints_inputs").remove();
                                            });

                                            if (%s) {
                                                $("#constraints_checkbox").trigger("click");
                                            }

                                            if (%s) {
                                                $("#hidden").trigger("click");
                                            }

                                            setupValidation($("#contract_editor"));

                                            $("#contract_editor .btn-primary").click(function() {
                                                invalid = null;

                                                $(".is-invalid").each(function() {
                                                    if (!$(this).closest(".task").is(".new-task, .closed")) {
                                                        if (invalid === null)
                                                            invalid = $(this);
                                                    }
                                                });

                                                if (invalid) {
                                                    $([document.documentElement, document.body]).animate({scrollTop: $(invalid).offset().top}, "fast", function() {
                                                        $(invalid).focus()
                                                    });
                                                }
                                                else {
                                                    var contract = {};
                                                    contract["contract_id"] = $("[name='contract_id']").val();
                                                    contract["title"] = $("label:contains('Contract title')").prev().val();
                                                    contract["categories"] = $("label:contains('Contract categories')").prev().val();
                                                    contract["description"] = $("label:contains('Contract description')").prev().val();
                                                    contract["hidden"] = $("#hidden").prop("checked");
                                                    contract["tasks"] = [];

                                                    if ($("#constraints_inputs").length > 0)
                                                        contract["constraints"] = {"min_cash": $("#constraints_inputs").find("label:contains('Minimum cash')").prev().val(), "min_awareness": $("#constraints_inputs").find("label:contains('Minimum awareness')").prev().val()};

                                                    $(".task").not(".new-task").not(".closed").each(function() {
                                                        var task = {};
                                                        task["task_id"] = $(this).find("[name='task_id']").val();
                                                        task["title"] = $(this).find("label:contains('Task title')").prev().val();
                                                        task["cash"] = $(this).find("label:contains('Task cash')").prev().val();
                                                        task["awareness"] = $(this).find("label:contains('Task awareness')").prev().val();
                                                        task["description"] = $(this).find("label:contains('Task description')").prev().val();
                                                        task["answer"] = $(this).find("label:contains('Task answer')").prev().val();
                                                        task["note"] = $(this).find("label:contains('Task note')").prev().val();
                                                        task["is_regex"] = $(this).find("label:contains('Regular expression')").prev().prop("checked");
                                                        task["ignore_case"] = $(this).find("label:contains('Ignore character case')").prev().prop("checked");
                                                        task["ignore_order"] = $(this).find("label:contains('Ignore word order')").prev().prop("checked");
                                                        contract["tasks"].push(task);
                                                    });

                                                    $.post(window.location.href.split('#')[0], {token: document.token, action: "update", contract: JSON.stringify(contract)}, function(content) {
                                                        if (content !== "OK")
                                                            alert("Something went wrong ('" + content + "')!");
                                                        else
                                                            reload();
                                                    });
                                                }
                                            });

                                            $(".close").hover(
                                                function() { $(this).closest(".card").addClass("highlight") },
                                                function() { $(this).closest(".card").removeClass("highlight") }
                                            );
                                        });
                                    </script>

END;
            echo sprintf($script, $_POST["contract_id"] != -1 ? "Edit" : "New", json_encode($options), $constraints["min_cash"], $constraints["min_awareness"], (($constraints["min_cash"] == $constraints["min_awareness"]) && ($constraints["min_awareness"] == "")) ? "false" : "true", $contract["hidden"] ? "true" : "false");
        }
        else {
            $template = file_get_contents("templates/contract.html");
            $_ = getAllContracts();
            $__ = getConstraintedContracts($_SESSION["team_id"]);

            define("CLEARFIX_HTML", "                                <div class=\"clearfix\"></div>\n");
            $counter = 0;

            array_push($_, -1);

            foreach ($_ as $contract_id) {
                if ($contract_id === -1) {
                    $html = format($template, array("title" => "???", "values" => generateValuesHtml("?", "?") . "<span style='float:right'><i class='fas fa-upload' title='Import' data-toggle='tooltip'></i></span>", "description" => "", "categories" => "", "contract_id" => $contract_id));
                    $html = preg_replace("/Take contract/s", "New contract", $html);
                    $html = preg_replace("/btn-success/s", "btn-warning", $html);
                }
                else {
                    $row = fetchAll("SELECT contracts.title, contracts.description, contracts.categories, contracts.hidden, SUM(tasks.cash) AS cash, SUM(tasks.awareness) AS awareness FROM contracts JOIN tasks ON contracts.contract_id=tasks.contract_id WHERE contracts.contract_id=:contract_id GROUP BY(contracts.contract_id)", array("contract_id" => $contract_id));
                    if (!$row)
                        $row = fetchAll("SELECT title, description, categories, hidden, 0 AS cash, 0 AS awareness FROM contracts WHERE contracts.contract_id=:contract_id", array("contract_id" => $contract_id));

                    $row = $row[0];
                    $dynamic = getDynamicScore(null, $contract_id);
                    $note = "";
                    if (in_array($contract_id, $__)) {
                        $constraint = fetchAll("SELECT * FROM constraints WHERE contract_id=:contract_id", array("contract_id" => $contract_id))[0];
                        $note .= "<p class=\"smaller border rounded border-danger\" style=\"border-style: dashed !important; border-width: 2px !important; padding: 5px\">Note: To take this contract team needs: ";

                        if (!is_null($constraint["min_cash"]))
                            $note .= sprintf("<b><i class='currency'></i> %s</b> ", number_format($constraint["min_cash"]));
                        if (!is_null($constraint["min_awareness"]))
                            $note .= sprintf('<b><i class="fas fa-eye"></i> %s</b> ', number_format($constraint["min_awareness"]));
                        $note .= "</p>";
                    }
                    $html = str_replace("</b>", '</b><button class="close" data-dismiss="modal" aria-label="Delete contract" title="Delete contract" data-toggle="tooltip"><span aria-hidden="true">×</span></button>', $template);
                    $html = format($html, array("title" => $row["title"]. ($row["hidden"] ? '<i class="far fa-eye-slash ml-2" title="Hidden" data-toggle="tooltip"></i>' : ""), "values" => generateValuesHtml($row["cash"], $row["awareness"], $dynamic) . "<span style='float: right; font-size: 95%'><i class='fas fa-download' title='Export' data-toggle='tooltip'></i></span>", "description" => $row["description"] . $note, "categories" => generateCategoriesHtml(explode(',', $row["categories"])), "contract_id" => $contract_id));
                    $html = preg_replace("/Take contract/s", "Edit contract", $html);
                    $html = preg_replace("/btn-success/s", "btn-primary", $html);
                    if ($row["hidden"]) {
                        $html = str_replace('card mb-3">', 'card mb-3 highlight-hidden">', $html);
                    }
                }
                $html = str_replace("<button", "<input name='edit' value='true' type='hidden'>\n                                                <button", $html);
                echo $html;
                $counter += 1;
                if ($counter % 3 === 0)
                    echo CLEARFIX_HTML;
            }

        print <<<END
                                <script>
                                    function deleteContract(contract_id) {
                                        $.post(window.location.href.split('#')[0], {token: document.token, action: "delete", contract_id: contract_id}, function(content) {
                                            if (content !== "OK")
                                                alert("Something went wrong ('" + content + "')!");
                                            else
                                                reload();
                                        });
                                    }

                                    $(document).ready(function() {
                                        $(".close[aria-label='Delete contract']").click(function() {
                                            var contract = $(this).closest(".contract");
                                            var contract_id = $(contract).find("[name=contract_id]").prop("value");
                                            var title = $(contract).find(".card-header b").text();
                                            showYesNoWarningBox("Are you sure that you want to delete contract '" + title + "'?", function() {deleteContract(contract_id);});
                                        });

                                        $(".close").prop("title", "Delete contract").hover(
                                            function() { $(this).closest(".card").addClass("highlight") },
                                            function() { $(this).closest(".card").removeClass("highlight") }
                                        );
                                    });
                                </script>

END;
        }
    }
?>

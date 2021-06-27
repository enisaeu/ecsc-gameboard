<?php
    require_once("common.php");

    $template = file_get_contents("templates/contract.html");
    $_ = getAvailableContracts($_SESSION["team_id"]);

    define("CLEARFIX_HTML", "                                <div class=\"clearfix\"></div>\n");
    $counter = 0;
    $success = false;

    foreach ($_ as $contract_id) {
        $row = fetchAll("SELECT contracts.title, contracts.description, contracts.categories, SUM(tasks.cash) AS cash, SUM(tasks.awareness) AS awareness FROM contracts JOIN tasks ON contracts.contract_id=tasks.contract_id WHERE contracts.contract_id=:contract_id GROUP BY(contracts.contract_id)", array("contract_id" => $contract_id));
        if (!$row)
            continue;   // contracts without tasks should not be displayed to the user
        else
            $row = $row[0];
        echo format($template, array("title" => $row["title"], "values" => generateValuesHtml(getDynamicScore(null, $contract_id), $row["awareness"]), "description" => $row["description"], "categories" => generateCategoriesHtml(explode(',', $row["categories"])), "contract_id" => $contract_id));
        $success = true;
        $counter += 1;
        if ($counter % 3 === 0)
            echo CLEARFIX_HTML;
    }

    $_ = getConstraintedContracts($_SESSION["team_id"]);

    foreach ($_ as $contract_id) {
        $constraint = fetchAll("SELECT * FROM constraints WHERE contract_id=:contract_id", array("contract_id" => $contract_id))[0];
        $contract = fetchAll("SELECT contracts.title, contracts.description, contracts.categories, SUM(tasks.cash) AS cash, SUM(tasks.awareness) AS awareness FROM contracts JOIN tasks ON contracts.contract_id=tasks.contract_id WHERE contracts.contract_id=:contract_id GROUP BY(contracts.contract_id)", array("contract_id" => $contract_id))[0];
        $description = $contract["description"];
        $description .= "<p class=\"smaller\"><u>Note:</u> To take this contract you'll need: ";

        if (!is_null($constraint["min_cash"]))
            $description .= sprintf("<b><i class='currency'></i> %s</b> ", number_format($constraint["min_cash"]));
        if (!is_null($constraint["min_awareness"]))
            $description .= sprintf('<b><i class="fas fa-eye"></i> %s</b> ', number_format($constraint["min_awareness"]));

        $description .= "</p>";

        $html = format($template, array("title" => $contract["title"], "values" => generateValuesHtml(getDynamicScore(null, $contract_id), $contract["awareness"]), "description" => $description, "categories" => generateCategoriesHtml(explode(',', $contract["categories"])), "contract_id" => $contract_id));
        $html = str_replace('class="card ', 'class="card text-white bg-secondary constrained ', $html);
        $html = preg_replace("/<form.+form>\n?/s", "", $html);
        $html = preg_replace("/style=\"[^\"]+background-color:[^\"]+\"/s", "", $html);
        echo $html;
        $success = true;

        $counter += 1;
        if ($counter % 3 === 0)
            echo CLEARFIX_HTML;
    }

    if (!$success)
        echo '                                <script>showMessageBox("Information", "There are no more available contracts");</script>' . "\n";

?>

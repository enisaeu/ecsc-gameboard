<?php
    require_once("common.php");

    $template = file_get_contents("templates/contract.html");
    $_ = getFinishedContracts($_SESSION["team_id"]);

    define("CLEARFIX_HTML", "                                <div class=\"clearfix\"></div>\n");
    $counter = 0;
    $success = false;

    foreach ($_ as $contract_id) {
        $row = fetchAll("SELECT contracts.title, contracts.description, contracts.categories, SUM(tasks.cash) AS cash, SUM(tasks.awareness) AS awareness FROM contracts JOIN tasks ON contracts.contract_id=tasks.contract_id WHERE contracts.contract_id=:contract_id GROUP BY(contracts.contract_id)", array("contract_id" => $contract_id))[0];
        $html = format($template, array("title" => $row["title"], "values" => generateValuesHtml($row["cash"], $row["awareness"]), "description" => $row["description"], "categories" => generateCategoriesHtml(explode(',', $row["categories"])), "contract_id" => $contract_id));
        $html = str_replace('class="card ', 'class="card text-white bg-secondary ', $html);
        $html = preg_replace("/<form.+form>\n?/s", "", $html);
        echo $html;
        $success = true;
        $counter += 1;
        if ($counter % 3 === 0)
            echo CLEARFIX_HTML;
    }

    if (!$success)
        echo '                                <script>showMessageBox("Information", "There are still no finished contracts");</script>' . "\n";

?>

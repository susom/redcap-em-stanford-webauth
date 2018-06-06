<?php
/** @var \Stanford\Webauth\Webauth $module */

namespace Stanford\Webauth;

use \HtmlPage;
use \ExternalModules\ExternalModules;

function cleanup($commit = false) {

    global $module;

    // Query the old settings
    $q = db_query("select form_name, project_id from redcap_surveys where webauth_required = 1 order by project_id, form_name");

    while ($row = db_fetch_assoc($q)) {

        $project_id = $row['project_id'];
        $survey_name = $row['form_name'];

        // Get the current values if any
        $webauth_surveys = ExternalModules::getProjectSetting($module->PREFIX, $project_id, $module::$module_key);
        $webauth_surveys = empty($webauth_surveys) ? array() : $webauth_surveys;
        $module::log($webauth_surveys, "Module settings for Project " . $project_id);


        // Add the current survey if not already there
        if (!in_array($survey_name, $webauth_surveys)) {
            // Add survey
            array_push($webauth_surveys, $survey_name);

            if ($commit) {
                $action = "Added to";
                // Write the results back to the EM as a json string
                // Make sure it is a simple array
                $webauth_surveys = array_values($webauth_surveys);
                ExternalModules::setProjectSetting($module->PREFIX, $project_id, $module::$module_key, $webauth_surveys);
            } else {
                $action = "Will add to";
            }
        } else {
            $action = "Already present in ";
        }
        echo "<div>$project_id: $survey_name - $action " . $module::$module_key . "</div>";
    }
}




// OPTIONAL: Display the header
$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

    ?>

    <div>
        <h3>Clean up existing WebAuth Configurations</h3>
        <div>This page helps you to take the webauth settings from the survey_settings table and transfer them to the external modules table.</div>
    </div>

    <?php


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    cleanup(true);
} else {
    ?>

        <form method="POST">
            <button class="btn btn-primary" type="submit">CLICK TO START CLEANUP</button>
        </form>

    <?php
    cleanup(false);

}

echo "<hr><div>DONE</div>";


// OPTIONAL: Display the footer
$HtmlPage->PrintFooterExt();

//require_once \ExternalModules\ExternalModules::getProjectHeaderPath();

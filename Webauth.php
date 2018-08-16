<?php



namespace Stanford\Webauth;

class Webauth extends \ExternalModules\AbstractExternalModule
{

    static $module_key = "webauth-surveys";

    /**  HOOKS  **/
    function redcap_every_page_before_render($project_id = null)
    {
        self::log(PAGE, __FUNCTION__);

        // Note that we must use the before_render hook here because the POST handling occurs before the every_page_top is called
        $this->updateSurveySettings();
    }


    function redcap_every_page_top($project_id = null)
    {
        self::log(PAGE, __FUNCTION__);

        // When editing the survey settings we need to handle webauth configuration now
        $this->renderSurveySettings();

        // Add survey lock icons to survey instruments that contain webauth
        $this->addSurveyIcons();

    }

    function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        self::log(PAGE, __FUNCTION__);

        // Enable functionality of webauth in actual surveys
        $this->enableSurveyWebauth($instrument);
    }


    /**  METHODS  **/

    /**
     * Get the array of survey names that are configured for webauth
     * @return array
     */
    function getWebauthSurveys()
    {
        // Get all surveys
        $surveys = $this->getProjectSetting(self::$module_key);
        $surveys = empty($surveys) ? array() : $surveys;
        self::log($surveys, "On getProjectSetting after null");
        return $surveys;
    }

    /**
     * Edit the webauth surveys
     * @param      $survey_name
     * @param bool $enable
     */
    function editWebauthSurveys($survey_name, $enable = false)
    {

        $surveys = $this->getWebauthSurveys();

        if ($enable and !in_array($survey_name, $surveys)) {
            // add survey
            array_push($surveys, $survey_name);
        }

        if (!$enable and in_array($survey_name, $surveys)) {
            // remove survey
            $surveys = array_diff($surveys, array($survey_name));
        }

        // Make it a simple array
        $surveys = array_values($surveys);
        self::log($surveys, "On saving");
        $this->setProjectSetting(self::$module_key, $surveys);
    }

    /**
     * Is the survey name provided configured to be webauthed
     * @param $survey_name
     * @return bool
     */
    function isWebauthed($survey_name)
    {
        $surveys = $this->getWebauthSurveys();
        return in_array($survey_name, $surveys);
    }


    /**
     * When a survey settings are saved, update the external module based on the form settings
     */
    function updateSurveySettings()
    {

        if ((PAGE == "Surveys/edit_info.php" || PAGE == "Surveys/create_survey.php") && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $survey_name = $_GET['page'];
            $webauth_required = (isset($_POST['webauth_required']) && $_POST['webauth_required'] == "on") ? 1 : 0;

            self::log($webauth_required, "webauth required for $survey_name");
            // self::log($_POST);
            $this->editWebauthSurveys($survey_name, $webauth_required);
        }

    }


    /**
     * When editing or creating survey settings, we need to insert into the form the option for enabling webauth
     */
    function renderSurveySettings()
    {
        if (PAGE == "Surveys/edit_info.php" || PAGE == "Surveys/create_survey.php") {
            $survey_name = $_GET['page'];

            // Get current value from external-module settings
            $webauth_required = $this->isWebauthed($survey_name);
            $webauth_checked = $webauth_required ? "checked" : "";
            // self::log($survey_name . " is webauthed? " . $webauth_required);

            ?>
            <div style="display:none;">
                <table>
                    <tr id="webauth-tr">
                        <td valign="top" style="width:20px;">
                            <input type="checkbox" id="webauth_required"
                                   name="webauth_required" <?php echo $webauth_checked; ?>>
                        </td>
                        <td valign="top" style="width:200px;font-weight:bold;padding-bottom:3px;" colspan=2>
                            Require a valid Stanford SUNet ID (a.k.a. webauth)
                            <div style="font-weight:normal;">
                                <i>Enabling webauth for this survey will force all users to provide a valid Stanford
                                    SUNet ID and password before they can access the survey. If you place a text field
                                    called 'webauth_user' on your instrument, the sunet id for the user taking the
                                    survey will be saved into this field and the field will be disabled. If you want
                                    this field to also be hidden from the end user, add '@HIDDEN' to the notes section
                                    of the field.</i>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <script>
                $(document).ready(function () {
                    $('#webauth-tr')
                        .insertAfter($('#save_and_return-tr'))
                        .show()
                    ;
                });
            </script>
            <?php
        }
    }

    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * When a survey page is displayed, make sure webauth is working
     * @param $instrument
     */
    function enableSurveyWebauth($instrument)
    {
        $webauth_required = $this->isWebauthed($instrument);

        if ($webauth_required) {
            $sunet = isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : '';
            // self::log($sunet, "Sunet");
            // TESTING ON LOCALHOST - if ($this->startsWith($_SERVER['REQUEST_URI'], "/webauth")) $sunet = "andy123";

            if (empty($sunet)) {
                // Not in webauth
                $uri = $_SERVER['REQUEST_URI'];

                // Make sure uri doesn't already include webauth
                if ($this->startsWith($uri, "/webauth")) {
                    echo "<div class='text-center alert alert-warning'><br><br>There appears to be a problem with webauth - please contact an administrator<br><br><br></div>";
                    exit();
                }

                $new_uri = "/webauth" . $uri;
                header('Location: ' . $new_uri);
                $this->exitAfterHook();
            } else {

                // Add Style
                echo "<link rel='stylesheet' type='text/css' href='" . $this->getUrl("webauth.css", true, false) . "'>";

                // Add Javascript
                echo "<script type='text/javascript' src='" . $this->getUrl("webauth.js", true, false) . "'></script>";
                echo "<script>webauth.sunet = '$sunet';</script>";
                echo "<script>webauth.isDev = " . self::isDev() . ";</script>";

                // Add HTML
                $refresh_url = $this->getUrl("Refresh.php", true, false);
                echo "<script>webauth.refreshUrl = '$refresh_url';</script>";
                ?>

                <div class="webauth-alert-wrapper">
                    <div class="alert alert-danger text-center">
                        <a href="#" class="close" data-dismiss="alert">&times;</a>
                        <strong>WEBAUTH SESSION WARNING!</strong><br>
                        This survey requires a valid webauth session to submit. If you submit without a valid session,
                        your data will be lost.<br>
                        To re-establish a valid session, press this button:
                        <a target="_BLANK" href="<?php echo $refresh_url . "&action=webauth-refresh"; ?>">
                            <span class="button btn-danger btn-xs">OPEN A NEW TAB</span>
                        </a> and follow the directions on the new tab.<br>
                        When you return to this tab, this warning should clear after ~30 seconds.
                    </div>
                </div>

                <?php
            }
        }
    }


    /**
     * On the edit instrument table it shows a lock icon next to surveys that have webauth enabled
     */
    function addSurveyIcons()
    {
        if (PAGE == "Design/online_designer.php") {
            $webauth_surveys = $this->getWebauthSurveys();
            if (count($webauth_surveys) > 0) {
                $shield_url = $this->getUrl("Shield_Red.png");
                ?>
                <script>
                    $(document).ready(function () {
                        var webauth_surveys = <?php echo json_encode($webauth_surveys); ?>;
                        //webauth.log("WebauthSurveys", webauth_surveys);
                        $.each(webauth_surveys, function (i, j) {
                            var img = $('<img/>')
                                .attr('src', '<?php echo $shield_url; ?>')
                                .attr('title', 'Webauth Protected Survey')
                                .css({"vertical-align": "middle", "right": "-3px"});
                            var shield = $('a.modsurvstg[href*="page=' + j + '&"]').prepend(img);
                        });
                    });
                </script>
                <?php
            }
        }
    }


    # defines criteria to judge someone is on a development box or not
    public static function isDev()
    {
        $is_localhost = (@$_SERVER['HTTP_HOST'] == 'localhost');
        $is_dev_server = (isset($GLOBALS['is_development_server']) && $GLOBALS['is_development_server'] == '1');
        $is_dev = ($is_localhost || $is_dev_server) ? 1 : 0;
        return $is_dev;
    }


    public static function log()
    {
        if (self::isDev()) {
            if (class_exists("\Plugin")) {
                $args = func_get_args();
                call_user_func("\Plugin::log", $args);
            }
        }
    }


}

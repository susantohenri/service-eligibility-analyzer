<?php

/**
 * Service Eligibility Analyzer
 *
 * @package     ServiceEligibilityAnalyzer
 * @author      Henri Susanto
 * @copyright   2022 Henri Susanto
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Service Eligibility Analyzer
 * Plugin URI:  https://github.com/susantohenri/service-eligibility-analyzer
 * Description: Wordpress plugin to analyse service eligibility
 * Version:     1.0.0
 * Author:      Henri Susanto
 * Author URI:  https://github.com/susantohenri/
 * Text Domain: ServiceEligibilityAnalyzer
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

define('SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SAMPLE', plugin_dir_url(__FILE__) . 'service-eligibility-analyzer-formula-sample.csv');
define('SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_ACTIVE', plugin_dir_url(__FILE__) . 'service-eligibility-analyzer-formula-active.csv');
define('SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE', plugin_dir_path(__FILE__) . 'service-eligibility-analyzer-formula-active.csv');
define('SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SUBMIT', 'service-eligibility-analyzer-formula-submit');
define('SERVICE_ELIGIBILITY_ANALYZER_LATEST_CSV_OPTION', 'service-eligibility-analyzer-last-uploaded-csv');

add_action('admin_menu', function () {
    add_menu_page('Service Eligibility Analyzer', 'Service Eligibility Analyzer', 'administrator', __FILE__, function () {
        if ($_FILES) {
            if ($_FILES[SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SUBMIT]['tmp_name']) {
                move_uploaded_file($_FILES[SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SUBMIT]['tmp_name'], SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE);
                update_option(SERVICE_ELIGIBILITY_ANALYZER_LATEST_CSV_OPTION, $_FILES[SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SUBMIT]['name']);
            }
        }
?>
        <div class="wrap">
            <h1>Service Eligibility Analyzer</h1>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div class="">
                        <div class="meta-box-sortables">
                            <div id="dashboard_quick_press" class="postbox ">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <span>Service Eligibility Analyzer Formula</span>
                                        <div>
                                            <?php if (file_exists(SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE)) : ?>
                                                <a class="button button-primary" href="<?= SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_ACTIVE ?>" style="text-decoration:none;">Export Current Formula</a>
                                            <?php endif ?>
                                            <a class="button button-primary" href="<?= SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SAMPLE ?>" style="text-decoration:none;">Download Empty CSV Sample File</a>
                                        </div>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <form name="post" action="" method="post" class="initial-form" enctype="multipart/form-data">
                                        <div class="input-text-wrap" id="title-wrap">
                                            <label> Last Uploaded CSV File Name: </label>
                                            <b><?= get_option(SERVICE_ELIGIBILITY_ANALYZER_LATEST_CSV_OPTION) ?></b>
                                        </div>
                                        <div class="input-text-wrap" id="title-wrap">
                                            <label for="title"> Choose New Formula CSV File </label>
                                            <input type="file" name="<?= SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SUBMIT ?>">
                                        </div>
                                        <p>
                                            <input type="submit" name="save" class="button button-primary" value="Upload Selected CSV">
                                            <br class="clear">
                                        </p>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }, '');
});
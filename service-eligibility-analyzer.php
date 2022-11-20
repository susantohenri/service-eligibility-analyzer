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
                service_eligibility_analyzer_analyse();
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

function service_eligibility_analyzer_analyse()
{
    if (!file_exists(SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE)) return true;
    $rows = [];
    if (($open = fopen(SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE, 'r')) !== FALSE) {
        while (($data = fgetcsv($open, 100000, ",")) !== FALSE) $rows[] = $data;
        fclose($open);
    }

    $thead = $rows[0];
    unset($rows[0]);
    $tbody = array_values($rows);

    $list_col_num = array_search('List', $thead);
    $label_col_num = array_search('Label', $thead);
    $link_col_num = array_search('Link', $thead);
    $logic_col_num = array_search('Logic', $thead);
    $forms_and_fields = [];
    $fields_to_analyse= [];
    $answers_to_analyse = [];
    $formulas = [];

    $form_field_col_num = $logic_col_num;
    while (isset($thead[$form_field_col_num + 1])) {
        $form_field_col_num++;
        $cell_value = $thead[$form_field_col_num];
        $cell_value = explode(',', $cell_value);
        $forms_and_fields[] = [
            'col_num' => $form_field_col_num,
            'form_id' => (int) str_replace('Form ID ', '', $cell_value[0]),
            'field_id' => (int) str_replace('Field ', '', $cell_value[1])
        ];
    }

    $fields_to_analyse = array_map(function ($form_and_field) {
        return $form_and_field['field_id'];
    }, $forms_and_fields);
    $fields_to_analyse = implode(', ', $fields_to_analyse);

    global $wpdb;
    $collect_answers = $wpdb->prepare("
        SELECT
            {$wpdb->prefix}frm_items.user_id
            , {$wpdb->prefix}frm_items.form_id
            , {$wpdb->prefix}frm_item_metas.field_id
            , {$wpdb->prefix}frm_item_metas.meta_value answer
        FROM {$wpdb->prefix}frm_item_metas
        LEFT JOIN {$wpdb->prefix}frm_items ON {$wpdb->prefix}frm_items.id = {$wpdb->prefix}frm_item_metas.item_id
        WHERE %d
        AND {$wpdb->prefix}frm_item_metas.field_id IN ($fields_to_analyse)
    ", TRUE);
    $answers = $wpdb->get_results($collect_answers);

    foreach ($tbody as $row_num => $row) {
        $is_eligible = $row[$list_col_num];
        $service_name = $row[$label_col_num];
        $service_link = $row[$link_col_num];
        $logic = $row[$logic_col_num];
        $formula = [];

        foreach ($forms_and_fields as $cell) {
            if (0 === strlen($row[$cell['col_num']])) continue;
            $formula[] = [
                'rule_col' => $cell['col_num'] + 1,
                'form_id' => $cell['form_id'],
                'field_id' => $cell['field_id'],
                'expected_value' => $row[$cell['col_num']]
            ];
        }

        $formulas[] = [
            'rule_row' => $row_num + 2,
            'service_name' => $service_name,
            'service_link' => $service_link,
            'is_eligible' => $is_eligible,
            'logic' => $logic,
            'formula' => $formula,
        ];
    }

    // echo json_encode($formulas) . '<br>';
}

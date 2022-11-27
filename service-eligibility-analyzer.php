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
define('SERVICE_ELIGIBILITY_ANALYZER_USER_META', 'service-eligibility-analyzer-eligibility-list');

add_shortcode('service-eligibility-analyzer', function ($atts) {
    $atts = shortcode_atts(['user-id' => get_current_user_id()], $atts);
    $user_meta = service_eligibility_analyzer_user_meta($atts['user-id'], null);

    $result = 'Eligible:';
    $eligible = array_map(function ($service) {
        return "<li><a href='{$service->link}'>{$service->name}</a></li>";
    }, $user_meta->eligible);
    $eligible = implode('', $eligible);
    $result .= "<ul>{$eligible}</ul>";
    $result .= '<p>Not Eligible:</p>';
    $not_eligible = array_map(function ($service) {
        return "<li><a href='{$service->link}'>{$service->name}</a></li>";
    }, $user_meta->not_eligible);
    $not_eligible = implode('', $not_eligible);
    $result .= "<ul>{$not_eligible}</ul>";

    return $result;
});

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
                                                <a class="button button-primary" href="<?= site_url() . '/wp-json/service-eligibility-analyzer/v1/download-latest' ?>" style="text-decoration:none;">Export Current Formula</a>
                                            <?php endif ?>
                                            <a class="button button-primary" href="<?= site_url() . '/wp-json/service-eligibility-analyzer/v1/download-sample' ?>" style="text-decoration:none;">Download Empty CSV Sample File</a>
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
    $formulas = service_eligibility_analyzer_formula();
    global $wpdb;
    foreach ($formulas as $rule) {
        $formula_to_query = "
            SELECT
                ID
            FROM {$wpdb->prefix}users
            WHERE %d
        ";
        $logic = $rule['logic'];
        foreach ($rule['formula'] as $frml) {
            $field_id = $frml['field_id'];
            $expected_value = $frml['expected_value'];
            $formula_to_query .= "
                {$logic} ID IN
                (
                    SELECT
                        {$wpdb->prefix}frm_items.user_id
                    FROM {$wpdb->prefix}frm_items
                    RIGHT JOIN {$wpdb->prefix}frm_item_metas ON {$wpdb->prefix}frm_items.id = {$wpdb->prefix}frm_item_metas.item_id
                    WHERE {$wpdb->prefix}frm_item_metas.field_id = {$field_id} AND {$wpdb->prefix}frm_item_metas.meta_value = '{$expected_value}'
                )                
            ";
        }

        foreach ($wpdb->get_results($wpdb->prepare($formula_to_query, 'AND' === $logic)) as $user) {
            $user_meta = service_eligibility_analyzer_user_meta($user->ID, null);

            $service = (object)[
                'name' => $rule['service_name'],
                'link' => $rule['service_link']
            ];

            $is_eligible = $rule['is_eligible'];
            $is_eligible = strtolower($is_eligible);
            $is_eligible = trim($is_eligible);
            $is_eligible = str_replace(' ', '_', $is_eligible);

            if (!in_array($service, $user_meta->$is_eligible)) $user_meta->$is_eligible[] = $service;
            service_eligibility_analyzer_user_meta($user->ID, $user_meta);
            // show calculation result per user: echo json_encode([$user->ID, $user_meta]) . '<br>';
        }
        // show applied rule per row for users above: echo json_encode($rule) . '<br><br>';
    }
}

function service_eligibility_analyzer_user_meta($user_id, $new_value = null)
{
    if (null === $new_value) {
        $user_meta = get_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META, true);
        $user_meta =  '' === $user_meta ? (object) ['eligible' => [], 'not_eligible' => []] : json_decode($user_meta);
        return $user_meta;
    } else return update_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META, json_encode($new_value));
}

add_action('rest_api_init', function () {
    register_rest_route('service-eligibility-analyzer/v1', '/download-sample', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {
            $filename = basename(SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SAMPLE);
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Content-Type: text/csv');
            readfile(SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_SAMPLE);
        }
    ));
    register_rest_route('service-eligibility-analyzer/v1', '/download-latest', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {
            $filename = basename(SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_ACTIVE);
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Content-Type: text/csv');
            readfile(SERVICE_ELIGIBILITY_ANALYZER_CSV_FILE_ACTIVE);
        }
    ));
});

add_action('frm_after_create_entry', function ($entry_id, $form_id) {
    if (in_array($form_id, service_eligibility_analyzer_form_ids())) service_eligibility_analyzer_analyse();
}, 30, 2);

add_action('frm_after_update_entry', function ($entry_id, $form_id) {
    if (in_array($form_id, service_eligibility_analyzer_form_ids())) service_eligibility_analyzer_analyse();
}, 10, 2);

function service_eligibility_analyzer_form_ids() {
    $rule_form_ids = array_map(function ($rule) {
        return array_map(function ($formula) {
            return $formula['form_id'];
        }, $rule['formula']);
    }, service_eligibility_analyzer_formula());
    $distinct = [];
    foreach ($rule_form_ids as $formula_form_ids) foreach ($formula_form_ids as $form_id) if (!in_array($form_id, $distinct)) $distinct[] = $form_id;
    return $distinct;
}

function service_eligibility_analyzer_formula()
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
    $formulas = [];

    // scan header
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

    // scan body
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

    return $formulas;
}

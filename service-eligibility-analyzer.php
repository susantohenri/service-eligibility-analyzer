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
define('SERVICE_ELIGIBILITY_SEPARATOR', '|');

add_shortcode('service-eligibility-analyzer', function ($atts) {
    $atts = shortcode_atts(['user-id' => get_current_user_id()], $atts);
    $user_meta = service_eligibility_analyzer_user_meta($atts['user-id'], null);

    $eligible = array_map(function ($service) {
        return "<li><a href='{$service->link}'>{$service->name}</a></li>";
    }, $user_meta->eligible);
    $eligible = implode('', $eligible);

    $not_eligible = array_map(function ($service) {
        return "<li><a href='{$service->link}'>{$service->name}</a></li>";
    }, $user_meta->not_eligible);
    $not_eligible = implode('', $not_eligible);

    /* TESTING LINES BEGIN *
    global $wpdb;
    $fields = service_eligibility_analyzer_field_ids();
    $fields = implode(', ', $fields);
    $answers = $wpdb->prepare("
        SELECT
            {$wpdb->prefix}frm_items.user_id
            , GROUP_CONCAT(CONCAT({$wpdb->prefix}frm_item_metas.field_id, ':', {$wpdb->prefix}frm_item_metas.meta_value)) answers
        FROM {$wpdb->prefix}frm_item_metas
        LEFT JOIN {$wpdb->prefix}frm_items ON {$wpdb->prefix}frm_items.id = {$wpdb->prefix}frm_item_metas.item_id
        WHERE %d AND {$wpdb->prefix}frm_item_metas.field_id IN ($fields) AND {$wpdb->prefix}frm_items.user_id = %d
    ", true, $atts['user-id']);
    $testing = $wpdb->get_results($answers);
    echo json_encode($testing);
    * TESTING LINES END */

    return "
        <div id='mySidenav' class='sidenav'>
            <a href='javascript:void(0)' class='closebtn' onclick='closeNav()'>×</a>
            <ul class='sidenav-menu'>
                <li>
                    <a href='#' class='has-submenu'>Eligible Services</a>
                    <ul class='sidenav-submenu'>
                        {$eligible}
                    </ul>
                </li>
                <li>
                    <a href='#' class='has-submenu'>Non-Eligible Services</a>
                    <ul class='sidenav-submenu'>
                        {$not_eligible}
                    </ul>
                </li>
            </ul>
        </div>
        <i class='fa fa-bars' onclick='openNav()' style='font-size:3rem;color:white;float:right;cursor: pointer;'></i>
    ";
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
                            <div id="dashboard_quick_press" class="postbox ">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <span>Custom Keyword Usage</span>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <form>
                                        Below are samples of formula value using custom keyword :
                                        <ol>
                                            <li>not<?= SERVICE_ELIGIBILITY_SEPARATOR ?>1120</li>
                                            <li>greater-than<?= SERVICE_ELIGIBILITY_SEPARATOR ?>1120</li>
                                            <li>emtpy<?= SERVICE_ELIGIBILITY_SEPARATOR ?></li>
                                            <li>not-empty<?= SERVICE_ELIGIBILITY_SEPARATOR ?></li>
                                            <li>in<?= SERVICE_ELIGIBILITY_SEPARATOR ?>1120,1120s,1120-A</li>
                                            <li>in<?= SERVICE_ELIGIBILITY_SEPARATOR ?>1120,1120s<?= SERVICE_ELIGIBILITY_SEPARATOR ?>not<?= SERVICE_ELIGIBILITY_SEPARATOR ?>1120-A</li>
                                        </ol>
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
    $rules = service_eligibility_analyzer_formula();
    $fields = service_eligibility_analyzer_field_ids($rules);
    $fields = implode(',', $fields);
    global $wpdb;

    $collect_users_answer = $wpdb->prepare("
        SELECT
            {$wpdb->prefix}frm_items.user_id
            , GROUP_CONCAT(CONCAT({$wpdb->prefix}frm_item_metas.field_id, ':', {$wpdb->prefix}frm_item_metas.meta_value)) answers
        FROM {$wpdb->prefix}frm_item_metas
        LEFT JOIN {$wpdb->prefix}frm_items ON {$wpdb->prefix}frm_items.id = {$wpdb->prefix}frm_item_metas.item_id
        WHERE %d AND {$wpdb->prefix}frm_item_metas.field_id IN ($fields)
        GROUP BY {$wpdb->prefix}frm_items.user_id
    ", true);

    $users = $wpdb->get_results($collect_users_answer);
    foreach ($users as $user) {
        $user_id = $user->user_id;
        $answers = explode(',', $user->answers);
        $user_meta = ['eligible' => [], 'not_eligible' => []];

        foreach ($rules as $rule) {
            $logic = strtolower($rule['logic']);
            $is_eligible = str_replace(' ', '_', trim(strtolower($rule['is_eligible'])));
            $service = (object)[
                'name' => $rule['service_name'],
                'link' => $rule['service_link'],
                // 'rule_num' => $rule['rule_row']
            ];
            $rule_match = null;
            foreach ($rule['formula'] as $formula) {
                $is_match = in_array($formula['field_id'] . ':' . $formula['expected_value'], $answers);
                if (is_null($rule_match)) $rule_match = $is_match;
                else if ('and' === $logic) $rule_match = $rule_match && $is_match;
                else if ('or' === $logic) $rule_match = $rule_match || $is_match;
            }
            if ($rule_match && !in_array($service, $user_meta[$is_eligible])) $user_meta[$is_eligible][] = $service;
        }
        service_eligibility_analyzer_user_meta($user_id, $user_meta);
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

function service_eligibility_analyzer_form_ids()
{
    $rule_form_ids = array_map(function ($rule) {
        return array_map(function ($formula) {
            return $formula['form_id'];
        }, $rule['formula']);
    }, service_eligibility_analyzer_formula());
    $distinct = [];
    foreach ($rule_form_ids as $formula_form_ids) foreach ($formula_form_ids as $form_id) if (!in_array($form_id, $distinct)) $distinct[] = $form_id;
    return $distinct;
}

function service_eligibility_analyzer_field_ids($formulas = null)
{
    $rule_field_ids = array_map(function ($rule) {
        return array_map(function ($formula) {
            return $formula['field_id'];
        }, $rule['formula']);
    }, is_null($formulas) ? service_eligibility_analyzer_formula() : $formulas);
    $distinct = [];
    foreach ($rule_field_ids as $formula_field_ids) foreach ($formula_field_ids as $field_id) if (!in_array($field_id, $distinct)) $distinct[] = $field_id;
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
            'rule_row' => $row_num + 1,
            'service_name' => $service_name,
            'service_link' => $service_link,
            'is_eligible' => $is_eligible,
            'logic' => $logic,
            'formula' => $formula,
        ];
    }

    return $formulas;
}

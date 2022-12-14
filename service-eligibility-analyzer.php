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
define('SERVICE_ELIGIBILITY_ANALYZER_USER_META_SHORTLIST', 'service-eligibility-analyzer-shortlist');
define('SERVICE_ELIGIBILITY_ANALYZER_USER_META_ELIGIBLE', 'service-eligibility-analyzer-eligible');
define('SERVICE_ELIGIBILITY_ANALYZER_USER_META_NOTELIGIBLE', 'service-eligibility-analyzer-noteligible');
define('SERVICE_ELIGIBILITY_SEPARATOR', '|');

add_shortcode('service-eligibility-analyzer', function ($atts) {
    $atts = shortcode_atts(['user-id' => get_current_user_id()], $atts);
    wp_register_script('service-eligibility-analyzer', plugin_dir_url(__FILE__) . 'service-eligibility-analyzer.js', array('jquery'));
    wp_enqueue_script('service-eligibility-analyzer');
    wp_localize_script(
        'service-eligibility-analyzer',
        'service_eligibility_analyzer',
        array(
            'shortcode_user_id' => $atts['user-id'],
            'eligibility_list_url' => site_url('wp-json/service-eligibility-analyzer/v1/list?&cache-breaker=' . time()),
            'eligibility_list_update_url' => site_url('wp-json/service-eligibility-analyzer/v1/update')
        )
    );
    return "
        <div class='service-eligibility-analyzer'>
            Shortlist
            <ol class='service-eligibility-analyzer-shortlist'>
            </ol>

            Eligible
            <ol class='service-eligibility-analyzer-eligible'>
            </ol>

            Not Eligible
            <ol class='service-eligibility-analyzer-not-eligible'>
            </ol>
        </div>
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
                                            <li>greater-than-equals<?= SERVICE_ELIGIBILITY_SEPARATOR ?>1120</li>
                                            <li>less-than<?= SERVICE_ELIGIBILITY_SEPARATOR ?>1065</li>
                                            <li>less-than-equals<?= SERVICE_ELIGIBILITY_SEPARATOR ?>1065</li>
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
            {$wpdb->prefix}frm_items.user_id,
            GROUP_CONCAT(CONCAT({$wpdb->prefix}frm_fields.id, ':', IFNULL({$wpdb->prefix}frm_item_metas.meta_value, 'EMPTY'))) answers
        FROM {$wpdb->prefix}frm_items
            LEFT JOIN {$wpdb->prefix}frm_forms ON {$wpdb->prefix}frm_items.form_id = {$wpdb->prefix}frm_forms.id
            LEFT JOIN {$wpdb->prefix}frm_fields ON {$wpdb->prefix}frm_fields.form_id = {$wpdb->prefix}frm_forms.id
            LEFT JOIN {$wpdb->prefix}frm_item_metas
                ON {$wpdb->prefix}frm_item_metas.item_id = {$wpdb->prefix}frm_items.id
                AND {$wpdb->prefix}frm_item_metas.field_id = {$wpdb->prefix}frm_fields.id
        WHERE %d AND {$wpdb->prefix}frm_fields.id IN($fields)
        GROUP BY {$wpdb->prefix}frm_items.user_id
    ", true);

    $users = $wpdb->get_results($collect_users_answer);
    foreach ($users as $user) {
        $user_id = $user->user_id;
        $answers = explode(',', $user->answers);
        $user_meta_shortlist = service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_SHORTLIST);
        $user_meta_eligible = service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_ELIGIBLE);
        $user_meta_noteligible = service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_NOTELIGIBLE);

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

                $formula_field_id = $formula['field_id'];
                $formula_expected_value = $formula['expected_value'];
                $answer = array_values(array_filter($answers, function ($ans) use ($formula_field_id) {
                    return -1 < strpos($ans, "{$formula_field_id}:");
                }));
                if (!isset($answer[0])) continue;
                $answer = $answer[0];
                $answer = explode(':', $answer);
                $answer = $answer[1];

                $is_match = null;
                switch (substr_count($formula_expected_value, SERVICE_ELIGIBILITY_SEPARATOR)) {
                    case 0:
                        $is_match = service_eligibility_analyzer_keyword_match('equals', $answer, $formula_expected_value);
                        break;
                    case 1:
                        $keyword = explode(SERVICE_ELIGIBILITY_SEPARATOR, $formula_expected_value);
                        $keyword = $keyword[0];
                        $is_match = service_eligibility_analyzer_keyword_match($keyword, $answer, $formula_expected_value);
                        break;
                    default:
                        $keywords = [];
                        $values = [];
                        foreach (explode(SERVICE_ELIGIBILITY_SEPARATOR, $formula_expected_value) as $index => $combination) {
                            if (0 === $index % 2) $keywords[] = $combination;
                            else $values[] = $combination;
                        }
                        foreach ($keywords as $index => $keyword) {
                            $expected = $values[$index];
                            $is_match = is_null($is_match) ? service_eligibility_analyzer_keyword_match($keyword, $answer, $expected) : $is_match && service_eligibility_analyzer_keyword_match($keyword, $answer, $expected);
                        }
                        break;
                }

                if (is_null($rule_match)) $rule_match = $is_match;
                else if ('and' === $logic) $rule_match = $rule_match && $is_match;
                else if ('or' === $logic) $rule_match = $rule_match || $is_match;
            }
            if ($rule_match) {
                if ('eligible' === $is_eligible && !in_array($service, $user_meta_eligible)) {
                    $user_meta_eligible[] = $service;
                }
                if ('not_eligible' === $is_eligible && !in_array($service, $user_meta_noteligible)) {
                    $user_meta_noteligible[] = $service;
                }
            }
        }
        $user_meta_shortlist = array_values(array_filter($user_meta_shortlist, function ($shortlist) use ($user_meta_eligible) {
            return in_array($shortlist, $user_meta_eligible);
        }));

        service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_SHORTLIST, $user_meta_shortlist);
        service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_ELIGIBLE, $user_meta_eligible);
        service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_NOTELIGIBLE, $user_meta_noteligible);
    }
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
    register_rest_route('service-eligibility-analyzer/v1', '/list', array(
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {
            $user_id = $_GET['user-id'];
            return [
                'shortlist' => service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_SHORTLIST),
                'eligible' => service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_ELIGIBLE),
                'not-eligible' => service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_NOTELIGIBLE),
            ];
        }
    ));
    register_rest_route('service-eligibility-analyzer/v1', '/update', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function () {
            $user_id = $_POST['user_id'];
            $service = ['name' => $_POST['service_name'], 'link' => $_POST['service_link']];

            $user_meta_shortlist = service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_SHORTLIST);
            $user_meta_eligible = service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_ELIGIBLE);

            $search_index_eligible = -1;
            $eligible_matches = array_filter($user_meta_eligible, function ($eliglible_service) use ($service) {
                return json_encode($eliglible_service) === json_encode($service);
            });
            foreach ($eligible_matches as $index => $value) $search_index_eligible = $index;

            $search_index_shortlist = -1;
            $shortlist_matches = array_filter($user_meta_shortlist, function ($eliglible_service) use ($service) {
                return json_encode($eliglible_service) === json_encode($service);
            });
            foreach ($shortlist_matches as $index => $value) $search_index_shortlist = $index;

            if (-1 < $search_index_shortlist) {
                unset($user_meta_shortlist[$search_index_shortlist]);
                $user_meta_eligible[] = $service;
            }
            if (-1 < $search_index_eligible) {
                unset($user_meta_eligible[$search_index_eligible]);
                $user_meta_shortlist[] = $service;
            }

            service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_SHORTLIST, array_values($user_meta_shortlist));
            service_eligibility_analyzer_user_meta($user_id, SERVICE_ELIGIBILITY_ANALYZER_USER_META_ELIGIBLE, array_values($user_meta_eligible));
            return true;
        }
    ));
});

add_action('frm_after_create_entry', function ($entry_id, $form_id) {
    if (in_array($form_id, service_eligibility_analyzer_form_ids())) service_eligibility_analyzer_analyse();
}, 30, 2);

add_action('frm_after_update_entry', function ($entry_id, $form_id) {
    if (in_array($form_id, service_eligibility_analyzer_form_ids())) service_eligibility_analyzer_analyse();
}, 10, 2);

function service_eligibility_analyzer_user_meta ($user_id, $meta_key, $meta_value = null) {
    $user_id = (int) $user_id;
    if (is_null($meta_value)) {
        $meta_value = get_user_meta($user_id, $meta_key);
        return isset($meta_value[0]) ? $meta_value[0] : [];
    } else {
        // $meta_value = isset($meta_value[0]) ? $meta_value[0] :[];
        return update_user_meta($user_id, $meta_key, $meta_value);
    }
}

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

function service_eligibility_analyzer_keyword_match($keyword, $answer, $expected)
{
    $expected = explode(SERVICE_ELIGIBILITY_SEPARATOR, $expected);
    $expected = isset($expected[1]) ? $expected[1] : $expected[0];
    switch ($keyword) {
        case 'equals':
            return "{$answer}" === "{$expected}";
            break;
        case 'not':
            return "{$answer}" !== "{$expected}";
            break;
        case 'greater-than':
            return (int) $answer > (int) $expected;
            break;
        case 'greater-than-equals':
            return (int) $answer >= (int) $expected;
            break;
        case 'less-than':
            return (int) $answer < (int) $expected;
            break;
        case 'less-than-equals':
            return (int) $answer <= (int) $expected;
            break;
        case 'empty':
            return "{$answer}" === "EMPTY";
            break;
        case 'not-empty':
            return "{$answer}" !== "EMPTY";
            break;
        case 'in':
            $expected = array_map(function ($word) {
                return trim($word);
            }, explode(',', $expected));
            return in_array("{$answer}", $expected);
            break;
        case 'not-in':
            $expected = array_map(function ($word) {
                return trim($word);
            }, explode(',', $expected));
            return !in_array("{$answer}", $expected);
            break;
    }
}

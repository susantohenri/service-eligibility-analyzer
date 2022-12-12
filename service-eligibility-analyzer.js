service_eligibility_analyzer_draw_list()

function service_eligibility_analyzer_draw_list() {
    jQuery.get(service_eligibility_analyzer.eligibility_list_url, {'user-id':service_eligibility_analyzer.shortcode_user_id}, function (lists) {
        for (var list of ['shortlist', 'eligible', 'not-eligible']) {
            jQuery(`.service-eligibility-analyzer ol.service-eligibility-analyzer-${list}`).html(lists[list][0].map(function (service) {
                var shortlist_action = ``
                if ('shortlist' === list) shortlist_action = `<a href="javascript:service_eligibility_analyzer_update_list(${service_eligibility_analyzer.shortcode_user_id}, '${service.name}', '${service.link}');">(-)</a>`
                if ('eligible' === list) shortlist_action = `<a href="javascript:service_eligibility_analyzer_update_list(${service_eligibility_analyzer.shortcode_user_id}, '${service.name}', '${service.link}');">(+)</a>`
                return `<li><a href="${service.link}">${service.name}</a> ${shortlist_action}</li>`
            }).join(''))
        }
    })
}

function service_eligibility_analyzer_update_list (user_id, service_name, service_link) {
    jQuery.post(service_eligibility_analyzer.eligibility_list_update_url, {user_id, service_name, service_link}, service_eligibility_analyzer_draw_list)
}
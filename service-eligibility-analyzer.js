service_eligibility_analyzer_draw_list()
function service_eligibility_analyzer_draw_list() {
    jQuery.get(service_eligibility_analyzer.eligibility_list_url, {}, function (lists) {
        for (var list of ['shortlist', 'eligible', 'not-eligible']) {
            jQuery(`.service-eligibility-analyzer ol.service-eligibility-analyzer-${list}`).html(lists[list][0].map(function (service) {
                
                return `<li><a href="${service.link}">${service.name}</a></li>`
            }).join(''))
        }
    })
}

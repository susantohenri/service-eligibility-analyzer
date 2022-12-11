jQuery.get(service_eligibility_analyzer.eligibility_list_url, {}, function (lists) {
    jQuery('.service-eligibility-analyzer').html(JSON.stringify(lists))
})
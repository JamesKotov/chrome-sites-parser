// collecting items data from group pages
var processGroupPage = function (html) {
	var	$html = getSafeJqHtml(html);

	return $html.find('.product-item').map(function(){

		var itemId = _.compact($(this).find('a.path').attr('href').split('/')).pop();

		var marking = _.drop(itemId.split('-'), 1).join('-');

		var name = $(this).find('.product-name a').text();

		var sizes = _.sortBy(
			$(this).find('.product-sizes-inner span').map(function(){
				return $.trim($(this).text());
			}).get()
		);

		return {id: itemId, marking: $.trim(marking), name: $.trim(name), sizes: sizes.join(',')};
	}).get()
}

// collecting group urls
var getAllGroupUrls = function() {
	return $('.s-menu').find('a').map(function(){
		return $.trim($(this).attr('href')) + '?count=all';
	}).get();
};

// run collector
collectData(getAllGroupUrls(), processGroupPage)
	.then(function(result) {
		// sending data
		sendData(uniqFlattenArray(result));
	});

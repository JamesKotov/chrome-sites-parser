// getting items data from items pages
var processPage = function (html) {

	var $html = getSafeJqHtml(html);

	var path = $html.find('.breadcrumbs a:last').text();

	var name = $html.find('h1').text();

	var itemId = jQuery.trim($html.find('.spacer-buy-area').contents().filter(function() {
	    return this.nodeType == 3;
	}).text());

	var marking = itemId;
	var sizes = $html.find('#customPrice04 option').map(function(){
		return jQuery.trim((jQuery(this).text()));
	}).get();

	sizes = _.sortBy(_.compact(sizes));

	return {id: itemId, path: path, marking: jQuery.trim(marking), name: jQuery.trim(name), sizes: sizes.join(',')};
}

// getting all items pages urls
var processGroupPage = function (html) {
	var $html = getSafeJqHtml(html);
	return $html.find('.product').find('a:first').map(function(){
		return jQuery.trim(jQuery(this).attr('href'));
	}).get()
}

// getting all group pages urls
var getAllGroupUrls = function() {
	return jQuery('.katalog ul.menu a').map(function(){
		return jQuery.trim(jQuery(this).attr('href')) + '?limit=300';
	}).get();
};

// run collector for group pages
collectData(getAllGroupUrls(), processGroupPage)
	.then(function(allItems) {
		// run collector for items pages
		collectData(uniqFlattenArray(allItems), processPage)
			.then(function(result) {
				// sending data
				sendData(result);
			});
	});

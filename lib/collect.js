if (!confirm("Начать сбор данных?")) {
	return true;
}

var start = new Date();
var startTime = start.getTime()/1000 | 0;
console.info('Start', start.getHours(), start.getMinutes(), start.getSeconds());
var scriptTextCache = {};
var scriptsCache = {};

// async page collector with anti-ddos dempfer ability
var collectData = function(aUrls, fCallback, iDempfer, bRandomize) {
	iDempfer = iDempfer || 0;
	bRandomize = !!bRandomize;
	var defer = Q.defer();

	console.info('collectData elements to process:', aUrls.length);

	if (iDempfer) {
		var pages = [];
		var timeout = 0;

		aUrls.forEach(function (itemUrl) {
			timeout += (bRandomize ? _.max([iDempfer, (iDempfer * (Math.random()+0.7))]) : iDempfer);
			setTimeout(function() {
				var d1 = jQuery.Deferred();
				jQuery.when(
					jQuery.ajax({
						url: itemUrl,
						dataType: "html"
					})
				).always(function(arg1, status){
					if (status === 'success') {
						return d1.resolve(arg1)
					}
					return d1.resolve(arg1.responseText || '')
				})
				pages.push(d1.promise())
			}, timeout);
		});

		setTimeout(function() {
			Q.allSettled(pages)
				.then(function(arrayOfPromises){
					return arrayOfPromises.map(function(item) {return item.value});
				})
				.then(function(arrayOfResults){
					return defer.resolve(arrayOfResults.map(fCallback));
				});
		}, timeout + iDempfer);
	} else {
		Q.allSettled(
			aUrls.map(function (itemUrl) {
				var d1 = jQuery.Deferred();
				jQuery.when(
					jQuery.ajax({
						url: itemUrl,
						dataType: "html"
					})
				).always(function(arg1, status){
					if (status === 'success') {
						return d1.resolve(arg1)
					}
					return d1.resolve(arg1.responseText || '')
				})
				return d1.promise();
			})
		)
		.then(function(arrayOfPromises){
			return arrayOfPromises.map(function(item) {return item.value});
		})
		.then(function(arrayOfResults){
			return defer.resolve(arrayOfResults.map(fCallback));
		});
	}

	return defer.promise;
}

// adding a hidden form to send collected data to server
var sendData = function(aData) {
	aData = uniqueCollection(aData);

	var now = new Date();
	var totalTime = ((now.getTime()/1000 | 0) - startTime) * 1;

	jQuery('body').append('<form action="{{sScriptUrl}}" method="post" enctype="application/x-www-form-urlencoded" id="saverform">\
	<input type="hidden" name="siteid" value="{{sRequestedSite}}"/>\
	<input type="hidden" name="totalTime" value=" ' + totalTime + '"/>\
	<input type="hidden" name="charset" value=" ' + document.characterSet.toUpperCase() + '"/>\
	<textarea name="data">' + JSON.stringify(aData) + '</textarea>\
	</form>');

	console.info('Sending data', now.getHours(), now.getMinutes(), now.getSeconds());

	jQuery('#saverform').submit();
}

var uniqFlattenArray = function(aArray) {
	return _.uniq([].concat.apply([],[].concat.apply([], aArray)))
}


var uniqueCollection = function(aData) {
	return _.uniqBy(aData, function (item) {
	    return JSON.stringify(_.omit(item, ['path']));
	});
}

// return jQuery parsed html page without images
var getSafeJqHtml = function(sHtml) {
	return jQuery(sHtml.replace(/<img\b[^>]*>/ig, ''));
}

// getting inline javascripts from pages
var getScriptText = function(html, substring, uniqHtmlLabel) {
	if (scriptTextCache[substring]) {
		console.log('use cache');
		return scriptTextCache[substring]
	}
	var scriptText = jQuery.trim(jQuery(html).find('script:contains("' + substring + '")').text());
	if (scriptText) {
		console.log('use quick search');
	} else {
		console.log('use full scan');
		if (!scriptsCache[uniqHtmlLabel]) {
			console.log('prepare scripts map');
			scriptsCache[uniqHtmlLabel] = {};

			var start = substring.substring(0,15);
			jQuery(html).each(function(indx, element){
				var el = jQuery(element);
				if (el.prop("tagName") === 'SCRIPT') {
					var text = jQuery.trim(jQuery(element).text());
					if (text.startsWith(start)) {
						var key = text.substring(0,40).split(' ').slice(0,4).join(' ');
						scriptsCache[uniqHtmlLabel][key] = text;
					}
				}
			})
		}
		scriptText = scriptsCache[uniqHtmlLabel][substring] || '';
	}
	scriptTextCache[substring] = scriptText || '';
	return scriptTextCache[substring];
}

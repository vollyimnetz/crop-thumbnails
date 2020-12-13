/**
 * Waiting x milliseconds for a final event than call the callback.
 * @see http://stackoverflow.com/a/4541963
 */
var CPT_waitForFinalEvent = (function () {
	var timers = {};
	return function (callback, ms, uniqueId) {
		if (!uniqueId) {
			uniqueId = "Don't call this twice without a uniqueId";
		}
		if (timers[uniqueId]) {
			clearTimeout (timers[uniqueId]);
		}
		timers[uniqueId] = setTimeout(callback, ms);
	};
})();


/** USAGE ******************
$(window).resize(function () {
	CPT_waitForFinalEvent(function(){
		alert('Resize...');
	}, 500, "some unique string");
});
***************************/

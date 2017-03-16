Vue.component('crop-editor', {
	props: ['image-data'],
	template: '<li>{{ imageData.text }}</li>',
	data : function() {
		return this.props;
	}
	ready: function() {
		console.log('loaded');
	}
})
var app = new Vue({
	el:'#cpt_crop_editor',
	data: {
		test: [
			{ text: 'test 1' },
			{ text: 'test 2' },
			{ text: 'test 3' }
		]
	}
});

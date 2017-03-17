CROP_THUMBNAILS_VUE.components.cropeditor = {
	template: '@./cropeditor.tpl.html',
	props:[
		'imageId'
	],
	data:function() {
		return {
			cropData : '',
			loading : false,
			selectSameRatio : true
		};
	},
	mounted:function() {
		var that = this;
		
		that.cropData = axios.get('http://localhost/wordpress-dev/wp-admin/admin-ajax.php?action=cpt_cropdata&imageId='+this.imageId)
			.then(function(response) {
				that.cropData = response.data;
				console.log('data loaded',response.data,that.cropData);
			});
	}
};

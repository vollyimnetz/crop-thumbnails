CROP_THUMBNAILS_VUE.components.loadingcontainer = {
	template: '@./loadingcontainer.tpl.html',
	props:{
		image : {
			required: true,
			type:String
		}
	},
	mounted:function() {
		console.log('loadingcontainer mounted',this.image);
	}
};

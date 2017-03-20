CROP_THUMBNAILS_VUE.components.loadingcontainer = {
	template: '@./loadingcontainer.tpl.html',
	props:{
		image : {
			required: true,
			type:String
		}
	},
	data:function() {
		return {
			status:null,
			imgLoad:null
		};
	},
	watch:{
		image:function() {
			this.setup();
		}
	},
	mounted:function() {
		this.setup();
	},
	methods:{
		setup : function() {
			var that = this;
			that.setStart();
			setTimeout(function() {
				that.imgLoad = imagesLoaded( that.$el );
				that.imgLoad.once('done',function() { 
					console.log('loaded',that.imgLoad.images);
					that.setComplete();
				});
			},300);
		},
		setComplete : function() {
			this.status = 'completed';
		},
		setStart : function() {
			this.status = 'started';
		}
	}
};

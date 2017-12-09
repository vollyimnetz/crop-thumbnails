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
			status:null
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
				var imgLoad = imagesLoaded( that.$el );
				imgLoad
					.once('done',function() {
						if(that.status!=='failed') {
							that.setComplete();
						}
					})
					.once('fail',function() {
						that.setFailed();
					})
					;
			},300);
		},
		setComplete : function() {
			this.status = 'completed';
		},
		setStart : function() {
			this.status = 'loading';
		},
		setFailed : function() {
			this.status = 'failed';
		}
	}
};

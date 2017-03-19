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
			console.log('image value changed',this.image);
			this.setup();
		}
	},
	mounted:function() {
		this.setup();
	},
	methods:{
		setup : function() {
			var that = this;
			this.setStart();
			imagesLoaded( this.$el, { background: true }, function() { that.setComplete(); } );
		},
		setComplete : function() {
			console.log('complete');
			this.status = 'completed';
		},
		setStart : function() {
			this.status = 'started';
		}
	}
};

CROP_THUMBNAILS_VUE.components.message = {
	template: '@./message.tpl.html',
	props:{},
	data:function() {
		return {
			closed:false
		};
	},
	methods:{
		close : function() {
			this.closed = true;
		}
	}
};

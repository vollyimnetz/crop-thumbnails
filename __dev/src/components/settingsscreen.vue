<template>
    <div>
        <div class="cptSettingsPostListDescription">{{settingsData.lang.choose_image_sizes}}</div>

        <ul class="cptSettingsPostList">
            
            <li v-for="postType in settingsData.post_types" :key="postType.name">
                <section v-if="postType">
                    <header><h3>{{postType.label}}</h3></header>

                    
                    <ul class="cptImageSizes">
                        <template v-for="imageSize in settingsData.image_sizes">
                            <li v-if="imageSize.crop" :key="imageSize.id">
                                <label>
                                    <input type="checkbox" :value="imageSize.id" :name="'crop-post-thumbs[hide_size]['+postType.name+']['+imageSize.id+']'" :checked="isImageSizeHidden(postType.name,imageSize.id)"/>
                                    <span class="name">{{imageSize.name}}</span>
                                    <span class="defaultName" v-if="imageSize.name!==imageSize.id">({{imageSize.id}})</span>
                                </label>
                            </li>
                        </template>
                    </ul>
                    
                    <label>
                        <input id="cpt_settings_post" type="checkbox" :name="'crop-post-thumbs[hide_post_type]['+postType.name+']'" value="1" :checked="isButtonHiddenOnPostType(postType.name)">
                        {{settingsData.lang.hide_on_post_type}}
                    </label>
                </section>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    props: {
        settings: { required: true },
    },
    components: {},
    data:() => ({
        settingsData: JSON.parse(this.settings)
    }),
    methods: {
        isButtonHiddenOnPostType(postType) {
            return (this.settingsData.options && this.settingsData.options.hide_post_type && this.settingsData.options.hide_post_type[postType] === "1");
        },
        isImageSizeHidden(postType,imageSize) {
            return (this.settingsData.options && this.settingsData.options.hide_size && this.settingsData.options.hide_size[postType] && this.settingsData.options.hide_size[postType][imageSize] === "1");
        }
    }
}
</script>

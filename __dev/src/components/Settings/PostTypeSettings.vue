<input type="checkbox" :value="imageSize.id" :name="'crop-post-thumbs[hide_size]['+postType.name+']['+imageSize.id+']'" :checked="isImageSizeHidden(postType.name,imageSize.id)"/>


<template>
    <div class="cpt_PostTypeSettings">
        <h2>{{settings.lang.general.nav_post_types}}</h2>

        <p>
            {{settings.lang.posttype_settings.intro_1}}
            <br />
            <strong>{{settings.lang.posttype_settings.intro_2}}</strong>
        </p>

        <div class="toolbar text-right">
            <button type="button" class="button-primary doSaveBtn" @click="doSave">{{settings.lang.general.save_changes}}</button>
        </div>

        <div class="cptSettingsPostListDescription">{{settings.lang.posttype_settings.choose_image_sizes}}</div>
        
        <ul class="cptSettingsPostList">
            
            <li v-for="postType in settings.post_types" :key="postType.name">
                <section v-if="postType">
                    <header><h3>{{postType.label}}</h3></header>

                    
                    <ul class="cptImageSizes">
                        <template v-for="imageSize in settings.image_sizes">
                            <li v-if="imageSize.crop" :key="imageSize.id">
                                <label>
                                    <input type="checkbox" :value="imageSize.id" :checked="isImageSizeHidden(postType.name,imageSize.id)"/>
                                    <span class="name">{{imageSize.name}}</span>
                                    <span class="defaultName" v-if="imageSize.name!==imageSize.id">({{imageSize.id}})</span>
                                </label>
                            </li>
                        </template>
                    </ul>
                    
                    <label>
                        <input id="cpt_settings_post" type="checkbox" :name="'crop-post-thumbs[hide_post_type]['+postType.name+']'" value="1" :checked="isButtonHiddenOnPostType(postType.name)">
                        {{settings.lang.posttype_settings.hide_on_post_type}}
                    </label>
                </section>
            </li>
        </ul>

        <pre>{{form}}</pre>

        <div class="toolbar text-right">
            <button type="button" class="button-primary doSaveBtn" @click="doSave">{{settings.lang.general.save_changes}}</button>
        </div>
    </div>
</template>

<script>
import { savePostTypeSettings } from './api';
import Message from './../Message.vue';
export default {
    components: { Message },
    props: {
        settings: { required: true, type: Object },
    },
    mounted() { this.doSetup(); },
    components: {},
    data:() => ({
        form:null
    }),
    methods: {
        doSetup() {
            this.form = this.getNewFormArray();
        },
        getNewFormArray() {
            console.log('getNewFormArray',this.settings)
            if(!this.settings.post_types) return [];
            if(!this.settings.image_sizes) return [];
            const result = [];
            
            for(const [key1, elem] of Object.entries(this.settings.post_types)) {
                const postType = {
                    name: elem.name,
                    imageSizes: [],
                    hidden: this.isButtonHiddenOnPostType(elem.name),
                };
                for(const [key2, imageSize] of Object.entries(this.settings.image_sizes)) {
                    if(!imageSize.crop) continue;
                    postType.imageSizes.push({
                        id: imageSize.id,
                        name: imageSize.name,
                        active: this.isImageSizeHidden(postType.name, imageSize.id)
                    })
                }

                result.push(postType);
            }
            console.log('getNewFormArray result', result)
            return result;
        },
        isButtonHiddenOnPostType(postType) {
            return (this.settings.options && this.settings.options.hide_post_type && this.settings.options.hide_post_type[postType] === "1");
        },
        isImageSizeHidden(postType,imageSize) {
            return (this.settings.options && this.settings.options.hide_size && this.settings.options.hide_size[postType] && this.settings.options.hide_size[postType][imageSize] === "1");
        },
        doSave() {
            savePostTypeSettings(this.form)
                .then(response => {
                    this.result = response.data
                })
                .catch(error => {
                    this.error = true;
                })
                .then(() =>{
                    this.loading = false;
                })
        }
    }
}
</script>

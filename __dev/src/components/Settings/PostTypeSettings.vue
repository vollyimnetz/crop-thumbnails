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
            <li v-for="postType in form" :key="postType.name">
                <section v-if="postType">
                    <header><h3>{{postType.label}}</h3></header>

                    <ul class="cptImageSizes">
                        <li v-for="imageSize in postType.imageSizes" :key="imageSize.id">
                            <label>
                                <input type="checkbox" v-model="imageSize.active" />
                                <span class="name">{{imageSize.name}}</span>
                                <span class="defaultName" v-if="imageSize.name!==imageSize.id">({{imageSize.id}})</span>
                            </label>
                        </li>
                    </ul>
                    
                    <label>
                        <input type="checkbox" v-model="postType.hidden">
                        <span>{{settings.lang.posttype_settings.hide_on_post_type}}</span>
                    </label>
                </section>
            </li>
        </ul>

        <div class="toolbar text-right">
            <button type="button" class="button-primary doSaveBtn" @click="doSave">{{settings.lang.general.save_changes}}</button>
        </div>
    </div>
</template>

<script>
import { savePostTypeSettings,transformToBoolValue } from './api';
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
            if(!this.settings.post_types) return [];
            if(!this.settings.image_sizes) return [];
            const result = [];
            
            for(const [key1, elem] of Object.entries(this.settings.post_types)) {
                const postType = {
                    name: elem.name,
                    label: elem.label,
                    imageSizes: [],
                    hidden: this.isButtonHiddenOnPostType(elem.name),
                };
                for(const [key2, imageSize] of Object.entries(this.settings.image_sizes)) {
                    if(!imageSize.crop) continue;
                    postType.imageSizes.push({
                        id: imageSize.id,
                        name: imageSize.name,
                        active: !!this.isImageSizeHidden(postType.name, imageSize.id),
                    });
                }
                result.push(postType);
            }
            return result;
        },
        isButtonHiddenOnPostType(postType) {
            if(!this.settings.options) return false;
            if(!this.settings.options.hide_post_type) return false;
            if(!this.settings.options.hide_post_type[postType]) return false;
            return transformToBoolValue(this.settings.options.hide_post_type[postType])
        },
        isImageSizeHidden(postType,imageSize) {
            if(!this.settings.options) return false;
            if(!this.settings.options.hide_size) return false;
            if(!this.settings.options.hide_size[postType]) return false;
            return transformToBoolValue(this.settings.options.hide_size[postType][imageSize]);
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

<style lang="scss">
.cpt_PostTypeSettings {
    .cptSettingsPostList { display:flex; flex-wrap: wrap; padding:2px; margin: 0;
        &>li { padding: 2px; box-sizing: border-box; margin: 0;
            @media(min-width:760px) { width: calc(100% / 3); }
        }
    }
    section { border: 1px solid rgba(0,0,0,0.1); background: #fff; padding: 1em; 
        h3 { margin-top:0; overflow: hidden; text-overflow: ellipsis; }
        & ul { margin: 1em 0; border-bottom: 1px solid rgba(0,0,0,0.1); }
    }

    .cptSettingsPostListDescription { text-align: center; font-size:1.2em; padding:1em; margin:0; border: 1px solid rgba(0,0,0,0.1); }
}
</style>

<template>
    <div class="cpt_Toolkit">
        <h2>Resize Toolkit</h2>
        <div class="stats" v-if="baseData">
            <div><strong>Full count of images: </strong>{{baseData.images.length}}</div>
            <div><strong>Posts with a featured image: </strong>{{baseData.post_thumbnails.length}}</div>
            <div v-if="baseData.imageSizes">
                <strong>Image sizes</strong>

                <div v-for="imageSize in baseData.imageSizes" :key="imageSize.id">
                    {{imageSize.name}} (<small>{{imageSize.width}} &times; {{imageSize.height}}</small>) <span v-if="imageSize.crop">CROP</span>
                </div>
            </div>

            <label>Search for ...<input type="text" v-model="filterSearch" placeholder="Search" /></label>
            
            <label><input type="checkbox" v-model="filterByFeaturedImage"> only when set as featured image</label>


            <Pagination :currentPage="currentPage" :perPage="perPage" :total="baseData.images.length" @pagechanged="(value) => currentPage = value"></Pagination>
            <table class="wp-list-table widefat fixed striped cropThumbnailsToolkitTable">
                 <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <TableRow v-for="entry in imagesFiltered" :key="entry.ID" :entry="entry"></TableRow>
                </tbody>
            </table>
            <Pagination :currentPage="currentPage" :perPage="perPage" :total="baseData.images.length" @pagechanged="(value) => currentPage = value"></Pagination>
        </div>
    </div>
</template>

<script>
import { toolkitBase } from './../api';
import Pagination from './Pagination.vue';
import TableRow from './TableRow.image.vue';
export default {
    components: { Pagination, TableRow },
    data: () =>({
        loading: false,
        baseData: null,
        currentPage: 1,
        perPage: 10,

        filterSearch: '',
        filterByFeaturedImage: false,
    }),
    mounted() { this.doLoad() },
    computed:{
        imagesFiltered() {
            if(!this.baseData || !Array.isArray(this.baseData.images))return [];
            let result = this.baseData.images;

            //filtering
            if(this.filterSearch.length>0 || this.filterByFeaturedImage) {
                let search = null;
                if(this.filterSearch.length>0) {
                    search = this.filterSearch.toLowerCase();
                }
                result = result.filter(elem => {
                    let isIn = true;
                    
                    if(search && elem.post_title.toLowerCase().indexOf(search) < 0) isIn = false;
                    if(this.filterByFeaturedImage && !Array.isArray(elem.isPostThumbnailFor)) isIn = false;
                    return isIn;
                });
            }

            //pagination
            const start = (this.currentPage-1) * this.perPage;
            const end = start + this.perPage;
            return result.slice(start, end);
        }
    },
    methods: {
        doLoad() {
            if(this.loading) return;
            this.loading = true;
            toolkitBase()
                .then(response => {
                    this.baseData = response.data;
                    this.optimizeData();
                })
                .catch(() => {})
                .finally(() => {
                    this.loading = false
                })
        },
        optimizeData() {
            if(!this.baseData || !Array.isArray(this.baseData.images) || !Array.isArray(this.baseData.post_thumbnails)) return;

            const mapper = {};
            this.baseData.post_thumbnails.forEach(elem => {
                if(!mapper[elem.ID]) mapper[elem.ID] = [];
                mapper[elem.ID].push(elem);
            });

            this.baseData.images.forEach(elem => {
                elem.isPostThumbnailFor = mapper[elem.ID];
            });
            this.$store.dispatch('toolkit/setAdminUrl', 'test');
        }
    }
}
</script>
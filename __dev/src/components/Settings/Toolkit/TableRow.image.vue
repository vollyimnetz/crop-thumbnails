<template>
    <tr class="TableRowImages">
        <td>{{entry.ID}}</td>
        <td>{{entry.post_title}}</td>
        <td>{{entry.post_date}}</td>
        <td>
            <ul class="postImageEntries" v-if="entry.isPostThumbnailFor">
                <li v-for="i in entry.isPostThumbnailFor" :key="i.parent"><a :href="getEditUrl(i.parent)" target="_blank">Entry with ID: {{i.parent}}</a></li>
            </ul>
        </td>
    </tr>
</template>

<script>
export default {
    props: {
        entry: { required:true, type:Object }
    },
    computed: {

    },
    methods: {
        getEditUrl(id) {

            return '/wp-admin/post.php?post='+parseInt(id)+'&action=edit';
        }
    }
}
</script>

<style lang="scss">
.TableRowImages {
    .postImageEntries { margin: 0; }
}
</style>
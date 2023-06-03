
<template>
    <ul class="Pagination">
        <li class="item">
            <button type="button" class="button" @click="onClickFirstPage" :disabled="isInFirstPage" aria-label="Go to first page">First</button>
        </li>

        <li class="item">
            <button type="button" class="button" @click="onClickPreviousPage" :disabled="isInFirstPage" aria-label="Go to previous page">Previous</button>
        </li>

        <li v-for="(page,$index) in pages" :key="$index" class="item">
            <button type="button" class="button" @click="onClickPage(page.name)" :class="{ 'button-primary': isPageActive(page.name) }" :aria-label="`Go to page number ${page.name}`">
                {{ page.name }}
            </button>
        </li>

        <li class="item">
            <button type="button" class="button" @click="onClickNextPage" :disabled="isInLastPage" aria-label="Go to next page">
                Next
            </button>
        </li>

        <li class="item">
            <button type="button" class="button" @click="onClickLastPage" :disabled="isInLastPage" aria-label="Go to last page">
                Last
            </button>
        </li>

        <li class="item">
            {{total}} entries in total. Page {{currentPage}} of {{totalPages}}.
        </li>
    </ul>
</template>

<script>
export default {
    props: {
        currentPage: { required: true, type: Number },
        //totalPages: { required: true, type: Number },
        total: { required: true, type: Number },
        perPage: { required: true, type: Number },
        maxVisibleButtons: { required: false, type: Number, default: 5 },
    },
    emits: ['pagechanged'],
    computed: {
        totalPages() {
            return Math.ceil( this.total / this.perPage );
        },
        startPage() {
            if(this.currentPage === 1) return 1;
            if(this.currentPage === this.totalPages) {
                return this.totalPages - this.maxVisibleButtons + 1;
            }
            return this.currentPage - 1;
        },
        endPage() {
            return Math.min(this.startPage + this.maxVisibleButtons - 1, this.totalPages);
        },
        pages() {
            const result = [];
            for(let i = this.startPage; i <= this.endPage; i+= 1 ) {
                result.push({ name: i, isDisabled: i === this.currentPage });
            }
            return result;
        },
        isInFirstPage() {
            return this.currentPage === 1;
        },
        isInLastPage() {
            return this.currentPage === this.totalPages;
        },
    },
    methods: {
        onClickFirstPage() {
            this.$emit('pagechanged', 1);
        },
        onClickPreviousPage() {
            this.$emit('pagechanged', this.currentPage - 1);
        },
        onClickPage(page) {
            this.$emit('pagechanged', page);
        },
        onClickNextPage() {
            this.$emit('pagechanged', this.currentPage + 1);
        },
        onClickLastPage() {
            this.$emit('pagechanged', this.totalPages);
        },
        isPageActive(page) {
            return this.currentPage === page;
        },
    }
}
</script>

<style lang="scss">
.Pagination { list-style-type: none;
    .item { display: inline-block; margin:0 .2em; }
}
</style>
<template>
    <div id="top_line" class="w-full h-2.5 bg-[#383838]"></div>
    <div
        id="wrap"
        class="max-w-[1020px] mx-auto relative mb-10 min-h-screen pt-2.5"
    >
        <AppHeader />

        <div class="relative">
            <div
                class="absolute left-1/2 -translate-x-1/2 w-screen h-px bg-[#383838]"
            ></div>
        </div>

        <!-- FILTER BANNER -->
        <div
            v-if="filters.tag || filters.search"
            class="clear-both px-5 py-1.5 bg-[#383838] text-[#ebe5cb] text-sm"
        >
            <span v-if="filters.tag"
                >Tag:
                <strong class="text-[#c3e062]">{{ filters.tag }}</strong></span
            >
            <span v-if="filters.search"
                >Search:
                <strong class="text-[#c3e062]"
                    >"{{ filters.search }}"</strong
                ></span
            >
            <a href="/" class="ml-3 text-[#c3e062] font-bold">× clear</a>
        </div>

        <!-- MAIN LAYOUT: sidebar left + articles right -->
        <div class="flex flex-row-reverse gap-0">
            <!-- RIGHT: article feed -->
            <div class="w-[630px] shrink-0 mr-7">
                <div v-if="articles.data.length === 0" class="error">
                    <span>No articles found.</span>
                </div>

                <ArticleCard
                    v-for="article in articles.data"
                    :key="article.id"
                    :article="article"
                />

                <!-- Pagination -->
                <AppPagination
                    :links="{
                        prev: articles.prev_page_url,
                        next: articles.next_page_url,
                    }"
                />
            </div>

            <!-- LEFT: sidebar -->
            <!-- <div class="flex-1 mt-5 min-w-[280px]">
                <div class="py-5">
                    <span
                        class="block text-sm font-bold px-2.5 py-2.5 text-[#ebe5cb] [text-shadow:2px_2px_2px_#35312D] border-t border-[#5B564F] border-b border-b-[#35312D]"
                        >Tag Cloud</span
                    >
                    <div
                        class="text-xs tracking-wide leading-6 text-center mt-5 px-0.5 py-5"
                    >
                        <a
                            v-for="tag in popularTags"
                            :key="tag.slug"
                            :href="`/?tag=${tag.slug}`"
                            :class="[
                                tagSizeClass(tag.count),
                                'text-[#ebe5cb] px-1.5 py-1 inline-block hover:bg-[#c3e062] hover:text-[#4f4943] hover:no-underline',
                            ]"
                            >{{ tag.name }}</a
                        >
                    </div>
                </div>
            </div> -->
        </div>
    </div>
    <AppFooter :popularTags="popularTags" :topArticles="topArticles" />
</template>

<script setup>
import AppFooter from "@/Components/AppFooter.vue";
import AppHeader from "@/Components/AppHeader.vue";
import AppPagination from "@/Components/AppPagination.vue";
import ArticleCard from "@/Components/ArticleCard.vue";
import { computed } from "vue";

const props = defineProps({
    articles: Object,
    popularTags: Array,
    filters: Object,
});

const topArticles = computed(() =>
    [...props.articles.data].sort((a, b) => b.love - a.love).slice(0, 5),
);

const maxCount = Math.max(...props.popularTags.map((t) => t.count), 1);
function tagSizeClass(count) {
    const r = count / maxCount;
    if (r > 0.75) return "largest";
    if (r > 0.5) return "large";
    if (r > 0.3) return "medium";
    if (r > 0.15) return "small";
    return "smallest";
}
</script>

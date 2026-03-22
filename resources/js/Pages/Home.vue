<template>
    <AppHead />

    <!-- Top accent line: always full viewport width -->
    <div class="z-50 fixed left-0 top-0 h-2.5 w-full bg-[#383838]"></div>

    <!-- Outer shell: clips overflow -->
    <div class="overflow-x-hidden pt-2.5 w-full">
        <!-- Page wrapper: max 1020px, centered -->
        <div class="mx-auto max-w-[1020px]">
            <AppHeader />

            <div
                class="left-[-50%] relative h-px w-[200%] bg-[#383838] ll"
            ></div>

            <!-- Filter banner -->
            <div
                v-if="filters.tag || filters.search"
                class="px-5 py-1.5 text-[#ebe5cb] text-sm bg-[#383838]"
            >
                <span v-if="filters.tag">
                    Tag:
                    <strong class="text-[#c3e062]">{{ filters.tag }}</strong>
                </span>
                <span v-if="filters.search">
                    Search:
                    <strong class="text-[#c3e062]"
                        >"{{ filters.search }}"</strong
                    >
                </span>
                <a href="/" class="ml-3 font-bold text-[#c3e062]">× clear</a>
            </div>

            <!--
                Layout logic:
                - The wrapper is max 1020px centered
                - Left ~390px is reserved for vote buttons / tags (they use negative absolute offsets)
                - Article takes the right 630px on large screens
                - On medium screens: article fills wrapper minus a smaller left gutter
                - On small screens: article fills full width (vote/tags hidden via ArticleCard responsive CSS)
            -->
            <div class="mb-10 mt-6 min-h-screen">
                <main class="ml-auto mr-0 w-full sm:w-[630px]">
                    <div
                        v-if="articles.data.length === 0"
                        class="pl-5 py-20 font-['Reenie_Beanie'] text-3xl text-[#ebe5cb]"
                    >
                        <span>No articles found.</span>
                    </div>

                    <ArticleCard
                        v-for="article in articles.data"
                        :key="article.id"
                        :article="article"
                    />

                    <AppPagination
                        :links="{
                            prev: articles.prev_page_url,
                            next: articles.next_page_url,
                        }"
                    />
                </main>
            </div>
        </div>
    </div>

    <AppFooter :popularTags="popularTags" :topArticles="topArticles" />
</template>

<script setup>
import AppFooter from "@/Components/AppFooter.vue";
import AppHead from "@/Components/AppHead.vue";
import AppHeader from "@/Components/AppHeader.vue";
import AppPagination from "@/Components/AppPagination.vue";
import ArticleCard from "@/Components/ArticleCard.vue";

const props = defineProps({
    articles: Object,
    popularTags: Array,
    filters: Object,
    topArticles: Array,
});

const maxCount = Math.max(...props.popularTags.map((t) => t.count), 1);
function tagSizeClass(count) {
    const r = count / maxCount;
    if (r > 0.75) return "text-xl";
    if (r > 0.5) return "text-lg";
    if (r > 0.3) return "text-base";
    if (r > 0.15) return "text-sm";
    return "text-xs";
}
</script>

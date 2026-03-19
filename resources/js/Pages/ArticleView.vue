<template>
    <!-- Top accent line -->
    <div class="fixed top-0 left-0 w-full h-2.5 bg-[#383838] z-50"></div>

    <!-- Outer shell -->
    <div class="w-full overflow-x-hidden pt-2.5">
        <!-- Page wrapper: max 1020px, centered -->
        <div class="max-w-[1020px] mx-auto">
            <AppHeader />

            <div
                class="ll h-px bg-[#383838] relative w-[200%] left-[-50%]"
            ></div>

            <div class="min-h-screen mb-10 mt-6">
                <!-- Same column as Home: 630px pinned to the right -->
                <main class="w-full sm:w-[630px] ml-auto mr-0">
                    <!-- Article card (read-more slider hidden) -->
                    <ArticleCard :article="article" :single-view="true" />

                    <!-- ── Share row ───────────────────────────────────────── -->
                    <div
                        class="flex items-center gap-3 font-['Reenie_Beanie'] text-2xl border-b border-[#4f4943] pb-4 mt-1 px-2"
                    >
                        <span class="text-[#ebe5cb] text-xl">share:</span>
                        <a
                            :href="`https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`"
                            target="_blank"
                            rel="noopener"
                            class="share-btn"
                            title="Share on X"
                            >𝕏</a
                        >
                        <a
                            :href="`https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`"
                            target="_blank"
                            rel="noopener"
                            class="share-btn"
                            title="Share on Facebook"
                            >f</a
                        >
                        <a
                            :href="`https://reddit.com/submit?url=${encodedUrl}&title=${encodedTitle}`"
                            target="_blank"
                            rel="noopener"
                            class="share-btn"
                            title="Share on Reddit"
                            >r/</a
                        >
                        <button
                            class="share-btn"
                            title="Copy link"
                            @click="copyLink"
                        >
                            {{ copied ? "✓" : "⎘" }}
                        </button>
                    </div>

                    <!-- ── Prev / Next ─────────────────────────────────────── -->
                    <AppPagination
                        :links="{
                            prev: prevArticle
                                ? `/view/${prevArticle.id}/${prevArticle.slug}`
                                : null,
                            next: nextArticle
                                ? `/view/${nextArticle.id}/${nextArticle.slug}`
                                : null,
                        }"
                        :titles="{
                            prev: prevArticle?.title ?? null,
                            next: nextArticle?.title ?? null,
                        }"
                        :labels="{ prev: 'PREVIOUS', next: 'NEXT' }"
                    />
                </main>
            </div>
        </div>
    </div>

    <AppFooter
        :popular-tags="popularTags"
        :top-articles="topArticles"
        :related-articles="relatedArticles"
    />
</template>

<script setup>
import AppFooter from "@/Components/AppFooter.vue";
import AppHeader from "@/Components/AppHeader.vue";
import AppPagination from "@/Components/AppPagination.vue";
import ArticleCard from "@/Components/ArticleCard.vue";

import { computed, ref } from "vue";

const props = defineProps({
    article: Object,
    prevArticle: { type: Object, default: null },
    nextArticle: { type: Object, default: null },
    relatedArticles: { type: Array, default: () => [] },
    popularTags: { type: Array, default: () => [] },
    topArticles: { type: Array, default: () => [] },
});

const copied = ref(false);
const encodedUrl = computed(() => encodeURIComponent(window.location.href));
const encodedTitle = computed(() => encodeURIComponent(props.article.title));

async function copyLink() {
    try {
        await navigator.clipboard.writeText(window.location.href);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        /* silent */
    }
}
</script>

<style scoped>
.share-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 4px;
    border: 1px solid #67625b;
    border-right-color: #353535;
    border-bottom: 2px solid #353535;
    background: #4f4943;
    color: #ebe5cb;
    font-family: "Ubuntu", sans-serif;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    transition:
        background 0.15s,
        color 0.15s;
}
.share-btn:hover {
    background: #c3e062;
    color: #2a2820;
    border-color: #c3e062;
    text-decoration: none;
}
</style>

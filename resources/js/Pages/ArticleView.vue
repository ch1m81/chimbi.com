<template>
    <AppHead
        :title="article.title"
        :description="articleDescription"
        :image="articleImage"
        type="article"
    />

    <!-- Top accent line -->
    <div class="z-50 fixed left-0 top-0 h-2.5 w-full bg-[#383838]"></div>

    <!-- Outer shell -->
    <div class="overflow-x-hidden pt-2.5 w-full">
        <!-- Page wrapper: max 1020px, centered -->
        <div class="mx-auto max-w-[1020px]">
            <AppHeader />

            <div
                class="left-[-50%] relative h-px w-[200%] bg-[#383838] ll"
            ></div>

            <div class="mb-10 mt-6 min-h-screen">
                <!-- Same column as Home: 630px pinned to the right -->
                <main class="ml-auto mr-0 w-full sm:w-[630px]">
                    <!-- Article card (read-more slider hidden) -->
                    <ArticleCard :article="article" :single-view="true" />

                    <!-- ── Share row ───────────────────────────────────────── -->
                    <!-- <div
                        class="flex gap-3 items-center mt-1 pb-4 px-2 font-['Reenie_Beanie'] text-2xl border-[#4f4943] border-b"
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
                    </div> -->

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
import AppHead from "@/Components/AppHead.vue";
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

const articleImage = computed(() => {
    if (props.article.thumbnail_url) return props.article.thumbnail_url;
    if (props.article.thumbnail)
        return `/storage/articles/${props.article.thumbnail}`;
    return null;
});

const articleDescription = computed(() => {
    if (!props.article.body) return "chimbi h0mepage";
    const tmp = document.createElement("div");
    tmp.innerHTML = props.article.body;
    const text = (tmp.textContent || tmp.innerText || "")
        .replace(/\s+/g, " ")
        .trim()
        .slice(0, 160);
    return text || "chimbi h0mepage";
});
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

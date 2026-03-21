<template>
    <!-- Top accent line -->
    <div class="fixed top-0 left-0 w-full h-2.5 bg-[#383838] z-50"></div>

    <div class="w-full pt-2.5">
        <div class="max-w-[1020px] mx-auto">
            <AppHeader />

            <div
                class="ll h-px bg-[#383838] relative w-[200%] left-[-50%]"
            ></div>

            <div class="min-h-screen mb-10 mt-6">
                <main
                    class="w-full sm:w-[630px] ml-auto mr-0 border-l border-[#4f4943]"
                >
                    <div
                        class="flex flex-wrap justify-center leading-[1.8] tracking-[0.1em] py-10 [text-shadow:1px_3px_1px_#4f4943]"
                    >
                        <Link
                            v-for="tag in tags"
                            :key="tag.slug"
                            :href="`/?tag=${tag.slug}`"
                            :class="tagSizeClass(tag.count)"
                            class="text-[#ebe5cb]! px-4 inline hover:bg-[#c3e062] hover:text-[#4f4943]! hover:no-underline! hover:[text-shadow:none] hover:z-10"
                            >{{ tag.name }}</Link
                        >
                    </div>
                </main>
            </div>
        </div>
    </div>

    <AppFooter :popular-tags="popularTags" :top-articles="topArticles" />
</template>

<script setup>
import AppFooter from "@/Components/AppFooter.vue";
import AppHeader from "@/Components/AppHeader.vue";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    tags: { type: Array, default: () => [] },
    popularTags: { type: Array, default: () => [] },
    topArticles: { type: Array, default: () => [] },
});

const maxCount = computed(() => Math.max(...props.tags.map((t) => t.count), 1));

function tagSizeClass(count) {
    const logCount = Math.log(count + 1);
    const logMax = Math.log(maxCount.value + 1);
    const r = logCount / logMax;

    if (r > 0.7) return "text-3xl";
    if (r > 0.55) return "text-2xl";
    if (r > 0.28) return "text-xl";
    return "text-lg";
}
</script>

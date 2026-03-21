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
                    <!-- ── Year selector ──────────────────────────────────── -->
                    <div
                        class="flex flex-wrap gap-2 px-2 pt-4 pb-2 border-[#4f4943] bg-[#4f4943]"
                    >
                        <Link
                            v-for="y in archive"
                            :key="y.year"
                            :href="`/archive?year=${y.year}`"
                            class="font-['Ubuntu'] text-2xl px-3 py-1 rounded hover:no-underline! transition-colors"
                            :class="
                                selectedYear === y.year
                                    ? 'bg-[#c3e062] text-[#2a2820]!'
                                    : ' text-[#ebe5cb]! hover:bg-[#c3e062] hover:text-[#2a2820]!'
                            "
                            >{{ y.year }}</Link
                        >
                    </div>

                    <!-- ── Month selector (only when year selected) ───────── -->
                    <div
                        v-if="selectedYear && currentYearMonths.length"
                        class="flex flex-wrap gap-2 px-2 py-1 bg-[#4f4943] font-['Ubuntu'] text-lg"
                    >
                        <Link
                            v-for="m in currentYearMonths"
                            :key="m.month"
                            :href="`/archive?year=${selectedYear}&month=${m.month}`"
                            class="px-3 rounded hover:no-underline! transition-colors"
                            :class="
                                selectedMonth === m.month
                                    ? 'bg-[#c3e062] text-[#2a2820]!'
                                    : 'text-[#ebe5cb]! hover:bg-[#c3e062] hover:text-[#2a2820]!'
                            "
                            >{{ m.label }}</Link
                        >
                    </div>

                    <!-- ── Articles ───────────────────────────────────────── -->
                    <div
                        v-if="!selectedYear"
                        class="py-20 pl-5 font-['Reenie_Beanie'] text-3xl text-[#ebe5cb]"
                    >
                        <span>Select a year to browse.</span>
                    </div>

                    <div
                        v-else-if="articles.data.length === 0"
                        class="py-20 pl-5 font-['Reenie_Beanie'] text-3xl text-[#ebe5cb]"
                    >
                        <span>No articles found.</span>
                    </div>

                    <template v-else>
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
                    </template>
                </main>
            </div>
        </div>
    </div>

    <AppFooter :popular-tags="popularTags" :top-articles="topArticles" />
</template>

<script setup>
import AppFooter from "@/Components/AppFooter.vue";
import AppHeader from "@/Components/AppHeader.vue";
import AppPagination from "@/Components/AppPagination.vue";
import ArticleCard from "@/Components/ArticleCard.vue";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const MONTHS = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
];

const props = defineProps({
    archive: { type: Array, default: () => [] }, // [{ year, months: [{ month, count }] }]
    articles: {
        type: Object,
        default: () => ({ data: [], prev_page_url: null, next_page_url: null }),
    },
    selectedYear: { type: Number, default: null },
    selectedMonth: { type: Number, default: null },
    popularTags: { type: Array, default: () => [] },
    topArticles: { type: Array, default: () => [] },
});

const currentYearMonths = computed(() => {
    const y = props.archive.find((y) => y.year === props.selectedYear);
    if (!y) return [];
    return y.months.map((m) => ({
        month: m.month,
        label: MONTHS[m.month - 1],
        count: m.count,
    }));
});
</script>

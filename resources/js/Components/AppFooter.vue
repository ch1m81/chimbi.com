<template>
    <!-- Top decorative line -->
    <div
        style="background-image: url(&quot;/slike/fgore.jpg&quot;)"
        class="h-1 w-full"
    ></div>

    <!-- Footer body -->
    <div
        style="background-image: url(&quot;/slike/pozadina_fut.png&quot;)"
        class="w-full"
    >
        <div class="max-w-[1020px] mx-auto py-0 grid md:grid-cols-3 gap-4">
            <!-- ── Tag Cloud ─────────────────────────────────────────────── -->
            <div class="shrink-0 bg-[#4f4943] overflow-hidden">
                <span
                    class="inline-block text-lg font-['Ubuntu'] font-bold text-[#ebe5cb] pl-4 pr-10 py-4 border-t border-t-[#5B564F] border-b border-b-[#35312D] [text-shadow:2px_2px_2px_#35312D]"
                >
                    Tag Cloud
                </span>
                <div
                    class="flex flex-wrap justify-center mt-5 px-1 pb-5 leading-[1.4] tracking-widest [text-shadow:1px_3px_1px_#4f4943]"
                >
                    <a
                        v-for="tag in shuffledTags"
                        :key="tag.slug"
                        :href="`/?tag=${tag.slug}`"
                        :class="tagSizeClass(tag.count)"
                        class="text-[#ebe5cb]! px-2 hover:bg-[#c3e062] hover:text-[#4f4943]! hover:no-underline! hover:z-10 hover:text-shadow-none"
                        >{{ tag.name }}</a
                    >
                </div>
            </div>

            <!-- ── Most Popular ──────────────────────────────────────────── -->
            <div class="flex-1 bg-[#4f4943]">
                <span
                    class="inline-block text-lg font-['Ubuntu'] font-bold text-[#ebe5cb] pl-4 pr-10 py-4 border-t border-t-[#5B564F] border-b border-b-[#35312D] [text-shadow:2px_2px_2px_#35312D]"
                >
                    Most Popular
                </span>
                <ul class="mt-8 list-none p-0 m-0">
                    <li
                        v-for="article in topArticles"
                        :key="article.id"
                        class="border-t border-t-[#5B564F] border-b border-b-[#35312D] first:border-t-0 last:border-b-0"
                    >
                        <a
                            :href="`/view/${article.id}/${article.slug}`"
                            class="block px-4 py-3 font-['Ubuntu'] text-md text-[#ebe5cb]! no-underline hover:bg-[#c3e062] hover:text-[#4f4943]! hover:no-underline! hover:text-shadow-none"
                            >{{ article.title }}</a
                        >
                    </li>
                </ul>
            </div>

            <!-- ── Related Articles (single article page only) ───────────── -->
            <div v-if="hasRelated" class="flex-1 bg-[#4f4943]">
                <span
                    class="inline-block text-lg font-['Ubuntu'] font-bold text-[#ebe5cb] pl-4 pr-10 py-4 border-t border-t-[#5B564F] border-b border-b-[#35312D] [text-shadow:2px_2px_2px_#35312D]"
                >
                    Related Articles
                </span>
                <ul class="mt-8 list-none p-0 m-0">
                    <li
                        v-for="article in relatedArticles"
                        :key="article.id"
                        class="border-t border-t-[#5B564F] border-b border-b-[#35312D] first:border-t-0 last:border-b-0"
                    >
                        <a
                            :href="`/view/${article.id}/${article.slug}`"
                            class="block px-4 py-3 font-['Ubuntu'] text-md text-[#ebe5cb]! no-underline hover:bg-[#c3e062] hover:text-[#4f4943]! hover:no-underline! hover:text-shadow-none"
                            >{{ article.title }}</a
                        >
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bottom decorative line -->
    <div
        style="background-image: url(&quot;/slike/fdole.jpg&quot;)"
        class="h-px w-full"
    ></div>

    <!-- Sub-footer -->
    <div
        style="background-image: url(&quot;/slike/pozadina_podfooter.png&quot;)"
        class="h-23 w-full"
    >
        <div class="max-w-[700px] mx-auto flex items-center">
            <!-- RSS -->
            <div class="w-20 h-23 overflow-hidden bg-[#67625B] shrink-0 p-5">
                <div
                    class="h-[32px] w-[50px] overflow-hidden"
                    style="
                        background: url(&quot;/slike/ikonice/rss.png&quot;)
                            no-repeat 0 -32px;
                    "
                >
                    <a
                        href="http://feeds.feedburner.com/ChimbisRssFeed"
                        class="block w-full h-full indent-[-9999px]"
                        style="
                            background: url(&quot;/slike/ikonice/rss.png&quot;)
                                no-repeat 0 0;
                        "
                    ></a>
                </div>
            </div>

            <!-- Copyright -->
            <div
                class="ml-5 h-23 bg-[#5B564F] w-80 shrink-0 flex flex-col justify-end-safe"
            >
                <p
                    class="font-['Ubuntu'] text-lg text-[#ebe5cb] tracking-widest leading-none pl-5 py-px"
                >
                    chimbi the king
                </p>
                <p
                    class="font-['Ubuntu'] text-sm text-[#ebe5cb] tracking-widest leading-none pl-5 py-1"
                >
                    "©" kopirajt, no kako bre <span>\o/</span>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    popularTags: { type: Array, default: () => [] },
    topArticles: { type: Array, default: () => [] },
    relatedArticles: { type: Array, default: () => [] },
});

const hasRelated = computed(() => props.relatedArticles.length > 0);
const maxCount = computed(() =>
    Math.max(...props.popularTags.map((t) => t.count), 1),
);

const shuffledTags = computed(() => {
    const arr = [...props.popularTags];
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
});

function tagSizeClass(count) {
    const logCount = Math.log(count + 1);
    const logMax = Math.log(maxCount.value + 1);
    const r = logCount / logMax;

    if (r > 0.85) return "text-2xl";
    if (r > 0.7) return "text-xl";
    if (r > 0.55) return "text-lg";
    if (r > 0.4) return "text-md";
    if (r > 0.28) return "text-base";
    if (r > 0.16) return "text-sm";
    return "text-xs";
}
</script>

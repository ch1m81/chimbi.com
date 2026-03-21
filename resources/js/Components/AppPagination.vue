<template>
    <div class="flex justify-between items-center my-12 min-h-7">
        <div
            v-if="links.prev"
            class="prev-side relative z-[1] min-w-0 max-w-[45%] pl-10 pr-2 py-4 cursor-pointer transition-[margin,max-width] duration-[50ms,150ms] hover:max-w-[90%] hover:-ml-4 hover:z-10"
            style="
                background: url(&quot;/slike/ikonice/nazad.png&quot;) no-repeat
                    left center;
            "
        >
            <Link
                :href="links.prev"
                class="text-[#c3e062] hover:no-underline! flex flex-col min-w-0 max-w-full overflow-hidden"
            >
                <span
                    class="text-4xl leading-none font-['Reenie_Beanie'] font-bold whitespace-nowrap"
                    >{{ labels.prev }}</span
                >
                <span
                    v-if="titles.prev"
                    class="title-span block text-md text-[#ebe5cb] capitalize leading-tight whitespace-nowrap overflow-hidden text-ellipsis max-w-full"
                    >{{ titles.prev }}</span
                >
            </Link>
        </div>
        <div v-else></div>

        <div
            v-if="links.next"
            class="next-side relative z-[1] min-w-0 max-w-[45%] pr-10 pl-2 py-4 cursor-pointer text-right transition-[margin,max-width] duration-[50ms,150ms] hover:max-w-[90%] hover:-mr-4 hover:z-10"
            style="
                background: url(&quot;/slike/ikonice/napred.png&quot;) no-repeat
                    right center;
            "
        >
            <Link
                :href="links.next"
                class="text-[#c3e062] hover:no-underline! flex flex-col items-end min-w-0 max-w-full overflow-hidden"
            >
                <span
                    class="text-4xl leading-none font-['Reenie_Beanie'] font-bold whitespace-nowrap"
                    >{{ labels.next }}</span
                >
                <span
                    v-if="titles.next"
                    class="title-span block text-md text-[#ebe5cb] capitalize leading-tight whitespace-nowrap overflow-hidden text-ellipsis max-w-full"
                    >{{ titles.next }}</span
                >
            </Link>
        </div>
        <div v-else></div>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
defineProps({
    links: {
        type: Object,
        required: true,
    },
    titles: {
        type: Object,
        default: () => ({ prev: null, next: null }),
    },
    labels: {
        type: Object,
        default: () => ({ prev: "Newer Posts", next: "Older Posts" }),
    },
});
</script>

<style scoped>
/* Hover: reveal full title painted over the other side */
.prev-side:hover .title-span,
.next-side:hover .title-span {
    overflow: visible;
    text-overflow: unset;
    background-image: url("/slike/pozadina.png");
    background-color: #5a5549;
    padding: 0 6px 0 0;
}
</style>

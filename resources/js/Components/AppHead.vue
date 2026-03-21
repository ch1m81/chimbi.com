<template>
    <Head>
        <title>{{ fullTitle }}</title>
        <meta name="description" :content="description" />
        <link rel="canonical" :href="canonicalUrl" />

        <!-- Open Graph -->
        <meta property="og:site_name" content="chimbi h0mepage" />
        <meta property="og:type" :content="type" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:title" :content="fullTitle" />
        <meta property="og:description" :content="description" />
        <meta v-if="image" property="og:image" :content="image" />

        <!-- Twitter/X -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="fullTitle" />
        <meta name="twitter:description" :content="description" />
        <meta v-if="image" name="twitter:image" :content="image" />
    </Head>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    title: { type: String, default: null },
    description: {
        type: String,
        default: "chimbi h0mepage — chimbi-jevo igraliste \o/",
    },
    image: { type: String, default: null },
    type: { type: String, default: "website" },
});

const canonicalUrl = computed(() => window.location.href.split("?")[0]);

const fullTitle = computed(() =>
    props.title ? `${props.title}` : "chimbi h0mepage",
);
</script>

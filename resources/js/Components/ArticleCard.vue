<template>
    <!-- Card wrapper -->
    <article class="article-card relative pb-px first:-mt-6 cursor-pointer">
        <!-- Vote button -->
        <div
            class="vote-container hidden md:flex md:absolute font-['Ubuntu'] text-md font-extrabold top-28 -left-37 items-center justify-center w-37 h-58"
        >
            <a
                href="#"
                class="love-btn relative left-6s hover:no-underline active:text-[#c3e062] left-4"
                :class="{ voted: hasVoted }"
                @click.prevent="loveArticle"
            >
                <div
                    class="love-icon relative mt-10 cursor-pointer w-12 h-12 overflow-visible"
                    :class="{ bumping: isBumping }"
                >
                    <p
                        class="love-score absolute text-center text-[#ebe5cb] leading-[1.6] -ml-5 mt-2"
                    >
                        {{ currentLove }}
                    </p>
                </div>
            </a>
        </div>

        <!-- Title bar -->
        <div
            class="article-title relative font-['Ubuntu'] capitalize text-xl leading-normal sm:-left-18 sm:top-8 top:1 py-2 px-5 left-0 w-[90%] h-14"
        >
            <Link
                :href="`/view/${article.id}/${article.slug}`"
                class="align-text-top text-[#ebe5cb] hover:text-[#c3e062] hover:no-underline"
            >
                {{ article.title }}
            </Link>
        </div>

        <span
            v-if="!article.published && isAdmin"
            class="absolute top-0 left-0 text-base px-2 mx-auto bg-red-800 text-white font-['Ubuntu'] z-10"
            >draft - not published yet</span
        >

        <!-- Edit button (admin only) -->
        <a
            v-if="isAdmin"
            :href="`/chimbi/edit/${article.id}`"
            class="absolute top-0 right-0 z-10 text-base px-2 bg-[#4f4943] hover:bg-[#c3e062] hover:text-[#2a2820]! font-['Ubuntu'] no-underline! text-[#ebe5cb]"
            >✎ edit</a
        >

        <!-- Date stamp -->
        <div
            class="article-date {{ article.published_at }} px-2 py-1 sm:top-9 -top-1 xl:-right-4 right-0 absolute text-[#ebe5cb] font-['Ubuntu'] text-l leading-normal h-8"
        >
            {{ article.published_at }}
        </div>

        <!-- Source URL -->
        <div
            v-if="article.source_url"
            class="max-w-full pt-10 px-16 pb-2 font-['Reenie_Beanie'] text-3xl"
        >
            <span
                >Found on:
                <a
                    :href="article.source_url"
                    target="_blank"
                    rel="noopener"
                    class="text-[#c3e062] hover:underline"
                >
                    {{ shortUrl(article.source_url) }}
                </a>
            </span>
        </div>

        <!-- YouTube embed (list view only) -->
        <div
            v-if="article.youtube_code && !singleView"
            id="article-body"
            class="article-body"
        >
            <iframe
                :src="`https://www.youtube.com/embed/${article.youtube_code}`"
                frameborder="0"
                allowfullscreen
                loading="lazy"
                width="560"
                height="315"
            ></iframe>
        </div>

        <!-- Full body HTML (single view shows everything, list view shows extracted iframe) -->
        <div
            v-else-if="article.body"
            id="article-body"
            class="article-body"
            v-html="article.body"
        ></div>

        <!-- Local thumbnail -->
        <div
            v-else-if="article.thumbnail"
            id="article-body"
            class="article-body"
        >
            <img
                :src="`/slike/slike_post/${article.thumbnail}`"
                :alt="article.title"
                loading="lazy"
                @error="onImgError"
            />
        </div>

        <!-- External thumbnail fallback -->
        <div
            v-else-if="article.thumbnail_url"
            id="article-body"
            class="article-body"
        >
            <img
                :src="article.thumbnail_url"
                :alt="article.title"
                loading="lazy"
                @error="onImgError"
            />
        </div>

        <!-- Tags -->
        <div
            v-if="article.tags.length"
            class="article-tags static md:absolute text-left md:text-right md:-left-1/3 font-['Ubuntu'] text-base text-[#ebe5cb] bottom-15 lg:-left-90 -left-56 w-full md:w-1/3 lg:w-xs px-2 md:px-0"
        >
            <Link
                v-for="tag in article.tags.slice(0, 10)"
                :key="tag.slug"
                :href="`/?tag=${tag.slug}`"
                class="tag-link inline-block m-0.5 py-1 pl-8 pr-3 rounded text-[#ebe5cb]! hover:text-[#504d48]! hover:bg-[#c3e062]! hover:no-underline! hover:rounded-none"
                >{{ tag.name }}</Link
            >
        </div>

        <!-- Read more slider -->
        <div
            v-if="!singleView"
            class="overflow-hidden border-b border-[#4f4943] text-center"
        >
            <div class="overflow-hidden border-b border-[#4f4943] text-center">
                <div class="relative h-16 overflow-hidden read-more-slider">
                    <div
                        class="read-more-btn w-60 py-4 ml-auto mr-8 font-['Reenie_Beanie'] text-4xl bg-[#4f4943] border border-[#67625b] border-r-[#353535] border-b-[#353535] rounded-lg"
                    >
                        <Link
                            :href="`/view/${article.id}/${article.slug}`"
                            class="text-[#c3e062] hover:underline"
                            >read more...</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>

<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    article: Object,
    singleView: { type: Boolean, default: false },
});

const isAdmin = computed(() => usePage().props.isAdmin);
const currentLove = ref(props.article.love);
const hasVoted = ref(false);
const isBumping = ref(false);

async function loveArticle() {
    if (hasVoted.value) return;
    currentLove.value++;
    hasVoted.value = true;
    isBumping.value = true;
    setTimeout(() => (isBumping.value = false), 400);
    try {
        const res = await fetch(`/articles/${props.article.id}/love`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
                "Content-Type": "application/json",
            },
        });
        const data = await res.json();
        if (data.already_voted) {
            currentLove.value = props.article.love;
            hasVoted.value = false;
        } else {
            currentLove.value = data.love;
        }
    } catch {
        currentLove.value = props.article.love;
        hasVoted.value = false;
    }
}

function shortUrl(url) {
    try {
        return new URL(url).hostname.replace("www.", "");
    } catch {
        return url.substring(0, 40);
    }
}

function onImgError(e) {
    e.target.closest(".article-body")?.style.setProperty("display", "none");
}
</script>

<style scoped>
/* ── Card ───────────────────────────────────────────────────────────────── */
.article-card {
    background: linear-gradient(
        to left,
        transparent 0%,
        rgba(255, 255, 255, 0.03) 60%,
        rgba(255, 255, 255, 0.07) 100%
    );
}
.article-card:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

/* ── Vote container (sprite bg) ─────────────────────────────────────────── */
.vote-container {
    background: url("/slike/pozadina_vote.png") no-repeat right;
}

/* ── Love icon (sprite) ─────────────────────────────────────────────────── */
.love-icon {
    background: url("/slike/ikonice/ruka_on.png") no-repeat 0 0 / 24px 48px;
    width: 48px;
    height: 24px;
}
.love-btn:hover .love-icon,
.love-btn.voted .love-icon {
    background-position: 0 -24px;
}
.love-btn.voted {
    opacity: 0.7;
}
.love-score {
    background: url("/slike/ikonice/chat.png") no-repeat;
    background-size: contain;
    width: 48px;
    height: 40px;
    padding: 9px 2px 0 0;
    top: -42px;
    left: 28px;
}

/* ── Title bar (sprite bg) ──────────────────────────────────────────────── */
.article-title {
    background: url("/slike/pozadina_naslov.png") no-repeat;
}

/* ── Date stamp (sprite bg) ─────────────────────────────────────────────── */
.article-date {
    background: url("/slike/datum.png") no-repeat right;
}

/* ── Body ───────────────────────────────────────────────────────────────── */
.article-body {
    padding: 20px 17px;
    font-family: "Ubuntu", serif;
    font-size: 14px;
    line-height: 1.5em;
    color: #ebe5cb;
}
.article-body p,
.article-body div {
    font-size: 14px;
}
.article-body iframe {
    display: block;
    margin: auto;
    border: 10px solid #524d47;
}
.article-body:hover iframe {
    border-color: #5c5750;
}
.article-body img {
    float: none;
    display: block;
    max-width: 505px;
    margin: 20px auto;
    border: 10px solid #524d47;
}
.article-body img:hover {
    border-color: #5c5750;
}

/* ── Tags (sprite bg) ───────────────────────────────────────────────────── */
.tag-link {
    background: url("/slike/ikonice/tag.png") no-repeat 4px center #4f4943;
}

/* ── Responsive: small screens (<640px) ────────────────────────────────── */
@media (max-width: 639px) {
    .article-body {
        padding: 12px 8px;
    }
    .article-body iframe {
        width: 100%;
        height: auto;
        aspect-ratio: 16 / 9;
    }
    .article-body img {
        max-width: 100%;
    }
}

/* ── Animations ─────────────────────────────────────────────────────────── */
@keyframes slideUpBounce {
    0% {
        top: 51px;
    }
    35% {
        top: 3.8px;
    }
    50% {
        top: 12px;
    }
    75% {
        top: 1.4px;
    }
    100% {
        top: 0;
    }
}
@keyframes slideDown {
    0% {
        top: 0;
    }
    45% {
        top: -5px;
    }
    100% {
        top: 51px;
    }
}
@keyframes bump {
    0% {
        transform: scale(1);
    }
    40% {
        transform: scale(1.3);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
    }
}

.read-more-slider {
    animation: slideDown 0.4s linear forwards;
}
.article-card:hover .read-more-slider {
    animation: slideUpBounce 0.4s linear forwards;
}
.bumping {
    animation: bump 0.35s ease;
}
</style>
<style>
.article-body iframe {
    display: block;
    margin: auto;
    border: 10px solid #524d47;
    max-width: 100%;
}

.article-body iframe + p {
    margin-top: 30px;
    margin-bottom: 30px;
}
.article-body img {
    max-width: 100%;
    height: auto !important;
    display: block;
    margin: 20px auto;
    border: 10px solid #524d47;
}
@media (max-width: 639px) {
    .article-body iframe[src*="youtube.com"],
    .article-body iframe[src*="youtu.be"],
    .article-body iframe[src*="vimeo.com"] {
        width: 100% !important;
        height: auto !important;
        aspect-ratio: 16 / 9;
    }
}
</style>

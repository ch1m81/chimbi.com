<template>
    <header
        class="header-wrap relative h-auto w-full before:absolute before:inset-0 before:bg-[radial-gradient(ellipse_at_55%_20%,rgba(255,255,255,0.18)_0%,transparent_40%)] before:pointer-events-none"
    >
        <!-- ── Admin badge ─────────────────────────────────────────────────── -->
        <div class="admin-badge absolute top-3 left-2 md:left-10 z-10">
            <a
                href="/rucno/chimbi/"
                target="_blank"
                class="admin-badge-link"
            ></a>
        </div>

        <!-- ── Search: desktop (lg+) always visible, absolutely positioned ── -->
        <div class="search-wrap hidden sm:flex absolute right-4 top-3 z-10">
            <form @submit.prevent="doSearch" class="flex">
                <fieldset class="flex border-none items-baseline">
                    <input
                        v-model="searchInput"
                        type="text"
                        maxlength="25"
                        placeholder="Enter text to search"
                        class="search-input"
                    />
                    <input class="search-btn" type="submit" value="Go" />
                </fieldset>
            </form>
        </div>

        <!-- ── Search icon button (< lg only) ─────────────────────────────── -->
        <button
            class="flex sm:hidden absolute top-3 right-3 z-20 w-9 h-9 items-center justify-center text-[#ebe5cb] bg-[#383838] rounded"
            @click="searchOpen = !searchOpen"
            aria-label="Toggle search"
        >
            <svg
                v-if="!searchOpen"
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
            </svg>
            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>

        <!-- ── Search dropdown (< lg, toggled) ────────────────────────────── -->
        <div
            v-show="searchOpen"
            class="sm:hidden absolute -bottom-1 left-0 w-full z-100 flex px-3 py-2 bg-[#2e2b26]"
        >
            <form @submit.prevent="doSearch" class="flex w-full">
                <fieldset class="flex border-none w-full">
                    <input
                        v-model="searchInput"
                        type="text"
                        maxlength="25"
                        placeholder="Enter text to search"
                        class="search-input flex-1"
                    />
                    <input
                        class="search-btn shrink-0"
                        type="submit"
                        value="Go"
                    />
                </fieldset>
            </form>
        </div>

        <!-- ── Logo + nav ──────────────────────────────────────────────────── -->
        <div
            class="flex flex-col md:flex-row items-center md:items-start justify-between pt-10"
        >
            <!-- Logo -->
            <div class="logo-wrap shrink-0 mt-6 ml-10 lg:mt-10 lg:ml-20">
                <Link href="/"
                    ><img
                        :src="'/slike/logo.png'"
                        alt="chimbi"
                        class="logo-img"
                /></Link>
            </div>

            <!-- Nav: right of logo on lg, stacked below on smaller -->
            <nav
                class="nav-wrap flex self-end justify-start sm:justify-end shrink grow mt-3 sm:mt-12 sm:ml-0 mr-10 sm:mr-0 z-99 pr-4"
            >
                <ul class="nav-list">
                    <li
                        :class="['nav1', { 'nav1-active': sort === 'popular' }]"
                    >
                        <Link href="/popular"></Link>
                    </li>
                    <li :class="['nav2', { 'nav2-active': sort === 'newest' }]">
                        <Link href="/"></Link>
                    </li>
                    <li :class="['nav3', { 'nav3-active': sort === 'tagged' }]">
                        <Link href="/tagged"></Link>
                    </li>
                    <li class="nav4"><Link href="/"></Link></li>
                    <li
                        :class="['nav5', { 'nav5-active': sort === 'archive' }]"
                    >
                        <Link href="/archive"></Link>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
</template>

<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const searchInput = ref("");
const searchOpen = ref(false);

const sort = computed(() => usePage().props.sort ?? "newest");

function doSearch() {
    searchOpen.value = false;
    router.get("/", { search: searchInput.value }, { preserveState: true });
}
</script>

<style scoped>
/* ── Logo ───────────────────────────────────────────────────────────────── */
.logo-wrap {
    width: clamp(160px, 42vw, 547px);
    height: auto;
}
.logo-img {
    width: 100%;
    height: auto;
    max-width: 547px;
    max-height: 197px;
}

/* ── Admin badge ────────────────────────────────────────────────────────── */
.admin-badge {
    width: 25px;
    height: 24px;
    overflow: hidden;
    background: url("/slike/ikonice/badge.png") no-repeat 0 -24px;
}
.admin-badge-link {
    display: block;
    width: 100%;
    height: 100%;
    background: url("/slike/ikonice/badge.png") no-repeat 0 0;
    text-indent: -9999px;
}

/* ── Search ─────────────────────────────────────────────────────────────── */
.search-wrap {
    width: 292px;
    height: 36px;
    background: url("/slike/search_pozadina.png") no-repeat;
}
.search-input {
    height: 28px;
    padding: 2px 4px;
    background: #292929;
    color: #ebe5cb;
    font-family: "Ubuntu", serif;
    font-size: 12px;
    border: none;
    outline: none;
}
@media (min-width: 640px) {
    .search-input {
        width: 200px;
        margin: 4px 5px 3px 5px;
    }
}
.search-btn {
    width: 79px;
    height: 28px;
    cursor: pointer;
    border: none;
    text-indent: -9999px;
    background: url("/slike/search_dugme.png") no-repeat center;
}
.search-btn:hover {
    background: url("/slike/search_dugme_hover.png") no-repeat center;
}

/* ── Nav list ───────────────────────────────────────────────────────────── */
.nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
    width: 272px;
    height: 147px;
    background: url("/slike/meni_putokaz.png") no-repeat left top;
}

@media (max-width: 425px) {
    .nav-wrap {
        transform-origin: top right;
        transform: scale(clamp(0.55, calc((100vw - 160px) / 180px), 1));
    }
}

/* Links: invisible text, full hit area */
.nav-list a {
    display: block;
    width: 100%;
    height: 100%;
    text-indent: -9999px;
    color: transparent;
    outline: none;
}

/* Individual sprite items */
.nav1 {
    width: 197px;
    height: 25px;
    background: url("/slike/meni_putokaz.png") no-repeat 0 0;
}
.nav2 {
    width: 135px;
    height: 25px;
    background: url("/slike/meni_putokaz.png") no-repeat 0 -34px;
    margin-top: 9px;
}
.nav3 {
    width: 192px;
    height: 25px;
    background: url("/slike/meni_putokaz.png") no-repeat 0 -65px;
    margin-top: 6px;
    padding-left: 80px;
}
.nav3 a {
    margin-left: 15px;
}
.nav4 {
    width: 75px;
    height: 24px;
    background: url("/slike/meni_putokaz.png") no-repeat 0 -99px;
    float: left;
    margin-top: 9px;
}
.nav5 {
    width: 158px;
    height: 24px;
    background: url("/slike/meni_putokaz.png") no-repeat -114px -99px;
    float: left;
    margin-top: 9px;
    margin-left: 39px;
}

.nav1:hover,
.nav1-active {
    background: url("/slike/meni_putokaz.png") no-repeat 0 -150px;
}
.nav2:hover,
.nav2-active {
    background: url("/slike/meni_putokaz.png") no-repeat 0 -184px;
}
.nav3:hover,
.nav3-active {
    background: url("/slike/meni_putokaz.png") no-repeat 0 -215px;
    padding-left: 65px;
}
.nav4:hover {
    background: url("/slike/meni_putokaz.png") no-repeat 0 -249px;
}
.nav5:hover,
.nav5-active {
    background: url("/slike/meni_putokaz.png") no-repeat -114px -249px;
}
</style>

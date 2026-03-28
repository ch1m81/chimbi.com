<template>
    <div class="min-h-screen bg-[#2a2820] text-[#ebe5cb] font-['Ubuntu']">
        <div
            class="bg-[#383838] border-b border-[#4f4943] px-6 py-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between sticky top-0 z-40"
        >
            <div class="flex items-center gap-4 min-w-0">
                <Link
                    href="/"
                    class="text-[#ebe5cb]! hover:text-[#c3e062]! hover:no-underline! text-2xl shrink-0"
                    >🏠</Link
                >
                <span class="text-[#6b6459]">/</span>
                <Link
                    href="/chimbi/create"
                    class="text-[#ebe5cb] hover:text-[#c3e062] text-base hover:no-underline"
                    >new article</Link
                >
                <span class="text-[#6b6459]">/</span>
                <span class="text-2xl text-[#c3e062] truncate"
                    >Tshoot link inspector</span
                >
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    @click="scanAll(false)"
                    :disabled="scanState.running"
                    class="admin-btn-secondary"
                >
                    {{ scanState.running ? "Scanning..." : "Scan cached" }}
                </button>
                <button
                    @click="scanAll(true)"
                    :disabled="scanState.running"
                    class="admin-btn-primary"
                >
                    Refresh live scan
                </button>
                <button @click="doLogout" class="admin-btn-muted">
                    Logout
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-5">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="admin-stat">
                    <div class="admin-stat-label">Articles</div>
                    <div class="admin-stat-value">{{ summary.article_count }}</div>
                </div>
                <div class="admin-stat">
                    <div class="admin-stat-label">With issues</div>
                    <div class="admin-stat-value text-red-300">
                        {{ summary.articles_with_issues }}
                    </div>
                </div>
                <div class="admin-stat">
                    <div class="admin-stat-label">Broken links</div>
                    <div class="admin-stat-value text-red-200">
                        {{ summary.issue_count }}
                    </div>
                </div>
                <div class="admin-stat">
                    <div class="admin-stat-label">Blocked links</div>
                    <div class="admin-stat-value text-amber-200">
                        {{ summary.blocked_count }}
                    </div>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div
                    class="bg-[#383838] border border-[#4f4943] rounded-lg px-4 py-4 flex flex-col gap-4"
                >
                    <label class="text-sm uppercase tracking-[0.2em] text-[#8b8477]">
                        Filter articles
                    </label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search title, slug, body, or URL..."
                        class="mt-2 w-full bg-[#1f1d18] border border-[#5b554f] rounded-md px-4 py-3 text-base outline-none transition-colors focus:border-[#c3e062]"
                    />
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs uppercase tracking-[0.2em] text-[#8b8477]">
                                Issue filter
                            </span>
                            <select
                                v-model="issueFilter"
                                class="mt-2 w-full bg-[#1f1d18] border border-[#5b554f] rounded-md px-4 py-3 text-base outline-none transition-colors focus:border-[#c3e062]"
                            >
                                <option value="all">All articles</option>
                                <option value="any">Any issue</option>
                                <option value="broken">Broken only</option>
                                <option value="blocked">Locked / blocked only</option>
                                <option value="clean">Clean only</option>
                            </select>
                        </label>

                        <div class="rounded-md border border-[#5b554f] bg-[#2d2b24] px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.2em] text-[#8b8477]">
                                Quick jump
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-if="issueArticleIds.length"
                                    @click="jumpToIssue(issueArticleIds[0])"
                                    class="admin-btn-secondary text-sm"
                                >
                                    First issue
                                </button>
                                <button
                                    v-if="issueArticleIds.length"
                                    @click="jumpToNextIssue"
                                    class="admin-btn-secondary text-sm"
                                >
                                    Next issue
                                </button>
                                <span v-if="!issueArticleIds.length" class="text-sm text-[#8b8477]">
                                    No issue articles yet
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-[#383838] border border-[#4f4943] rounded-lg px-4 py-4">
                    <div class="text-xs uppercase tracking-[0.2em] text-[#8b8477]">
                        Articles with issues
                    </div>
                    <div class="mt-3 max-h-56 overflow-y-auto space-y-2 pr-1">
                        <button
                            v-for="article in issueArticles"
                            :key="`issue-${article.id}`"
                            @click="jumpToIssue(article.id)"
                            class="w-full text-left rounded-md border border-red-500/35 bg-red-950/20 px-3 py-2 hover:border-red-400"
                        >
                            <div class="text-sm text-[#f8f4e6]">
                                #{{ article.id }} {{ article.title || "Untitled article" }}
                            </div>
                            <div class="mt-1 text-xs text-[#f0b4b4]">
                                {{ article.issue_count }} broken
                                <span v-if="article.blocked_count">
                                    · {{ article.blocked_count }} locked
                                </span>
                            </div>
                        </button>
                        <div v-if="!issueArticles.length" class="text-sm text-[#8b8477]">
                            Scan to populate issue jump list.
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="scanError"
                class="border border-red-500 bg-red-950/60 text-red-100 rounded-md px-4 py-3"
            >
                {{ scanError }}
            </div>

            <div class="space-y-4">
                <article
                    v-for="article in filteredArticles"
                    :key="article.id"
                    :id="articleAnchor(article.id)"
                    class="rounded-xl border bg-[#383838] overflow-hidden"
                    :class="
                        article.has_issues
                            ? 'border-red-500 shadow-[0_0_0_1px_rgba(239,68,68,0.35)]'
                            : 'border-[#4f4943]'
                    "
                >
                    <button
                        type="button"
                        class="w-full px-5 py-4 border-b border-[#4f4943] text-left hover:bg-white/3 transition-colors"
                        @click="toggleExpanded(article.id)"
                    >
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span
                                        class="text-[11px] uppercase tracking-[0.25em] text-[#8b8477]"
                                    >
                                        #{{ article.id }}
                                    </span>
                                    <span
                                        v-if="article.has_issues"
                                        class="status-chip status-broken"
                                    >
                                        {{ article.issue_count }} broken
                                    </span>
                                    <span
                                        v-if="article.blocked_count"
                                        class="status-chip status-blocked"
                                    >
                                        {{ article.blocked_count }} locked
                                    </span>
                                    <span
                                        v-if="!article.has_issues && !article.blocked_count && scanState.finished"
                                        class="status-chip status-ok"
                                    >
                                        clean
                                    </span>
                                    <span class="status-chip status-muted">
                                        {{ article.published ? "published" : "draft" }}
                                    </span>
                                    <span class="status-chip status-muted">
                                        {{ article.link_count }} links
                                    </span>
                                </div>

                                <h2 class="text-xl text-[#f8f4e6] leading-tight">
                                    {{ article.title || "Untitled article" }}
                                </h2>
                                <div class="mt-2 text-sm text-[#a9a18f]">
                                    <span class="font-mono">{{ article.slug }}</span>
                                    <span v-if="article.published_at">
                                        · {{ article.published_at }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 shrink-0">
                                <span class="status-chip status-muted">
                                    {{ isExpanded(article.id) ? "expanded" : "collapsed" }}
                                </span>
                            </div>
                        </div>
                    </button>

                    <div v-if="isExpanded(article.id)" class="px-5 py-4 border-b border-[#4f4943] bg-[#312f28]">
                        <div class="flex flex-wrap gap-2 mb-4">
                            <a
                                :href="article.view_url"
                                target="_blank"
                                rel="noopener"
                                class="admin-btn-secondary text-sm hover:no-underline!"
                            >
                                View
                            </a>
                            <Link
                                :href="article.edit_url"
                                class="admin-btn-secondary text-sm hover:no-underline!"
                            >
                                Edit
                            </Link>
                        </div>

                        <div class="text-xs uppercase tracking-[0.2em] text-[#8b8477] mb-2">
                            Body
                        </div>
                        <pre class="whitespace-pre-wrap break-words text-sm leading-6 text-[#ebe5cb] font-mono">{{
                            article.body || "No body content."
                        }}</pre>
                    </div>

                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <div class="text-xs uppercase tracking-[0.2em] text-[#8b8477]">
                                Links
                            </div>
                            <div class="text-xs text-[#8b8477]">
                                Click a broken or locked link to search for a replacement.
                            </div>
                        </div>

                        <div v-if="!article.links.length" class="text-sm text-[#8b8477]">
                            No external links found in this article.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="link in article.links"
                                :key="link.url"
                                class="rounded-lg border border-[#4f4943] bg-[#2d2b24] px-4 py-4"
                                :class="{
                                    'border-red-500 bg-red-950/20': link.scan?.state === 'broken',
                                    'border-amber-500 bg-amber-950/20': link.scan?.state === 'blocked',
                                }"
                            >
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <span
                                                class="status-chip"
                                                :class="statusClass(link.scan?.state)"
                                            >
                                                {{ statusLabel(link.scan) }}
                                            </span>
                                            <span
                                                v-for="source in link.sources"
                                                :key="`${link.url}-${source}`"
                                                class="status-chip status-muted"
                                            >
                                                {{ source }}
                                            </span>
                                            <span
                                                v-if="link.scan?.status_code"
                                                class="status-chip status-muted"
                                            >
                                                HTTP {{ link.scan.status_code }}
                                            </span>
                                        </div>

                                        <a
                                            :href="link.url"
                                            target="_blank"
                                            rel="noopener"
                                            class="block text-[#c3e062] break-all hover:underline"
                                        >
                                            {{ link.url }}
                                        </a>

                                        <div
                                            v-if="link.scan?.reason"
                                            class="mt-2 text-sm text-[#d4cdc0]"
                                        >
                                            {{ link.scan.reason }}
                                        </div>

                                        <div
                                            v-if="link.contexts?.length"
                                            class="mt-3 space-y-2"
                                        >
                                            <div
                                                v-for="context in link.contexts.slice(0, 2)"
                                                :key="context"
                                                class="rounded-md bg-black/15 px-3 py-2 text-xs text-[#bfb7a7] font-mono break-words"
                                            >
                                                {{ context }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 shrink-0">
                                        <button
                                            v-if="canSearchReplacement(link)"
                                            @click="searchReplacement(article, link)"
                                            :disabled="isSearching(article.id, link.url)"
                                            class="admin-btn-primary text-sm"
                                        >
                                            {{
                                                isSearching(article.id, link.url)
                                                    ? "Searching..."
                                                    : "Search replacement"
                                            }}
                                        </button>
                                        <button
                                            v-if="replacementQuery(article.id, link.url)"
                                            @click="openSearch(article.id, link.url)"
                                            class="admin-btn-secondary text-sm"
                                        >
                                            Open search
                                        </button>
                                    </div>
                                </div>

                                <div
                                    v-if="replacementError(article.id, link.url)"
                                    class="mt-3 rounded-md border border-red-500/70 bg-red-950/40 px-3 py-2 text-sm text-red-100"
                                >
                                    {{ replacementError(article.id, link.url) }}
                                </div>

                                <div
                                    v-if="replacementResults(article.id, link.url).length"
                                    class="mt-4 rounded-lg border border-[#5b554f] bg-[#24221d] px-4 py-3"
                                >
                                    <div class="text-xs uppercase tracking-[0.2em] text-[#8b8477] mb-3">
                                        Suggested replacements
                                    </div>

                                    <div class="space-y-3">
                                        <a
                                            v-for="result in replacementResults(article.id, link.url)"
                                            :key="result.url"
                                            :href="result.url"
                                            target="_blank"
                                            rel="noopener"
                                            class="block rounded-md border border-[#4f4943] bg-[#2d2b24] px-3 py-3 hover:border-[#c3e062] hover:no-underline!"
                                        >
                                            <div class="text-[#f8f4e6]">{{ result.title }}</div>
                                            <div class="mt-1 text-xs text-[#8b8477]">
                                                {{ result.host }}
                                            </div>
                                            <div class="mt-2 text-sm text-[#c3e062] break-all">
                                                {{ result.url }}
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="!filteredArticles.length" class="text-center text-[#8b8477] py-10">
                No articles match the current filters.
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
    articles: { type: Array, default: () => [] },
});

const search = ref("");
const issueFilter = ref("all");
const scanError = ref("");
const scanState = ref({
    running: false,
    finished: false,
    summary: null,
});

const expanded = ref({});
const replacementStates = ref({});
const articles = ref(props.articles.map(enhanceArticle));
const currentIssueJumpIndex = ref(-1);

function enhanceArticle(article) {
    return {
        ...article,
        links: (article.links ?? []).map((link) => ({
            ...link,
            scan: link.scan ?? null,
        })),
    };
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? "";
}

function articleMatches(article, query) {
    const haystack = [
        article.title,
        article.slug,
        article.body_preview,
        article.source_url,
        ...(article.links ?? []).map((link) => link.url),
    ]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();

    return haystack.includes(query);
}

const filteredArticles = computed(() => {
    const query = search.value.trim().toLowerCase();

    return articles.value.filter((article) => {
        if (issueFilter.value === "any" && !hasAnyIssue(article)) {
            return false;
        }

        if (issueFilter.value === "broken" && !article.has_issues) {
            return false;
        }

        if (issueFilter.value === "blocked" && !article.blocked_count) {
            return false;
        }

        if (issueFilter.value === "clean" && hasAnyIssue(article)) {
            return false;
        }

        if (!query) {
            return true;
        }

        return articleMatches(article, query);
    });
});

const issueArticles = computed(() =>
    articles.value.filter((article) => hasAnyIssue(article)),
);

const issueArticleIds = computed(() => issueArticles.value.map((article) => article.id));

const summary = computed(() => {
    if (scanState.value.summary) {
        return scanState.value.summary;
    }

    return {
        article_count: articles.value.length,
        issue_count: articles.value.reduce((sum, article) => sum + (article.issue_count ?? 0), 0),
        articles_with_issues: articles.value.filter((article) => article.has_issues).length,
        blocked_count: articles.value.reduce((sum, article) => sum + (article.blocked_count ?? 0), 0),
    };
});

function mergeArticles(nextArticles) {
    const expansionState = { ...expanded.value };

    articles.value = nextArticles.map((article) => enhanceArticle(article));
    expanded.value = expansionState;
    currentIssueJumpIndex.value = -1;
}

async function scanAll(force = false) {
    scanState.value.running = true;
    scanError.value = "";

    try {
        const response = await fetch("/chimbi/tshoot/scan", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({ force }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Scan failed.");
        }

        mergeArticles(data.articles ?? []);
        scanState.value.summary = data.summary ?? null;
        scanState.value.finished = true;
    } catch (error) {
        scanError.value = error.message || "Link scan failed.";
    } finally {
        scanState.value.running = false;
    }
}

function toggleExpanded(articleId) {
    expanded.value = {
        ...expanded.value,
        [articleId]: !expanded.value[articleId],
    };
}

function isExpanded(articleId) {
    return !!expanded.value[articleId];
}

function linkKey(articleId, url) {
    return `${articleId}:${url}`;
}

function getReplacementState(articleId, url) {
    return (
        replacementStates.value[linkKey(articleId, url)] ?? {
            loading: false,
            error: "",
            query: "",
            results: [],
        }
    );
}

function isSearching(articleId, url) {
    return getReplacementState(articleId, url).loading;
}

function replacementResults(articleId, url) {
    return getReplacementState(articleId, url).results ?? [];
}

function replacementError(articleId, url) {
    return getReplacementState(articleId, url).error ?? "";
}

function replacementQuery(articleId, url) {
    return getReplacementState(articleId, url).query ?? "";
}

function canSearchReplacement(link) {
    return ["broken", "blocked"].includes(link.scan?.state ?? "");
}

async function searchReplacement(article, link) {
    const key = linkKey(article.id, link.url);

    replacementStates.value = {
        ...replacementStates.value,
        [key]: {
            loading: true,
            error: "",
            query: "",
            results: [],
        },
    };

    try {
        const response = await fetch("/chimbi/tshoot/search-replacement", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({
                url: link.url,
                article_title: article.title,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Replacement search failed.");
        }

        replacementStates.value = {
            ...replacementStates.value,
            [key]: {
                loading: false,
                error: "",
                query: data.query ?? "",
                results: data.results ?? [],
            },
        };
    } catch (error) {
        replacementStates.value = {
            ...replacementStates.value,
            [key]: {
                loading: false,
                error: error.message || "Replacement search failed.",
                query: "",
                results: [],
            },
        };
    }
}

function openSearch(articleId, url) {
    const query = replacementQuery(articleId, url);
    if (!query) return;

    window.open(
        `https://duckduckgo.com/?q=${encodeURIComponent(query)}`,
        "_blank",
        "noopener",
    );
}

function hasAnyIssue(article) {
    return !!article.has_issues || (article.blocked_count ?? 0) > 0;
}

function articleAnchor(articleId) {
    return `article-${articleId}`;
}

function jumpToIssue(articleId) {
    expanded.value = {
        ...expanded.value,
        [articleId]: true,
    };

    const element = document.getElementById(articleAnchor(articleId));
    element?.scrollIntoView({ behavior: "smooth", block: "start" });

    currentIssueJumpIndex.value = issueArticleIds.value.findIndex((id) => id === articleId);
}

function jumpToNextIssue() {
    if (!issueArticleIds.value.length) return;

    const nextIndex = (currentIssueJumpIndex.value + 1) % issueArticleIds.value.length;
    jumpToIssue(issueArticleIds.value[nextIndex]);
}

function statusLabel(scan) {
    if (!scan) return "Pending";
    return scan.label ?? "Pending";
}

function statusClass(state) {
    if (state === "ok") return "status-ok";
    if (state === "blocked") return "status-blocked";
    if (state === "broken") return "status-broken";
    return "status-muted";
}

function doLogout() {
    router.post("/chimbi/logout");
}

onMounted(() => {
    scanAll(false);
});
</script>

<style scoped>
.admin-btn-primary,
.admin-btn-secondary,
.admin-btn-muted {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    padding: 0.65rem 1rem;
    border-radius: 0.55rem;
    border: 1px solid #4f4943;
    font-size: 0.95rem;
    transition:
        background-color 0.15s ease,
        color 0.15s ease,
        border-color 0.15s ease,
        opacity 0.15s ease;
}

.admin-btn-primary {
    background: #c3e062;
    color: #2a2820;
    border-color: #c3e062;
    font-weight: 700;
}

.admin-btn-primary:hover {
    background: #d4ef73;
    color: #2a2820;
}

.admin-btn-secondary {
    background: #4f4943;
    color: #ebe5cb;
    border-color: #67625b;
}

.admin-btn-secondary:hover {
    background: #67625b;
    color: #ebe5cb;
}

.admin-btn-muted {
    background: #2d2b24;
    color: #d4cdc0;
    border-color: #5b554f;
}

.admin-btn-muted:hover {
    border-color: #8b8477;
    color: #f4f0e1;
}

.admin-stat {
    border: 1px solid #4f4943;
    border-radius: 0.9rem;
    padding: 1.1rem 1.15rem;
    background:
        radial-gradient(circle at top right, rgba(195, 224, 98, 0.12), transparent 35%),
        #383838;
}

.admin-stat-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.22em;
    color: #8b8477;
}

.admin-stat-value {
    margin-top: 0.45rem;
    font-size: 2rem;
    line-height: 1;
    font-weight: 700;
    color: #f8f4e6;
}

.status-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    font-size: 0.73rem;
    line-height: 1;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border: 1px solid transparent;
}

.status-ok {
    background: rgba(163, 230, 53, 0.14);
    border-color: rgba(163, 230, 53, 0.28);
    color: #d9f99d;
}

.status-blocked {
    background: rgba(251, 191, 36, 0.14);
    border-color: rgba(251, 191, 36, 0.3);
    color: #fde68a;
}

.status-broken {
    background: rgba(248, 113, 113, 0.16);
    border-color: rgba(248, 113, 113, 0.34);
    color: #fecaca;
}

.status-muted {
    background: rgba(139, 132, 119, 0.12);
    border-color: rgba(139, 132, 119, 0.24);
    color: #c8c1b3;
}
</style>

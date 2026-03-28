<template>
    <div class="min-h-screen bg-[#2a2820] text-[#ebe5cb] font-['Ubuntu']">
        <div
            ref="mainHeader"
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
                    @click="scanAll(true)"
                    :disabled="scanState.running"
                    class="admin-btn-primary admin-scan-btn disabled:cursor-not-allowed disabled:pointer-events-none"
                    :class="{ 'admin-scan-btn-running': scanState.running }"
                >
                    <span class="inline-flex items-center gap-2">
                        <span class="relative flex h-3 w-3 shrink-0" aria-hidden="true">
                            <span
                                v-if="scanState.running"
                                class="absolute inline-flex h-full w-full rounded-full bg-[#2a2820]/35 opacity-100 animate-ping scale-[1.9]"
                            ></span>
                            <span
                                v-if="scanState.running"
                                class="absolute inline-flex h-full w-full rounded-full bg-white/20 animate-pulse scale-[2.4]"
                            ></span>
                            <span
                                class="relative inline-flex h-3 w-3 rounded-full transition-colors duration-200"
                                :class="scanState.running ? 'bg-[#2a2820] shadow-[0_0_0_2px_rgba(255,255,255,0.45)]' : 'bg-[#2a2820]/40 border border-[#8b8477]'"
                            ></span>
                        </span>
                        <span
                            class="tracking-[0.06em]"
                            :class="scanState.running ? 'text-[#1f1d18]' : ''"
                        >
                            {{ scanButtonLabel }}
                        </span>
                    </span>
                </button>
                <button @click="doLogout" class="admin-btn-muted">
                    Logout
                </button>
            </div>
        </div>

        <div ref="issueNavSentinel" class="h-px w-full"></div>

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-5">
            <div class="grid gap-4 md:grid-cols-4">
                <button
                    type="button"
                    class="admin-stat text-left"
                    :class="{ 'admin-stat-active': issueFilter === 'all' }"
                    @click="issueFilter = 'all'"
                >
                    <div class="admin-stat-label">Articles</div>
                    <div class="admin-stat-value">{{ summary.article_count }}</div>
                </button>
                <button
                    type="button"
                    class="admin-stat text-left"
                    :class="{ 'admin-stat-active': issueFilter === 'any' }"
                    @click="issueFilter = 'any'"
                >
                    <div class="admin-stat-label">With issues</div>
                    <div class="admin-stat-value text-red-300">
                        {{ summary.articles_with_issues }}
                    </div>
                </button>
                <button
                    type="button"
                    class="admin-stat text-left"
                    :class="{ 'admin-stat-active': issueFilter === 'broken' }"
                    @click="issueFilter = 'broken'"
                >
                    <div class="admin-stat-label">Broken links</div>
                    <div class="admin-stat-value text-red-200">
                        {{ summary.issue_count }}
                    </div>
                </button>
                <button
                    type="button"
                    class="admin-stat text-left"
                    :class="{ 'admin-stat-active': issueFilter === 'blocked' }"
                    @click="issueFilter = 'blocked'"
                >
                    <div class="admin-stat-label">Blocked links</div>
                    <div class="admin-stat-value text-amber-200">
                        {{ summary.blocked_count }}
                    </div>
                </button>
            </div>

            <div class="bg-[#383838] border border-[#4f4943] rounded-lg px-4 py-4 flex flex-col gap-4">
                <label class="text-sm uppercase tracking-[0.2em] text-[#8b8477]">
                    Filter articles
                </label>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search title, slug, body, or URL..."
                    class="mt-2 w-full bg-[#1f1d18] border border-[#5b554f] rounded-md px-4 py-3 text-base outline-none transition-colors focus:border-[#c3e062]"
                />
            </div>

            <div class="bg-[#383838] border border-[#4f4943] rounded-lg px-4 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-xs uppercase tracking-[0.2em] text-[#8b8477]">
                        Articles with issues
                    </div>
                    <div class="text-xs text-[#8b8477]">
                        {{ issueArticles.length }} article{{ issueArticles.length === 1 ? "" : "s" }}
                    </div>
                </div>
                <div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    <button
                        v-for="article in issueArticles"
                        :key="`issue-${article.id}`"
                        @click="jumpToIssue(article.id)"
                        class="w-full text-left rounded-md border border-red-500/35 bg-red-950/20 px-3 py-3 hover:border-red-400"
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

            <div
                v-if="scanError"
                class="border border-red-500 bg-red-950/60 text-red-100 rounded-md px-4 py-3"
            >
                {{ scanError }}
            </div>

            <div
                v-if="showStickyIssueNav"
                class="sticky top-[88px] z-30 rounded-lg border border-[#5b554f] bg-[#2d2b24]/95 backdrop-blur px-4 py-3"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="text-sm text-[#d4cdc0]">
                        <span class="text-[#f8f4e6] font-semibold">Issue navigation</span>
                        <span class="text-[#8b8477]">
                            · {{ issueArticleIds.length }} article{{ issueArticleIds.length === 1 ? "" : "s" }} with issues
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            :disabled="!issueArticleIds.length"
                            @click="jumpToPreviousIssue"
                            class="admin-btn-secondary text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Previous issue
                        </button>
                        <button
                            :disabled="!issueArticleIds.length"
                            @click="jumpToNextIssue"
                            class="admin-btn-primary text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Next issue
                        </button>
                    </div>
                </div>
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

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="text-xs uppercase tracking-[0.18em] text-[#8b8477]">
                                        Found on
                                    </span>
                                    <a
                                        v-if="article.primary_link?.url"
                                        :href="article.primary_link.url"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-sm text-[#c3e062] break-all hover:underline"
                                        @click.stop
                                    >
                                        {{ article.primary_link.url }}
                                    </a>
                                    <span v-else class="text-sm text-[#8b8477]">
                                        No source URL
                                    </span>
                                    <span
                                        v-if="article.primary_link?.ignored"
                                        class="status-chip status-muted"
                                    >
                                        ignored
                                    </span>
                                </div>

                                <div
                                    v-if="article.primary_link?.scan && article.primary_link.scan.state !== 'ok' && !article.primary_link.ignored"
                                    class="mt-3 inline-flex max-w-full items-center gap-2 rounded-md border px-3 py-2 text-sm"
                                    :class="primaryIssueBannerClass(article.primary_link.scan.state)"
                                >
                                    <strong class="uppercase tracking-[0.14em] text-[11px]">
                                        {{ article.primary_link.scan.label }}
                                    </strong>
                                    <span class="truncate">
                                        {{ article.primary_link.scan.reason }}
                                    </span>
                                </div>

                                <div
                                    v-if="brokenThumbnailLink(article)"
                                    class="mt-3 rounded-md border border-amber-500/50 bg-amber-950/20 px-3 py-3"
                                >
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="status-chip status-blocked">thumbnail issue</span>
                                        <span class="text-sm text-amber-100">
                                            The saved thumbnail looks broken. You can fetch a fresh candidate from the source URL.
                                        </span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            @click.stop="fetchThumbnailSuggestion(article)"
                                            :disabled="isFetchingThumbnailSuggestion(article.id)"
                                            class="admin-btn-secondary text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                        >
                                            {{
                                                isFetchingThumbnailSuggestion(article.id)
                                                    ? "Fetching..."
                                                    : "Fetch new thumbnail"
                                            }}
                                        </button>
                                    </div>

                                    <div
                                        v-if="thumbnailSuggestionError(article.id)"
                                        class="mt-3 text-sm text-red-200"
                                    >
                                        {{ thumbnailSuggestionError(article.id) }}
                                    </div>

                                    <div
                                        v-if="thumbnailSuggestion(article.id)?.suggested_thumbnail_url"
                                        class="mt-4 grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)]"
                                    >
                                        <img
                                            :src="thumbnailSuggestion(article.id).suggested_thumbnail_url"
                                            alt="Suggested thumbnail"
                                            class="w-full rounded-md border border-[#5b554f] bg-black/20 object-cover aspect-video"
                                        />
                                        <div class="min-w-0">
                                            <div class="text-xs uppercase tracking-[0.18em] text-[#8b8477]">
                                                Suggested thumbnail
                                            </div>
                                            <div class="mt-2 text-sm text-[#c3e062] break-all">
                                                {{ thumbnailSuggestion(article.id).suggested_thumbnail_url }}
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    @click.stop="applyThumbnailSuggestion(article)"
                                                    :disabled="isApplyingThumbnailSuggestion(article.id)"
                                                    class="admin-btn-primary text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                                >
                                                    {{
                                                        isApplyingThumbnailSuggestion(article.id)
                                                            ? "Applying..."
                                                            : "Approve thumbnail"
                                                    }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 shrink-0">
                                <button
                                    v-if="article.primary_link?.url && canToggleIgnore(article.primary_link)"
                                    type="button"
                                    @click.stop="toggleIgnored(article, article.primary_link)"
                                    :disabled="isTogglingIgnore(article.id, article.primary_link.url)"
                                    class="admin-btn-muted text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    {{
                                        isTogglingIgnore(article.id, article.primary_link.url)
                                            ? "Saving..."
                                            : article.primary_link.ignored
                                              ? "Unignore"
                                              : "Ignore"
                                    }}
                                </button>
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
                            <button
                                v-if="hasAnyIssue(article)"
                                type="button"
                                @click="deleteArticle(article)"
                                :disabled="isDeletingArticle(article.id)"
                                class="admin-btn-danger text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                {{
                                    isDeletingArticle(article.id)
                                        ? "Deleting article..."
                                        : "Delete article"
                                }}
                            </button>
                        </div>

                        <div class="text-xs uppercase tracking-[0.2em] text-[#8b8477] mb-2">
                            Body
                        </div>
                        <pre class="whitespace-pre-wrap break-words text-sm leading-6 text-[#ebe5cb] font-mono">{{
                            article.body || "No body content."
                        }}</pre>
                    </div>

                    <div
                        v-if="isExpanded(article.id) && secondaryLinks(article).length"
                        class="px-5 py-4"
                    >
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <div class="text-xs uppercase tracking-[0.2em] text-[#8b8477]">
                                Other links
                            </div>
                            <div class="text-xs text-[#8b8477]">
                                Found on URL is primary. These are just extra links found in the article.
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="link in secondaryLinks(article)"
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
                                            <span
                                                v-if="link.ignored"
                                                class="status-chip status-muted"
                                            >
                                                ignored
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
                                            v-if="canToggleIgnore(link)"
                                            type="button"
                                            @click="toggleIgnored(article, link)"
                                            :disabled="isTogglingIgnore(article.id, link.url)"
                                            class="admin-btn-muted text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                        >
                                            {{
                                                isTogglingIgnore(article.id, link.url)
                                                    ? "Saving..."
                                                    : link.ignored
                                                      ? "Unignore"
                                                      : "Ignore"
                                            }}
                                        </button>
                                        <button
                                            v-if="canDeleteBodyBlock(link)"
                                            type="button"
                                            @click="fetchBlockDeletePreview(article, link)"
                                            :disabled="isFetchingBlockPreview(article.id, link.url) || isDeletingBlock(article.id, link.url)"
                                            class="admin-btn-danger text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                        >
                                            {{
                                                isFetchingBlockPreview(article.id, link.url)
                                                    ? "Preparing..."
                                                    : "Delete block"
                                            }}
                                        </button>
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
                                        <button
                                            v-if="canOpenProviderSearch(link)"
                                            @click="openProviderSearch(article, link)"
                                            class="admin-btn-secondary text-sm"
                                        >
                                            {{ providerSearchLabel(link) }}
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
                                    v-if="blockDeleteError(article.id, link.url)"
                                    class="mt-3 rounded-md border border-red-500/70 bg-red-950/40 px-3 py-2 text-sm text-red-100"
                                >
                                    {{ blockDeleteError(article.id, link.url) }}
                                </div>

                                <div
                                    v-if="blockDeleteCandidates(article.id, link.url).length"
                                    class="mt-4 rounded-lg border border-red-500/40 bg-[#241716] px-4 py-3"
                                >
                                    <div class="text-xs uppercase tracking-[0.2em] text-[#f0b4b4] mb-3">
                                        Delete preview
                                    </div>

                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <button
                                            v-for="candidate in blockDeleteCandidates(article.id, link.url)"
                                            :key="candidate.key"
                                            type="button"
                                            @click="setSelectedBlockDeleteCandidate(article.id, link.url, candidate.key)"
                                            class="px-3 py-1.5 rounded border text-xs"
                                            :class="
                                                selectedBlockDeleteKey(article.id, link.url) === candidate.key
                                                    ? 'border-red-400 bg-red-900/40 text-red-100'
                                                    : 'border-[#5b554f] bg-[#2d2b24] text-[#d4cdc0]'
                                            "
                                        >
                                            {{ candidate.label }}
                                        </button>
                                    </div>

                                    <pre class="whitespace-pre-wrap break-words text-xs leading-6 text-[#f8d7d7] font-mono">{{
                                        selectedBlockDeleteCandidate(article.id, link.url)?.preview_html
                                    }}</pre>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            @click="confirmDeleteBodyBlock(article, link)"
                                            :disabled="isDeletingBlock(article.id, link.url)"
                                            class="admin-btn-danger text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                                        >
                                            {{
                                                isDeletingBlock(article.id, link.url)
                                                    ? "Deleting..."
                                                    : "Confirm delete and update article"
                                            }}
                                        </button>
                                    </div>
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const props = defineProps({
    articles: { type: Array, default: () => [] },
});

const search = ref("");
const issueFilter = ref("all");
const scanError = ref("");
const mainHeader = ref(null);
const issueNavSentinel = ref(null);
const showStickyIssueNav = ref(false);
const scanState = ref({
    running: false,
    finished: false,
    summary: null,
});

const expanded = ref({});
const replacementStates = ref({});
const ignoreStates = ref({});
const thumbnailSuggestionStates = ref({});
const blockDeleteStates = ref({});
const articleDeleteStates = ref({});
const articles = ref(props.articles.map(enhanceArticle));
const currentIssueJumpIndex = ref(-1);
let issueNavObserver = null;

function enhanceArticle(article) {
    return {
        ...article,
        links: (article.links ?? []).map((link) => ({
            ...link,
            scan: link.scan ?? null,
        })),
        primary_link: article.primary_link
            ? {
                  ...article.primary_link,
                  scan: article.primary_link.scan ?? null,
              }
            : null,
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

const scanButtonLabel = computed(() => {
    if (scanState.value.running) {
        return "Scanning live...";
    }

    return scanState.value.finished ? "Rescan live" : "Start live scan";
});

function beginGlobalScan() {
    scanState.value.running = true;
}

function endGlobalScan() {
    scanState.value.running = false;
}

function isDeletingArticle(articleId) {
    return !!articleDeleteStates.value[articleId];
}

function mergeArticles(nextArticles) {
    const expansionState = { ...expanded.value };

    articles.value = nextArticles.map((article) => enhanceArticle(article));
    expanded.value = expansionState;
    currentIssueJumpIndex.value = -1;
}

async function scanAll(force = false) {
    beginGlobalScan();
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

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Scan returned a non-JSON response.",
            );
        }

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Scan failed.");
        }

        mergeArticles(data.articles ?? []);
        scanState.value.summary = data.summary ?? null;
        scanState.value.finished = true;
        await nextTick();
        window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (error) {
        scanError.value = error.message || "Link scan failed.";
    } finally {
        endGlobalScan();
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

function isTogglingIgnore(articleId, url) {
    return !!ignoreStates.value[linkKey(articleId, url)];
}

function thumbnailSuggestionState(articleId) {
    return (
        thumbnailSuggestionStates.value[articleId] ?? {
            loading: false,
            applying: false,
            error: "",
            suggested_thumbnail_url: "",
            current_thumbnail_url: "",
        }
    );
}

function isFetchingThumbnailSuggestion(articleId) {
    return thumbnailSuggestionState(articleId).loading;
}

function isApplyingThumbnailSuggestion(articleId) {
    return thumbnailSuggestionState(articleId).applying;
}

function thumbnailSuggestionError(articleId) {
    return thumbnailSuggestionState(articleId).error;
}

function thumbnailSuggestion(articleId) {
    return thumbnailSuggestionState(articleId);
}

function blockDeleteState(articleId, url) {
    return (
        blockDeleteStates.value[linkKey(articleId, url)] ?? {
            loading: false,
            deleting: false,
            error: "",
            selected_key: "",
            candidates: [],
        }
    );
}

function blockDeleteCandidates(articleId, url) {
    return blockDeleteState(articleId, url).candidates ?? [];
}

function selectedBlockDeleteKey(articleId, url) {
    return blockDeleteState(articleId, url).selected_key ?? "";
}

function selectedBlockDeleteCandidate(articleId, url) {
    const state = blockDeleteState(articleId, url);
    return (state.candidates ?? []).find((candidate) => candidate.key === state.selected_key) ?? null;
}

function blockDeleteError(articleId, url) {
    return blockDeleteState(articleId, url).error ?? "";
}

function isFetchingBlockPreview(articleId, url) {
    return blockDeleteState(articleId, url).loading;
}

function isDeletingBlock(articleId, url) {
    return blockDeleteState(articleId, url).deleting;
}

function setSelectedBlockDeleteCandidate(articleId, url, candidateKey) {
    const key = linkKey(articleId, url);
    blockDeleteStates.value = {
        ...blockDeleteStates.value,
        [key]: {
            ...blockDeleteState(articleId, url),
            selected_key: candidateKey,
        },
    };
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
    return !link.ignored && ["broken", "blocked"].includes(link.scan?.state ?? "");
}

function canToggleIgnore(link) {
    if (!link) return false;
    if (link.ignored) return true;

    return ["broken", "blocked"].includes(link.scan?.state ?? "");
}

function canDeleteBodyBlock(link) {
    return !link.ignored && link.scan?.state === "broken" && link.sources?.includes("body");
}

function secondaryLinks(article) {
    return (article.links ?? []).filter(
        (link) => !link.sources?.includes("source_url"),
    );
}

function brokenThumbnailLink(article) {
    return secondaryLinks(article).find(
        (link) =>
            link.sources?.includes("thumbnail_url") &&
            !link.ignored &&
            link.scan?.state === "broken",
    );
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

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Replacement search returned a non-JSON response.",
            );
        }

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

async function toggleIgnored(article, link) {
    const key = linkKey(article.id, link.url);

    ignoreStates.value = {
        ...ignoreStates.value,
        [key]: true,
    };

    try {
        const response = await fetch("/chimbi/tshoot/ignore", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({
                article_id: article.id,
                url: link.url,
                ignored: !link.ignored,
            }),
        });

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Ignore request returned a non-JSON response.",
            );
        }

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Ignore update failed.");
        }

        link.ignored = !!data.ignored;

        if (article.primary_link?.url === link.url) {
            article.primary_link.ignored = !!data.ignored;
        }

        recalculateArticleIssues(article);
    } catch (error) {
        scanError.value = error.message || "Ignore update failed.";
    } finally {
        ignoreStates.value = {
            ...ignoreStates.value,
            [key]: false,
        };
    }
}

function recalculateArticleIssues(article) {
    const activeLinks = (article.links ?? []).filter(
        (link) => !link.ignored && !link.sources?.includes("thumbnail_url"),
    );
    article.issue_count = activeLinks.filter((link) => link.scan?.state === "broken").length;
    article.blocked_count = activeLinks.filter((link) => link.scan?.state === "blocked").length;
    article.has_issues = article.issue_count > 0;
}

async function fetchThumbnailSuggestion(article) {
    thumbnailSuggestionStates.value = {
        ...thumbnailSuggestionStates.value,
        [article.id]: {
            ...thumbnailSuggestionState(article.id),
            loading: true,
            error: "",
        },
    };

    try {
        const response = await fetch("/chimbi/tshoot/thumbnail-suggestion", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({
                article_id: article.id,
            }),
        });

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Thumbnail suggestion returned a non-JSON response.",
            );
        }

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Thumbnail suggestion failed.");
        }

        thumbnailSuggestionStates.value = {
            ...thumbnailSuggestionStates.value,
            [article.id]: {
                loading: false,
                applying: false,
                error: "",
                current_thumbnail_url: data.current_thumbnail_url ?? "",
                suggested_thumbnail_url: data.suggested_thumbnail_url ?? "",
            },
        };
    } catch (error) {
        thumbnailSuggestionStates.value = {
            ...thumbnailSuggestionStates.value,
            [article.id]: {
                ...thumbnailSuggestionState(article.id),
                loading: false,
                error: error.message || "Thumbnail suggestion failed.",
            },
        };
    }
}

async function applyThumbnailSuggestion(article) {
    const suggestion = thumbnailSuggestionState(article.id);

    if (!suggestion.suggested_thumbnail_url) return;

    thumbnailSuggestionStates.value = {
        ...thumbnailSuggestionStates.value,
        [article.id]: {
            ...suggestion,
            applying: true,
            error: "",
        },
    };

    try {
        const response = await fetch("/chimbi/tshoot/apply-thumbnail", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({
                article_id: article.id,
                thumbnail_url: suggestion.suggested_thumbnail_url,
            }),
        });

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Apply thumbnail returned a non-JSON response.",
            );
        }

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Thumbnail update failed.");
        }

        thumbnailSuggestionStates.value = {
            ...thumbnailSuggestionStates.value,
            [article.id]: {
                ...suggestion,
                applying: false,
                current_thumbnail_url: data.thumbnail_url ?? suggestion.suggested_thumbnail_url,
            },
        };

        await rescanArticle(article.id, true);
    } catch (error) {
        thumbnailSuggestionStates.value = {
            ...thumbnailSuggestionStates.value,
            [article.id]: {
                ...thumbnailSuggestionState(article.id),
                applying: false,
                error: error.message || "Thumbnail update failed.",
            },
        };
    }
}

async function rescanArticle(articleId, force = false) {
    beginGlobalScan();

    try {
        const response = await fetch("/chimbi/tshoot/scan", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({
                article_ids: [articleId],
                force,
            }),
        });

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Article rescan returned a non-JSON response.",
            );
        }

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Article rescan failed.");
        }

        const refreshed = (data.articles ?? [])[0];
        if (!refreshed) return;

        const nextArticle = enhanceArticle(refreshed);
        const index = articles.value.findIndex((item) => item.id === articleId);

        if (index === -1) return;

        const next = [...articles.value];
        next[index] = nextArticle;
        articles.value = next;
    } finally {
        endGlobalScan();
    }
}

async function fetchBlockDeletePreview(article, link) {
    const key = linkKey(article.id, link.url);

    blockDeleteStates.value = {
        ...blockDeleteStates.value,
        [key]: {
            loading: true,
            deleting: false,
            error: "",
            selected_key: "",
            candidates: [],
        },
    };

    try {
        const response = await fetch("/chimbi/tshoot/delete-block-preview", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({
                article_id: article.id,
                url: link.url,
            }),
        });

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Delete preview returned a non-JSON response.",
            );
        }

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Delete preview failed.");
        }

        blockDeleteStates.value = {
            ...blockDeleteStates.value,
            [key]: {
                loading: false,
                deleting: false,
                error: "",
                selected_key: data.candidates?.[0]?.key ?? "",
                candidates: data.candidates ?? [],
            },
        };
    } catch (error) {
        blockDeleteStates.value = {
            ...blockDeleteStates.value,
            [key]: {
                loading: false,
                deleting: false,
                error: error.message || "Delete preview failed.",
                selected_key: "",
                candidates: [],
            },
        };
    }
}

async function confirmDeleteBodyBlock(article, link) {
    const key = linkKey(article.id, link.url);
    const selected = selectedBlockDeleteCandidate(article.id, link.url);

    if (!selected) return;

    blockDeleteStates.value = {
        ...blockDeleteStates.value,
        [key]: {
            ...blockDeleteState(article.id, link.url),
            deleting: true,
            error: "",
        },
    };

    try {
        const response = await fetch("/chimbi/tshoot/delete-block", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({
                article_id: article.id,
                url: link.url,
                candidate_key: selected.key,
            }),
        });

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Delete block returned a non-JSON response.",
            );
        }

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Delete block failed.");
        }

        blockDeleteStates.value = {
            ...blockDeleteStates.value,
            [key]: {
                loading: false,
                deleting: false,
                error: "",
                selected_key: "",
                candidates: [],
            },
        };

        await rescanArticle(article.id, true);
    } catch (error) {
        blockDeleteStates.value = {
            ...blockDeleteStates.value,
            [key]: {
                ...blockDeleteState(article.id, link.url),
                deleting: false,
                error: error.message || "Delete block failed.",
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

function linkHost(link) {
    try {
        return new URL(link.url).hostname.replace(/^www\./, "").toLowerCase();
    } catch {
        return "";
    }
}

function providerSearchLabel(link) {
    const host = linkHost(link);

    if (host.includes("youtube.com") || host.includes("youtu.be") || host.includes("youtube-nocookie.com")) {
        return "Search YouTube";
    }

    if (host.includes("vimeo.com")) {
        return "Search Vimeo";
    }

    return "Search Google";
}

function providerSearchUrl(article, link) {
    const title = (article.title ?? "").trim();
    const host = linkHost(link);

    if (host.includes("youtube.com") || host.includes("youtu.be") || host.includes("youtube-nocookie.com")) {
        return `https://www.youtube.com/results?search_query=${encodeURIComponent(title)}`;
    }

    if (host.includes("vimeo.com")) {
        return `https://vimeo.com/search?q=${encodeURIComponent(title)}`;
    }

    return `https://www.google.com/search?q=${encodeURIComponent(title)}`;
}

function canOpenProviderSearch(link) {
    return !link.ignored && ["broken", "blocked"].includes(link.scan?.state ?? "");
}

function openProviderSearch(article, link) {
    const url = providerSearchUrl(article, link);
    if (!url) return;

    window.open(url, "_blank", "noopener");
}

async function deleteArticle(article) {
    if (!hasAnyIssue(article)) return;

    const confirmed = window.confirm(
        `Delete article #${article.id} "${article.title || "Untitled article"}"?`,
    );

    if (!confirmed) return;

    articleDeleteStates.value = {
        ...articleDeleteStates.value,
        [article.id]: true,
    };

    try {
        const response = await fetch(`/chimbi/tshoot/article/${article.id}`, {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
        });

        const raw = await response.text();
        let data = null;

        try {
            data = raw ? JSON.parse(raw) : {};
        } catch {
            throw new Error(
                raw?.trim()?.slice(0, 220) || "Delete article returned a non-JSON response.",
            );
        }

        if (!response.ok) {
            throw new Error(data.message ?? data.error ?? "Delete article failed.");
        }

        articles.value = articles.value.filter((item) => item.id !== article.id);
        scanState.value.summary = null;
        delete expanded.value[article.id];
    } catch (error) {
        scanError.value = error.message || "Delete article failed.";
    } finally {
        articleDeleteStates.value = {
            ...articleDeleteStates.value,
            [article.id]: false,
        };
    }
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

function jumpToPreviousIssue() {
    if (!issueArticleIds.value.length) return;

    const previousIndex =
        currentIssueJumpIndex.value <= 0
            ? issueArticleIds.value.length - 1
            : currentIssueJumpIndex.value - 1;

    jumpToIssue(issueArticleIds.value[previousIndex]);
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

function primaryIssueBannerClass(state) {
    if (state === "blocked") {
        return "border-amber-500/60 bg-amber-950/30 text-amber-100";
    }

    return "border-red-500/60 bg-red-950/30 text-red-100";
}

function doLogout() {
    router.post("/chimbi/logout");
}

onMounted(() => {
    if (issueNavSentinel.value && typeof IntersectionObserver !== "undefined") {
        issueNavObserver = new IntersectionObserver(
            ([entry]) => {
                showStickyIssueNav.value = !entry.isIntersecting;
            },
            {
                root: null,
                threshold: 0,
                rootMargin: "-1px 0px 0px 0px",
            },
        );

        issueNavObserver.observe(issueNavSentinel.value);
    } else {
        showStickyIssueNav.value = false;
    }

    scanAll(false);
});

onBeforeUnmount(() => {
    issueNavObserver?.disconnect();
    issueNavObserver = null;
});
</script>

<style scoped>
.admin-btn-primary,
.admin-btn-secondary,
.admin-btn-muted,
.admin-btn-danger {
    cursor: pointer;
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

.admin-btn-danger {
    background: #7f1d1d;
    color: #fee2e2;
    border-color: #ef4444;
}

.admin-btn-danger:hover {
    background: #991b1b;
    color: #fff1f2;
}

.admin-scan-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    position: relative;
    overflow: hidden;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.04),
        0 8px 18px rgba(0, 0, 0, 0.16);
    transition:
        transform 0.15s ease,
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        background 0.15s ease;
}

.admin-scan-btn:hover {
    transform: translateY(-1px);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.06),
        0 10px 24px rgba(0, 0, 0, 0.2);
}

.admin-scan-btn:disabled {
    opacity: 1;
}

.admin-scan-btn-running {
    background:
        linear-gradient(135deg, rgba(212, 239, 115, 0.96), rgba(195, 224, 98, 0.96)),
        #c3e062;
    color: #1f1d18;
    border-color: rgba(235, 245, 182, 0.95);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.08),
        0 0 0 1px rgba(195, 224, 98, 0.18),
        0 10px 24px rgba(195, 224, 98, 0.22);
    text-shadow: none;
}

.admin-stat {
    cursor: pointer;
    border: 1px solid #4f4943;
    border-radius: 0.9rem;
    padding: 1.1rem 1.15rem;
    background:
        radial-gradient(circle at top right, rgba(195, 224, 98, 0.12), transparent 35%),
        #383838;
    transition:
        border-color 0.15s ease,
        transform 0.15s ease,
        box-shadow 0.15s ease;
}

.admin-stat:hover {
    border-color: #8b8477;
}

.admin-stat-active {
    border-color: #c3e062;
    box-shadow: 0 0 0 1px rgba(195, 224, 98, 0.25);
    transform: translateY(-1px);
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

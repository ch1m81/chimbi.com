<template>
    <div class="min-h-screen bg-[#2a2820] text-[#ebe5cb] font-['Ubuntu']">
        <!-- Header bar -->
        <div
            class="bg-[#383838] border-b border-[#4f4943] px-6 py-6 flex items-center justify-between sticky top-0 z-40"
            :class="
                deletedNotice
                    ? 'border-red-500 shadow-[0_0_0_1px_rgba(239,68,68,0.4)]'
                    : 'border-[#4f4943]'
            "
        >
            <div class="flex items-center gap-4 min-w-0 w-1/3">
                <Link
                    href="/"
                    class="text-[#ebe5cb]! hover:text-[#c3e062]! hover:no-underline! text-2xl shrink-0"
                    >🏠</Link
                >
                <span class="text-[#6b6459]">/</span>
                <button
                    @click="goBack"
                    class="text-[#ebe5cb] hover:text-[#c3e062] text-xl shrink-0"
                >
                    ← back
                </button>
                <span class="text-[#6b6459]">/</span>
                <span class="text-2xl text-shadow-sm text-mauve-400 truncate">{{
                    mode === "create"
                        ? "💡 New Article"
                        : "✨ Edit: " + form.title
                }}</span>
                <a
                    v-if="mode === 'edit' && article"
                    :href="publicArticleUrl"
                    target="_blank"
                    rel="noopener"
                    class="text-xl text-[#c3e062] hover:underline shrink-0 bg-[#4f4943] px-4 py-2 rounded-sm border"
                    title="View article"
                    >🗨️ view article</a
                >
            </div>

            <div class="flex items-center gap-3 w-1/5 justify-end">
                <div v-if="isDirty && !saved" class="flex items-center">
                    <span
                        class="animate-ping rounded-full bg-yellow-400 opacity-75 w-3 h-3"
                    ></span>
                    <span class="text-yellow-400 text-xl hidden sm:block pl-2">
                        unsaved
                    </span>
                </div>
                <span v-if="saved" class="text-[#c3e062] text-2xl"
                    >✓ Saved</span
                >

                <template v-if="mode === 'edit'">
                    <Link
                        v-if="prevArticle"
                        :href="adminArticleUrl(prevArticle.id)"
                        :title="prevArticle.title"
                        class="px-6 py-1 text-lg bg-[#4f4943] hover:bg-[#67625b] rounded hover:no-underline text-[#ebe5cb]!"
                        >← prev</Link
                    >
                    <Link
                        v-if="nextArticle"
                        :href="adminArticleUrl(nextArticle.id)"
                        :title="nextArticle.title"
                        class="px-6 py-1 text-lg bg-[#4f4943] hover:bg-[#67625b] rounded hover:no-underline text-[#ebe5cb]!"
                        >next →</Link
                    >
                </template>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <Link
                    href="/chimbi/tshoot"
                    class="px-3 py-1 text-sm bg-[#4f4943] hover:bg-[#67625b] rounded text-[#ebe5cb]! hover:no-underline!"
                >
                    Tshoot
                </Link>
                <button
                    v-if="mode === 'edit'"
                    @click="confirmDelete"
                    class="border-dotted px-3 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded"
                >
                    delete 💣
                </button>
                <button
                    @click="doLogout"
                    class="px-3 py-1 text-sm bg-[#4f4943] hover:bg-[#67625b] rounded"
                >
                    Logout
                </button>
            </div>
        </div>

        <div
            v-if="deletedNotice"
            class="max-w-4xl mx-auto mt-4 px-4 py-3 border border-red-500 bg-red-950/60 text-red-100 rounded-sm text-base"
        >
            Deleted:
            <strong class="text-white">{{ deletedNotice }}</strong>
            This article no longer exists. You are now editing the next available post.
        </div>

        <div
            class="max-w-4xl mx-auto py-8 grid grid-cols-1 lg:grid-cols-3 gap-6"
        >
            <!-- ── Left: main fields ──────────────────────────────────────── -->
            <div class="lg:col-span-2 flex flex-col gap-5 text-xl">
                <!-- Source URL with fetch button -->
                <div class="field-group">
                    <label class="field-label">Found on URL</label>
                    <div class="flex gap-2">
                        <input
                            v-model="form.source_url"
                            type="url"
                            placeholder="https://..."
                            class="grow w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062]"
                            @blur="autoFetchIfEmpty"
                        />
                        <button
                            @click="fetchMeta"
                            :disabled="fetching || !form.source_url"
                            class="px-4 py-2 bg-[#c3e062] text-[#2a2820] font-bold rounded text-sm hover:bg-[#d4ef73] disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
                        >
                            {{ fetching ? "..." : "Fetch" }}
                        </button>
                    </div>
                    <p v-if="fetchError" class="text-red-400 text-xs mt-1">
                        {{ fetchError }}
                    </p>
                </div>

                <!-- Title -->
                <div class="field-group">
                    <label class="field-label">Title</label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Article title"
                        class="w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062]"
                        :class="{ 'border-red-500': errors.title }"
                        @input="autoSlug"
                    />
                    <p v-if="errors.title" class="field-error">
                        {{ errors.title }}
                    </p>
                </div>

                <!-- Slug -->
                <div class="field-group">
                    <label class="field-label">Slug</label>
                    <input
                        v-model="form.slug"
                        type="text"
                        class="font-mono w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062]"
                        :class="{ 'border-red-500': errors.slug }"
                        @input="markSlugManual"
                    />
                    <p v-if="errors.slug" class="field-error">
                        {{ errors.slug }}
                    </p>
                </div>

                <!-- YouTube code -->
                <div class="field-group">
                    <label class="field-label">YouTube Code</label>
                    <input
                        v-model="form.youtube_code"
                        type="text"
                        placeholder="e.g. dQw4w9WgXcQ"
                        class="font-mono w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062]"
                    />
                    <div
                        v-if="form.youtube_code"
                        class="mt-2 aspect-video bg-black rounded overflow-hidden"
                    >
                        <iframe
                            :src="`https://www.youtube.com/embed/${form.youtube_code}`"
                            class="w-full h-full"
                            frameborder="0"
                            allowfullscreen
                        ></iframe>
                    </div>
                </div>

                <!-- Body (Markdown) -->
                <div class="field-group">
                    <div class="flex items-center justify-between mb-1">
                        <label class="field-label mb-0">Body (Markdown)</label>
                        <button
                            @click="openPreview"
                            class="text-base text-[#c3e062] hover:underline cursor-pointer"
                        >
                            👁 Preview
                        </button>
                    </div>
                    <textarea
                        v-model="form.body"
                        rows="12"
                        placeholder="Markdown content..."
                        class="w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062] font-mono resize-y"
                    ></textarea>
                    <div class="mt-3 p-4 bg-[#383838] border border-[#4f4943] rounded-sm">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <label class="field-label mb-0">List Preview Rules</label>
                            <button
                                type="button"
                                @click="insertMoreMarker"
                                class="px-3 py-1 text-sm bg-[#4f4943] hover:bg-[#67625b] rounded"
                            >
                                Insert `<!--more-->` marker
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-[#c3e062] mb-1">
                                    Custom excerpt
                                </label>
                                <textarea
                                    v-model="form.excerpt"
                                    rows="3"
                                    placeholder="Optional. If filled, the homepage/list uses this text instead of auto trimming."
                                    class="w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062] resize-y"
                                ></textarea>
                            </div>

                            <div class="flex items-center gap-3">
                                <label class="block text-sm text-[#c3e062]">
                                    Auto trim to
                                </label>
                                <input
                                    v-model.number="form.trim_sentences"
                                    type="number"
                                    min="1"
                                    max="10"
                                    class="w-20 py-1 px-2 bg-olive-700 border border-olive-400 rounded-sm text-[#ebe5cb]"
                                />
                                <span class="text-sm text-[#ebe5cb]">sentences</span>
                            </div>

                            <div class="text-sm text-[#6b6459] leading-relaxed">
                                Priority: custom excerpt first, then `<!--more-->` marker, then sentence trim. Existing character trim is kept only as a legacy fallback.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Right: meta + tags + publish ─────────────────────────── -->
            <div class="flex flex-col gap-5">
                <!-- Thumbnail -->
                <div class="field-group text-xl">
                    <label class="field-label">Thumbnail</label>

                    <!-- Preview -->
                    <div v-if="thumbnailPreview" class="mb-2 relative">
                        <img
                            :src="thumbnailPreview"
                            class="w-full rounded border border-[#4f4943] object-cover max-h-80 aspect-video"
                        />
                        <button
                            @click="clearThumbnail"
                            class="absolute top-1 right-1 w-8 h-8 p-1 bg-black/50 rounded-full text-white text-xs hover:bg-black hover:cursor-pointer hover:text-red-500"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Upload -->
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="onFileChange"
                    />
                    <button
                        @click="fileInput.click()"
                        class="w-full py-2 border text-base border-dashed border-[#4f4943] hover:border-[#c3e062] hover:text-[#c3e062] rounded transition-colors"
                    >
                        Upload image
                    </button>

                    <!-- OR URL -->
                    <div class="mt-2">
                        <input
                            v-model="form.thumbnail_url"
                            type="url"
                            placeholder="or paste image URL"
                            class="w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062]"
                            @input="onThumbnailUrl"
                        />
                    </div>
                </div>

                <!-- Tags -->
                <div class="field-group">
                    <div class="flex items-center justify-between mb-1 text-xl">
                        <label class="field-label mb-0">Tags</label>
                        <button
                            @click="suggestTags"
                            :disabled="
                                suggesting || (!form.title && !form.body)
                            "
                            class="text-lg px-2 py-1 bg-[#4f4943] hover:bg-[#c3e062] hover:text-[#2a2820] rounded transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            {{ suggesting ? "..." : "✨ Suggest" }}
                        </button>
                    </div>
                    <p v-if="suggestError" class="text-red-400 text-xs mb-1">
                        {{ suggestError }}
                    </p>

                    <!-- AI suggestions -->
                    <div
                        v-if="suggestedTags.length"
                        class="flex flex-wrap gap-1 mb-2 p-2 bg-[#383838] rounded"
                    >
                        <span class="text-xs text-[#6b6459] w-full mb-1"
                            >Suggestions — click to add:</span
                        >
                        <button
                            v-for="tag in suggestedTags"
                            :key="tag.slug"
                            @click="addSuggestedTag(tag)"
                            class="text-xs px-2 py-0.5 bg-[#4f4943] hover:bg-[#c3e062] hover:text-[#2a2820] rounded transition-colors"
                            :class="{ 'opacity-40': isTagSelected(tag.id) }"
                        >
                            {{ tag.name }}
                        </button>
                    </div>

                    <!-- Selected tags -->
                    <div
                        class="flex flex-wrap gap-1 min-h-8 p-2 bg-[#1e1c18] rounded border border-[#4f4943] mb-2"
                    >
                        <span
                            v-for="tagId in form.tags"
                            :key="tagId"
                            class="flex items-center gap-1 text-base px-2 py-0.5 bg-[#4f4943] rounded"
                        >
                            {{ tagName(tagId) }}
                            <button
                                @click="removeTag(tagId)"
                                class="text-[#6b6459] hover:text-red-400 cursor-pointer ml-2"
                            >
                                ✕
                            </button>
                        </span>
                        <span
                            v-if="!form.tags.length"
                            class="text-base text-[#6b6459]"
                            >No tags selected</span
                        >
                    </div>

                    <!-- Search/add tags -->
                    <input
                        v-model="tagSearch"
                        type="text"
                        placeholder="Search or create tag..."
                        class="w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062]"
                    />
                    <div
                        v-if="tagSearch"
                        class="mt-2 max-h-40 overflow-y-auto bg-[#1e1c18] border border-[#4f4943] rounded"
                    >
                        <button
                            v-for="tag in filteredTags"
                            :key="tag.id"
                            @click="toggleTag(tag.id)"
                            class="w-full text-left px-3 py-1.5 text-lg hover:bg-[#4f4943] flex items-center justify-between"
                            :class="{
                                'text-[#c3e062] text-xl': isTagSelected(tag.id),
                            }"
                        >
                            {{ tag.name }}
                            <span
                                v-if="isTagSelected(tag.id)"
                                class="text-xl font-extrabold"
                                >✓</span
                            >
                        </button>
                        <button
                            v-if="tagSearch && !exactTagMatch"
                            @click="createAndAddTag"
                            class="w-full text-left px-3 py-2 relative text-xl text-[#c3e062] hover:bg-[#4f4943] border-t border-[#4f4943] cursor-pointer"
                        >
                            + Create "{{ tagSearch }}"
                        </button>
                    </div>
                </div>

                <!-- Publish settings -->
                <div class="field-group">
                    <label class="field-label text-xl">Publish</label>
                    <label class="flex items-center gap-2 cursor-pointer mb-3">
                        <input
                            type="checkbox"
                            v-model="form.published"
                            class="accent-[#c3e062] w-5 h-5"
                        />
                        <span class="text-base">Published</span>
                    </label>
                    <label class="text-xl">Published date</label>
                    <input
                        v-model="form.published_at"
                        type="date"
                        class="w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062]"
                    />
                </div>

                <!-- Love (edit only) -->
                <div v-if="mode === 'edit'" class="field-group">
                    <label class="text-xl">Love score</label>
                    <input
                        v-model.number="form.love"
                        type="number"
                        min="0"
                        class="w-full bg-olive-700 border-olive-400 border-s-4 rounded-sm px-3 py-2 text-base outline-none transition-colors hover:border-[#c3e062] focus:border-[#c3e062]"
                    />
                </div>

                <!-- Save button -->
                <button
                    @click="save"
                    :disabled="saving"
                    class="w-full py-3 bg-[#c3e062] text-[#2a2820] font-bold rounded text-lg hover:bg-[#d4ef73] disabled:opacity-60 disabled:cursor-not-allowed tracking-wide hover:cursor-pointer"
                >
                    {{
                        saving
                            ? "Saving..."
                            : mode === "create"
                              ? "Publish Article"
                              : "Save Changes"
                    }}
                </button>
            </div>
        </div>

        <!-- ── Preview Modal ───────────────────────────────────────────────── -->
        <div
            v-if="showPreview"
            class="fixed inset-0 z-50 bg-black/80 flex items-start justify-center p-4 overflow-y-auto"
            @click.self="showPreview = false"
        >
            <div
                class="bg-[#2a2820] border border-[#4f4943] rounded-lg max-w-5xl w-full mt-8 mb-8"
            >
                <div
                    class="flex items-center justify-between px-6 py-3 border-b border-[#4f4943]"
                >
                    <div class="flex gap-3 text-sm">
                        <button
                            @click="previewMode = 'full'"
                            class="px-3 py-1 rounded transition-colors"
                            :class="
                                previewMode === 'full'
                                    ? 'bg-[#c3e062] text-[#2a2820] font-bold'
                                    : 'bg-[#4f4943] text-[#ebe5cb]'
                            "
                        >
                            Full body
                        </button>
                        <button
                            @click="previewMode = 'list'"
                            class="px-3 py-1 rounded transition-colors"
                            :class="
                                previewMode === 'list'
                                    ? 'bg-[#c3e062] text-[#2a2820] font-bold'
                                    : 'bg-[#4f4943] text-[#ebe5cb]'
                            "
                        >
                            List preview
                        </button>
                    </div>
                    <button
                        @click="showPreview = false"
                        class="text-[#6b6459] hover:text-[#ebe5cb] text-xl"
                    >
                        ✕
                    </button>
                </div>
                <div class="px-4 sm:px-6 py-4">
                    <div class="w-full pt-2.5">
                        <div class="max-w-[1020px] mx-auto">
                            <div class="mb-6 mt-2">
                                <main class="w-full sm:w-[630px] ml-auto mr-0">
                                    <ArticleCard
                                        :article="previewArticle"
                                        :single-view="previewMode === 'full'"
                                    />
                                </main>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="previewMode === 'list'"
                        class="mt-3 text-[#6b6459] text-xs italic"
                    >
                        {{ listPreviewLabel }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import ArticleCard from "@/Components/ArticleCard.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
    article: { type: Object, default: null },
    allTags: { type: Array, default: () => [] },
    mode: { type: String, default: "create" },
    flash: { type: Object, default: () => ({}) },
    returnTo: { type: String, default: "/" },
    prevArticle: { type: Object, default: null },
    nextArticle: { type: Object, default: null },
});

// ── Form state ────────────────────────────────────────────────────────────
const initialForm = {
    title: props.article?.title ?? "",
    slug: props.article?.slug ?? "",
    body: props.article?.body ?? "",
    body_trim: props.article?.body_trim ?? null,
    excerpt: props.article?.excerpt ?? "",
    trim_sentences: props.article?.trim_sentences ?? null,
    source_url: props.article?.source_url ?? "",
    youtube_code: props.article?.youtube_code ?? "",
    thumbnail_url: props.article?.thumbnail_url ?? "",
    thumbnail: props.article?.thumbnail ?? null,
    love: props.article?.love ?? 0,
    published: props.article?.published ?? false,
    published_at:
        props.article?.published_at ?? new Date().toISOString().slice(0, 10),
    tags: [...(props.article?.tags ?? [])],
};

const form = ref({ ...initialForm });
const savedForm = ref({ ...initialForm });
const sourceBaselineUrl = ref(initialForm.source_url);
const errors = ref({});
const saving = ref(false);
const saved = ref(false);
const showPreview = ref(false);
const previewMode = ref("full");
const copiedPublicUrl = ref(false);
const tagSearch = ref("");
const fileInput = ref(null);
const thumbnailFile = ref(null);
const fetching = ref(false);
const fetchError = ref("");
const suggesting = ref(false);
const suggestError = ref("");
const suggestedTags = ref([]);

// ── Dirty tracking ─────────────────────────────────────────────────────────
const isDirty = computed(
    () => JSON.stringify(form.value) !== JSON.stringify(savedForm.value),
);
const deletedNotice = computed(
    () => props.flash?.deletedArticleTitle?.trim?.() || "",
);
const publicArticleUrl = computed(() =>
    props.mode === "edit" && props.article
        ? `/view/${props.article.id}/${props.article.slug}`
        : "",
);
const headerTitle = computed(() => {
    if (props.mode === "create") return form.value.title?.trim() || "New Article";
    return form.value.title?.trim() || `Edit Article #${props.article?.id ?? ""}`;
});
const headerListMode = computed(() => {
    if (form.value.excerpt?.trim()) return "Excerpt";
    if ((form.value.body ?? "").includes("<!--more-->")) return "Marker";
    if (form.value.trim_sentences) return `${form.value.trim_sentences} sent.`;
    if (form.value.body_trim) return "Legacy";
    return "Full";
});

function handleBeforeUnload(e) {
    if (isDirty.value && !saved.value) {
        e.preventDefault();
        e.returnValue = "";
    }
}
onMounted(() => window.addEventListener("beforeunload", handleBeforeUnload));
onBeforeUnmount(() =>
    window.removeEventListener("beforeunload", handleBeforeUnload),
);

// ── Navigation ─────────────────────────────────────────────────────────────
function goBack() {
    if (isDirty.value && !confirm("You have unsaved changes. Leave anyway?"))
        return;
    window.location.href = props.returnTo || "/";
}

function adminArticleUrl(id) {
    const params = new URLSearchParams();
    if (props.returnTo) params.set("return_to", props.returnTo);
    const query = params.toString();
    return `/chimbi/edit/${id}${query ? `?${query}` : ""}`;
}

async function copyPublicUrl() {
    if (!publicArticleUrl.value) return;
    try {
        await navigator.clipboard.writeText(
            `${window.location.origin}${publicArticleUrl.value}`,
        );
        copiedPublicUrl.value = true;
        setTimeout(() => {
            copiedPublicUrl.value = false;
        }, 1800);
    } catch {
        copiedPublicUrl.value = false;
    }
}

// ── Computed ───────────────────────────────────────────────────────────────
const thumbnailPreview = computed(() => {
    if (thumbnailFile.value) return URL.createObjectURL(thumbnailFile.value);
    if (form.value.thumbnail_url) return form.value.thumbnail_url;
    if (form.value.thumbnail)
        return `/storage/articles/${form.value.thumbnail}`;
    return null;
});

const renderedBody = computed(() =>
    (form.value.body ?? "")
        .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
        .replace(/\*(.*?)\*/g, "<em>$1</em>")
        .replace(/\n/g, "<br>"),
);

function stripHtml(html) {
    const tmp = document.createElement("div");
    tmp.innerHTML = html ?? "";
    return (tmp.textContent || tmp.innerText || "").replace(/\s+/g, " ").trim();
}

function trimTextToSentences(text, count) {
    const normalized = (text ?? "").trim();
    if (!normalized || !count || count < 1) return "";
    const matches = normalized.match(/[^.!?]+[.!?]+(?:\s+|$)|[^.!?]+$/g) ?? [];
    return matches
        .map((sentence) => sentence.trim())
        .filter(Boolean)
        .slice(0, count)
        .join(" ");
}

function firstMediaOnly(html) {
    const match = (html ?? "").match(/<(iframe|video)\b[^>]*>[\s\S]*?<\/\1>/i);
    return match?.[0] ?? "";
}

const listPreviewHtml = computed(() => {
    if (form.value.excerpt?.trim()) {
        return textToHtml(form.value.excerpt);
    }

    if ((form.value.body ?? "").includes("<!--more-->")) {
        return (form.value.body ?? "").split("<!--more-->")[0].trim();
    }

    if (form.value.trim_sentences) {
        return textToHtml(
            trimTextToSentences(stripHtml(form.value.body), form.value.trim_sentences),
        );
    }

    const media = firstMediaOnly(form.value.body);
    if (media) return media;

    if (form.value.body_trim) {
        const text = stripHtml(form.value.body);
        if (!text) return "";
        const trimmed =
            text.length > form.value.body_trim
                ? `${text.slice(0, form.value.body_trim).trim()}...`
                : text;
        return textToHtml(trimmed);
    }

    return renderedBody.value;
});

const listPreviewLabel = computed(() => {
    if (form.value.excerpt?.trim()) return "Using custom excerpt";
    if ((form.value.body ?? "").includes("<!--more-->"))
        return "Using <!--more--> marker";
    if (form.value.trim_sentences)
        return `Using first ${form.value.trim_sentences} sentence${form.value.trim_sentences === 1 ? "" : "s"}`;
    if (firstMediaOnly(form.value.body)) return "Using first embedded media item";
    if (form.value.body_trim) return `Using legacy ${form.value.body_trim}-character trim`;
    return "Using full body";
});

const previewContent = computed(() => {
    if (previewMode.value === "list") return listPreviewHtml.value;
    return renderedBody.value;
});
const previewArticle = computed(() => ({
    id: props.article?.id ?? 0,
    title: form.value.title || "Untitled article",
    slug: form.value.slug || "preview",
    body: previewMode.value === "list" ? listPreviewHtml.value : form.value.body,
    thumbnail: form.value.thumbnail,
    thumbnail_url: form.value.thumbnail_url,
    youtube_code: previewMode.value === "list" ? "" : form.value.youtube_code,
    source_url: form.value.source_url,
    love: form.value.love ?? 0,
    published_at: form.value.published_at || "preview",
    published: form.value.published ?? false,
    tags: form.value.tags.map((tagId) => {
        const tag = props.allTags.find((item) => item.id === tagId);
        return tag
            ? { name: tag.name, slug: tag.slug }
            : { name: String(tagId), slug: String(tagId) };
    }),
}));

function openPreview() {
    previewMode.value = "full";
    showPreview.value = true;
}

function insertMoreMarker() {
    const marker = "\n\n<!--more-->\n\n";
    form.value.body = form.value.body?.includes("<!--more-->")
        ? form.value.body
        : `${form.value.body ?? ""}${marker}`;
}

const filteredTags = computed(() => {
    if (!tagSearch.value) return [];
    const q = tagSearch.value.toLowerCase();
    return props.allTags.filter((t) => t.name.toLowerCase().includes(q));
});

const exactTagMatch = computed(() =>
    props.allTags.some(
        (t) => t.name.toLowerCase() === tagSearch.value.toLowerCase(),
    ),
);

// ── Slug auto-generation ───────────────────────────────────────────────────
function slugify(value) {
    return (value ?? "")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-|-$/g, "");
}

const slugGenerated = ref(
    !props.article ||
        !props.article.slug ||
        props.article.slug === slugify(props.article.title),
);

function autoSlug() {
    if (!slugGenerated.value) return;
    form.value.slug = slugify(form.value.title);
}

function markSlugManual() {
    const currentSlug = (form.value.slug ?? "").trim();
    const generatedSlug = slugify(form.value.title);

    slugGenerated.value = !currentSlug || currentSlug === generatedSlug;
}

function normalizeUrl(url) {
    return (url ?? "").trim();
}

function isSourceReplacement() {
    return (
        !!normalizeUrl(form.value.source_url) &&
        normalizeUrl(form.value.source_url) !== normalizeUrl(sourceBaselineUrl.value)
    );
}

function hasGeneratedMediaBody(body) {
    if (!body?.trim()) return true;
    return /^\s*(?:<(?:p|div)[^>]*>\s*)?(?:<(?:iframe|video)\b[\s\S]*?<\/(?:iframe|video)>)(?:\s*<\/(?:p|div)>)?\s*$/i.test(
        body.trim(),
    );
}

function setFetchedThumbnail(url) {
    if (!url) return;
    thumbnailFile.value = null;
    form.value.thumbnail = null;
    form.value.thumbnail_url = url;
    if (fileInput.value) fileInput.value.value = "";
}

function escapeHtml(text) {
    return (text ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function textToHtml(text) {
    const trimmed = (text ?? "").trim();
    if (!trimmed) return "";

    return trimmed
        .split(/\n{2,}/)
        .map((paragraph) => `<p>${escapeHtml(paragraph).replace(/\n/g, "<br>")}</p>`)
        .join("\n");
}

function setFetchedYoutubeBody(code, description = "") {
    const descriptionHtml = textToHtml(description);
    form.value.body =
        `<iframe src="https://www.youtube.com/embed/${code}" frameborder="0" allowfullscreen width="560" height="315"></iframe>` +
        (descriptionHtml ? `\n${descriptionHtml}` : "");
}

function applyFetchedContent({ title, description, youtubeCode, thumbnailUrl, mediaBody = "" }) {
    const replacingSource = isSourceReplacement();

    if (title && (!form.value.title || replacingSource)) {
        form.value.title = title;
        if (slugGenerated.value) autoSlug();
    }

    form.value.youtube_code = youtubeCode ?? "";

    if (thumbnailUrl) {
        setFetchedThumbnail(thumbnailUrl);
    } else if (replacingSource) {
        thumbnailFile.value = null;
        form.value.thumbnail = null;
        form.value.thumbnail_url = "";
        if (fileInput.value) fileInput.value.value = "";
    }

    if (mediaBody) {
        form.value.body = mediaBody;
        return;
    }

    if (youtubeCode) {
        setFetchedYoutubeBody(youtubeCode, description);
        return;
    }

    if (description && (replacingSource || hasGeneratedMediaBody(form.value.body))) {
        form.value.body = description;
        return;
    }

    if (replacingSource || hasGeneratedMediaBody(form.value.body)) {
        form.value.body = "";
    }
}

// ── URL meta fetch ──────────────────────────────────────────────────────────
async function fetchMeta() {
    if (!form.value.source_url) return;
    fetching.value = true;
    fetchError.value = "";

    // Reddit — fetch client-side (server IP is blocked by Reddit)
    if (/reddit\.com\/r\/[^/]+\/comments\//.test(form.value.source_url)) {
        try {
            const jsonUrl =
                form.value.source_url
                    .replace(/[?#].*$/, "")
                    .replace(/\/$/, "") + "/.json";

            const res = await fetch(jsonUrl, {
                headers: { Accept: "application/json" },
            });
            const data = await res.json();
            const post = data[0]?.data?.children[0]?.data ?? {};

            // Thumbnail — preview image first, then post.thumbnail fallback
            const previews = post.preview?.images[0]?.resolutions ?? [];
            let thumbnailUrl = "";
            if (previews.length) {
                thumbnailUrl = previews[previews.length - 1].url.replace(
                    /&amp;/g,
                    "&",
                );
            } else if (
                post.url_overridden_by_dest?.match(/\.(jpg|jpeg|png|gif|webp)/i)
            ) {
                thumbnailUrl = post.url_overridden_by_dest;
            } else if (post.thumbnail?.startsWith("http")) {
                thumbnailUrl = post.thumbnail;
            }

            // Reddit hosted video — put as <video> tag in body
            let mediaBody = "";
            if (
                post.is_video &&
                post.media?.reddit_video?.fallback_url
            ) {
                const videoUrl = post.media.reddit_video.fallback_url.replace(
                    /&amp;/g,
                    "&",
                );
                mediaBody = `<video controls width="560" style="max-width:100%">\n  <source src="${videoUrl}" type="video/mp4">\n</video>`;
            }

            applyFetchedContent({
                title: post.title ?? "",
                description: post.selftext ?? "",
                youtubeCode: "",
                thumbnailUrl,
                mediaBody,
            });
        } catch {
            fetchError.value = "Could not fetch Reddit metadata.";
        } finally {
            fetching.value = false;
        }
        return;
    }

    // All other URLs — server-side fetch
    try {
        const res = await fetch("/chimbi/fetch-meta", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            body: JSON.stringify({ url: form.value.source_url }),
        });
        const data = await res.json();
        applyFetchedContent({
            title: data.title ?? "",
            description: data.description ?? "",
            youtubeCode: data.youtube_code ?? "",
            thumbnailUrl: data.thumbnail_url ?? "",
        });
    } catch {
        fetchError.value = "Could not fetch URL metadata.";
    } finally {
        fetching.value = false;
    }
}

function autoFetchIfEmpty() {
    if (!form.value.title && form.value.source_url) fetchMeta();
}

// ── Thumbnail ───────────────────────────────────────────────────────────────
function onFileChange(e) {
    thumbnailFile.value = e.target.files[0] ?? null;
    form.value.thumbnail_url = "";
}
function onThumbnailUrl() {
    thumbnailFile.value = null;
    form.value.thumbnail = null;
}
function clearThumbnail() {
    thumbnailFile.value = null;
    form.value.thumbnail_url = "";
    form.value.thumbnail = null;
    if (fileInput.value) fileInput.value.value = "";
}

// ── Tags ────────────────────────────────────────────────────────────────────
function tagName(id) {
    return props.allTags.find((t) => t.id === id)?.name ?? id;
}
function isTagSelected(id) {
    return form.value.tags.includes(id);
}
function toggleTag(id) {
    if (isTagSelected(id))
        form.value.tags = form.value.tags.filter((t) => t !== id);
    else form.value.tags.push(id);
    tagSearch.value = "";
}
function removeTag(id) {
    form.value.tags = form.value.tags.filter((t) => t !== id);
}
function addSuggestedTag(tag) {
    if (!isTagSelected(tag.id)) form.value.tags.push(tag.id);
}

async function createAndAddTag() {
    const name = tagSearch.value.trim();
    if (!name) return;
    const res = await fetch("/chimbi/tags", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
        body: JSON.stringify({ name }),
    });
    const tag = await res.json();
    props.allTags.push(tag);
    form.value.tags.push(tag.id);
    tagSearch.value = "";
}

// ── AI tag suggestions ─────────────────────────────────────────────────────
async function suggestTags() {
    suggesting.value = true;
    suggestError.value = "";
    suggestedTags.value = [];
    try {
        const res = await fetch("/chimbi/suggest-tags", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            body: JSON.stringify({
                title: form.value.title,
                body: form.value.body?.slice(0, 500) ?? "",
            }),
        });
        const data = await res.json();
        if (Array.isArray(data)) {
            suggestedTags.value = data;
        } else {
            suggestError.value = data.error ?? "Could not get suggestions.";
        }
    } catch {
        suggestError.value = "Tag suggestion failed.";
    } finally {
        suggesting.value = false;
    }
}

// ── Save ─────────────────────────────────────────────────────────────────────
async function save() {
    saving.value = true;
    errors.value = {};

    const data = { ...form.value };
    if (!data.excerpt?.trim()) data.excerpt = null;
    if (!data.trim_sentences) data.trim_sentences = null;
    if (data.excerpt || (data.body ?? "").includes("<!--more-->") || data.trim_sentences) {
        data.body_trim = null;
    }
    if (thumbnailFile.value) data.thumbnail = thumbnailFile.value;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            savedForm.value = { ...form.value };
            sourceBaselineUrl.value = form.value.source_url;
            setTimeout(() => (saved.value = false), 3000);
        },
        onError: (e) => {
            errors.value = e;
        },
        onFinish: () => {
            saving.value = false;
        },
    };

    if (props.mode === "create") {
        router.post("/chimbi/create", data, options);
    } else {
        // Use POST with _method spoofing for file uploads
        router.post(
            `/chimbi/edit/${props.article.id}`,
            { ...data, _method: "PUT" },
            options,
        );
    }
}

function confirmDelete() {
    if (!confirm("Delete this article? This cannot be undone.")) return;
    router.delete(`/chimbi/delete/${props.article.id}`);
}

function doLogout() {
    if (isDirty.value && !confirm("You have unsaved changes. Logout anyway?"))
        return;
    router.post("/chimbi/logout");
}
</script>

<style scoped>
.admin-btn-primary,
.admin-btn-secondary,
.admin-btn-danger,
.admin-btn-muted,
.admin-btn-muted-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    padding: 0.6rem 0.95rem;
    border-radius: 0.4rem;
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

.admin-btn-danger {
    background: #b91c1c;
    color: white;
    border-color: #dc2626;
}

.admin-btn-danger:hover {
    background: #dc2626;
    color: white;
}

.admin-btn-muted,
.admin-btn-muted-danger {
    background: #4f4943;
    color: #d4cdc0;
    border-color: #5b554f;
    opacity: 0.88;
}

.admin-btn-muted:hover {
    background: #59534d;
    color: #ebe5cb;
}

.admin-btn-muted-danger:hover {
    background: #6f1d1d;
    color: #fff3f3;
    border-color: #8b1f1f;
}

.admin-btn-primary:disabled,
.admin-btn-secondary:disabled,
.admin-btn-danger:disabled,
.admin-btn-muted:disabled,
.admin-btn-muted-danger:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.admin-stat {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    padding: 0.75rem 0.85rem;
    border: 1px solid #4f4943;
    border-radius: 0.45rem;
    background: rgba(42, 40, 32, 0.45);
}

.admin-stat-label {
    font-size: 0.68rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #8f8778;
}

.admin-stat-value {
    font-size: 0.95rem;
    color: #ebe5cb;
    line-height: 1.2;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.field-error {
    color: #f87171;
    font-size: 0.75rem;
    margin-top: 2px;
    min-height: 1rem;
}
</style>

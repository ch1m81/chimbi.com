<template>
    <div class="min-h-screen bg-[#2a2820] text-[#ebe5cb] font-['Ubuntu']">
        <!-- Header bar -->
        <div
            class="bg-[#383838] border-b border-[#4f4943] px-6 py-6 flex items-center justify-between sticky top-0 z-40"
        >
            <div class="flex items-center gap-4 min-w-0 w-1/3">
                <!-- Home -->
                <Link
                    href="/"
                    class="text-[#ebe5cb]! hover:text-[#c3e062]! hover:no-underline! text-2xl shrink-0"
                    >🏠</Link
                >
                <span class="text-[#6b6459]">/</span>
                <!-- Back -->
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
                    :href="`/view/${article.id}/${article.slug}`"
                    target="_blank"
                    rel="noopener"
                    class="text-xl text-[#c3e062] hover:underline shrink-0 bg-[#4f4943] px-4 py-2 rounded-sm border"
                    title="View article"
                    >🗨️ view article</a
                >
            </div>
            <div class="flex items-center gap-3 w-1/5 justify-end">
                <!-- Dirty indicator -->
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

                <!-- Prev / Next (edit only) -->
                <template v-if="mode === 'edit'">
                    <Link
                        v-if="prevArticle"
                        :href="`/chimbi/edit/${prevArticle.id}`"
                        :title="prevArticle.title"
                        class="px-6 py-1 text-lg bg-[#4f4943] hover:bg-[#67625b] rounded hover:no-underline text-[#ebe5cb]!"
                        >← prev</Link
                    >
                    <Link
                        v-if="nextArticle"
                        :href="`/chimbi/edit/${nextArticle.id}`"
                        :title="nextArticle.title"
                        class="px-6 py-1 text-lg bg-[#4f4943] hover:bg-[#67625b] rounded hover:no-underline text-[#ebe5cb]!"
                        >next →</Link
                    >
                </template>
            </div>
            <div class="flex items-center gap-3 shrink-0">
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
                    <!-- Trim option -->
                    <div class="flex items-center gap-3 mt-2">
                        <label
                            class="flex items-center gap-2 text-sm text-[#ebe5cb] cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                v-model="useTrim"
                                class="accent-[#c3e062] w-5 h-5"
                            />
                            <span class="text-lg"
                                >Trim body on list page at</span
                            >
                        </label>
                        <input
                            v-if="useTrim"
                            v-model.number="form.body_trim"
                            type="number"
                            min="50"
                            max="2000"
                            class="py-1 text-[#c3e062]"
                        />
                        <span v-if="useTrim" class="text-lg">characters</span>
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
                            v-if="useTrim && form.body_trim"
                            @click="previewMode = 'trimmed'"
                            class="px-3 py-1 rounded transition-colors"
                            :class="
                                previewMode === 'trimmed'
                                    ? 'bg-[#c3e062] text-[#2a2820] font-bold'
                                    : 'bg-[#4f4943] text-[#ebe5cb]'
                            "
                        >
                            Trimmed ({{ form.body_trim }} chars)
                        </button>
                    </div>
                    <button
                        @click="showPreview = false"
                        class="text-[#6b6459] hover:text-[#ebe5cb] text-xl"
                    >
                        ✕
                    </button>
                </div>
                <div class="px-6 py-4">
                    <div
                        class="font-['Ubuntu'] text-xl text-[#ebe5cb] capitalize mb-4 pb-2 border-b border-[#4f4943]"
                    >
                        {{ form.title }}
                    </div>
                    <div
                        class="text-[#ebe5cb] text-sm leading-relaxed"
                        v-html="previewContent"
                    ></div>
                    <div
                        v-if="previewMode === 'trimmed' && isTrimmed"
                        class="mt-3 text-[#6b6459] text-xs italic"
                    >
                        … trimmed at {{ form.body_trim }} characters
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
    article: { type: Object, default: null },
    allTags: { type: Array, default: () => [] },
    mode: { type: String, default: "create" },
    flash: { type: Object, default: () => ({}) },
    referrer: { type: String, default: "/" },
    prevArticle: { type: Object, default: null },
    nextArticle: { type: Object, default: null },
});

// ── Form state ────────────────────────────────────────────────────────────
const initialForm = {
    title: props.article?.title ?? "",
    slug: props.article?.slug ?? "",
    body: props.article?.body ?? "",
    body_trim: props.article?.body_trim ?? 300,
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
const savedForm = ref({ ...initialForm }); // tracks last saved state
const useTrim = ref(!!props.article?.body_trim);
const errors = ref({});
const saving = ref(false);
const saved = ref(false);
const showPreview = ref(false);
const previewMode = ref("full");
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
    if (props.referrer && props.referrer !== window.location.href) {
        window.location.href = props.referrer;
    } else {
        window.history.back();
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

const isTrimmed = computed(
    () =>
        useTrim.value &&
        form.value.body_trim &&
        (form.value.body ?? "").length > form.value.body_trim,
);

const previewContent = computed(() => {
    if (previewMode.value === "trimmed" && isTrimmed.value) {
        return (form.value.body ?? "")
            .slice(0, form.value.body_trim)
            .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
            .replace(/\*(.*?)\*/g, "<em>$1</em>")
            .replace(/\n/g, "<br>");
    }
    return renderedBody.value;
});

function openPreview() {
    previewMode.value = "full";
    showPreview.value = true;
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
const slugGenerated = ref(!props.article);

function autoSlug() {
    if (!slugGenerated.value) return;
    form.value.slug = form.value.title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-|-$/g, "");
}

watch(
    () => form.value.slug,
    () => {
        if (props.article) slugGenerated.value = false;
    },
);

// ── URL meta fetch ──────────────────────────────────────────────────────────
async function fetchMeta() {
    if (!form.value.source_url) return;
    fetching.value = true;
    fetchError.value = "";
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
        if (data.title && !form.value.title) form.value.title = data.title;
        if (data.youtube_code) form.value.youtube_code = data.youtube_code;
        if (data.thumbnail_url && !form.value.thumbnail_url)
            form.value.thumbnail_url = data.thumbnail_url;
        if (data.title && slugGenerated.value) autoSlug();
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

// ── AI tag suggestions (via Laravel proxy — no CORS) ────────────────────────
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
    if (!useTrim.value) form.value.body_trim = null;

    const data = { ...form.value };
    if (thumbnailFile.value) data.thumbnail = thumbnailFile.value;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            savedForm.value = { ...form.value };
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
        router.put(`/chimbi/edit/${props.article.id}`, data, options);
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

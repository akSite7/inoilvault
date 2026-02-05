<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    anime: {
        type: Object,
        required: true,
    },
    comments: {
        type: Array,
        required: true,
    },
    current_user: {
        type: Object,
        default: null,
    },
});

const normalizeType = (value) =>
    typeof value === 'string' ? value.trim().toLowerCase().replace(/\s+/g, ' ') : '';

const typeLabels = {
    tv: 'ТВ Сериал',
    'tv сериал': 'ТВ Сериал',
    'тв сериал': 'ТВ Сериал',
    movie: 'Фильм',
    film: 'Фильм',
    фильм: 'Фильм',
    ova: 'OVA',
    ona: 'ONA',
    спешл: 'Спешл',
};

const formatType = (value) => typeLabels[normalizeType(value)] || value || '-';
const formatRelatedMeta = (item) => {
    const parts = [];
    if (item?.type) parts.push(formatType(item.type));
    if (item?.year) parts.push(item.year);
    return parts.length ? parts.join(' / ') : '-';
};

const splitList = (value) => {
    if (Array.isArray(value)) return value.filter(Boolean);
    if (!value) return [];
    return value
        .split(/\s*[|,;]\s*/g)
        .map((item) => item.trim())
        .filter(Boolean);
};

const heroLines = computed(() => {
    const names = splitList(props.anime.main_character);
    const voices = splitList(props.anime.main_voice_actor);
    if (!names.length) return [];
    return names.map((name, index) => ({
        name,
        voice: voices[index] || '',
    }));
});

const studioValues = computed(() => {
    if (Array.isArray(props.anime.studios) && props.anime.studios.length) {
        return props.anime.studios.filter(Boolean);
    }
    if (props.anime.studio) {
        return [props.anime.studio];
    }
    return [];
});

const infoRows = computed(() => [
    { label: 'Тип', value: formatType(props.anime.type) },
    { label: 'Эпизоды', value: props.anime.episodes ?? '-' },
    { label: 'Статус', value: props.anime.status || '-' },
    { label: 'Жанр', value: props.anime.genres || '-' },
    { label: 'Первоисточник', value: props.anime.source || '-' },
    { label: 'Сезон', value: props.anime.season_label || '-' },
    { label: 'Выпуск', value: props.anime.year || '-' },
    { label: 'Студия', value: studioValues.value.length ? studioValues.value.join(', ') : '-' },
    { label: 'Рейтинг MPAA', value: props.anime.mpaa_rating || '-' },
    { label: 'Возрастные ограничения', value: props.anime.age_rating || '-' },
    { label: 'Длительность', value: props.anime.duration || '-' },
    {
        label: 'Главные герои',
        value: heroLines.value.length ? heroLines.value : '-',
    },
]);

const descriptionHtml = computed(() => {
    const raw = props.anime.description || '';
    return raw.replace(/<\/br\s*>/gi, '<br>').replace(/\n/g, '<br>');
});

const totalComments = computed(
    () => props.comments.reduce((sum, comment) => sum + 1 + (comment.replies?.length || 0), 0)
);

const hasComments = computed(() => props.comments?.length > 0);

const framesAll = computed(() => props.anime.frames || []);
const framesPreview = computed(() => framesAll.value.slice(0, 3));
const altTitles = computed(() => {
    if (!props.anime.alt_title) return [];
    return props.anime.alt_title
        .split(/\s*[|,;]\s*/)
        .map((title) => title.trim())
        .filter(Boolean);
});

const commentForm = useForm({ body: '' });
const replyBodies = ref({});
const editBodies = ref({});
const activeReplyId = ref(null);
const activeEditId = ref(null);
const hiddenReplies = ref({});
const initHiddenReplies = (items = []) => {
    const nextState = {};
    items.forEach((comment) => {
        if (comment.replies?.length) {
            const existing = Object.prototype.hasOwnProperty.call(hiddenReplies.value, comment.id)
                ? hiddenReplies.value[comment.id]
                : undefined;
            nextState[comment.id] = existing ?? true;
        }
    });
    hiddenReplies.value = nextState;
};

watch(
    () => props.comments,
    (value) => {
        initHiddenReplies(value || []);
    },
    { immediate: true },
);
const highlightedId = ref(null);
const highlightTimer = ref(null);

const isHighlighted = (id) => String(highlightedId.value) === String(id);

const highlightComment = (id) => {
    if (!id) return;
    highlightedId.value = String(id);
    if (highlightTimer.value) {
        window.clearTimeout(highlightTimer.value);
    }
    highlightTimer.value = window.setTimeout(() => {
        highlightedId.value = null;
    }, 2500);
};

const findRootCommentId = (targetId) => {
    const target = String(targetId);
    for (const comment of props.comments) {
        if (String(comment.id) === target) return comment.id;
        if (Array.isArray(comment.replies) && comment.replies.some((reply) => String(reply.id) === target)) {
            return comment.id;
        }
    }
    return null;
};

const scrollToComment = async (targetId) => {
    if (!targetId) return;
    const rootId = findRootCommentId(targetId);
    if (rootId && String(rootId) !== String(targetId)) {
        hiddenReplies.value[rootId] = false;
    }
    await nextTick();
    const targetEl = document.getElementById(`comment-${targetId}`);
    if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        highlightComment(targetId);
    }
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const targetId = params.get('comment');
    if (!targetId) return;
    const storedId = sessionStorage.getItem('scroll_comment_id');
    const shouldScroll = sessionStorage.getItem('scroll_comment_once') === '1' && storedId === String(targetId);
    if (!shouldScroll) return;
    sessionStorage.removeItem('scroll_comment_id');
    sessionStorage.removeItem('scroll_comment_once');
    scrollToComment(targetId);
});

onBeforeUnmount(() => {
    if (highlightTimer.value) {
        window.clearTimeout(highlightTimer.value);
    }
});
const showListOptions = ref(false);
const showCoverModal = ref(false);
const showFramesModal = ref(false);
const currentFrameIndex = ref(0);
const selectedListStatus = ref(props.anime.list_status || '');

const listOptions = [
    { value: 'watching', label: 'Смотрю', color: 'border-blue-400' },
    { value: 'completed', label: 'Просмотрено', color: 'border-green-400' },
    { value: 'on_hold', label: 'Отложено', color: 'border-gray-300' },
    { value: 'dropped', label: 'Брошено', color: 'border-red-400' },
    { value: 'planned', label: 'Запланировано', color: 'border-yellow-400' },
];
const removeListOption = { value: 'remove', label: 'Удалить из списка', color: 'border-red-400' };

const availableListOptions = computed(() =>
    selectedListStatus.value
        ? [...listOptions.filter((option) => option.value !== selectedListStatus.value), removeListOption]
        : listOptions
);

const formatTimeAgo = (isoString) => {
    if (!isoString) return '';
    const diff = Date.now() - new Date(isoString).getTime();
    const rtf = new Intl.RelativeTimeFormat('ru', { numeric: 'auto' });
    const seconds = Math.round(diff / 1000);
    if (seconds < 60) return rtf.format(-seconds, 'second');
    const minutes = Math.round(seconds / 60);
    if (minutes < 60) return rtf.format(-minutes, 'minute');
    const hours = Math.round(minutes / 60);
    if (hours < 24) return rtf.format(-hours, 'hour');
    const days = Math.round(hours / 24);
    if (days < 7) return rtf.format(-days, 'day');
    const weeks = Math.round(days / 7);
    if (weeks < 5) return rtf.format(-weeks, 'week');
    const months = Math.round(days / 30);
    if (months < 12) return rtf.format(-months, 'month');
    const years = Math.round(days / 365);
    return rtf.format(-years, 'year');
};

const submitComment = () => {
    const body = commentForm.body.trim();
    if (!body) return;
    commentForm.post(`/anime/${props.anime.id}/comments`, {
        preserveScroll: true,
        onSuccess: () => commentForm.reset('body'),
    });
};

const startReply = (commentId) => {
    activeEditId.value = null;
    activeReplyId.value = commentId;
    if (!replyBodies.value[commentId]) {
        replyBodies.value[commentId] = '';
    }
};

const submitReply = (commentId) => {
    const body = (replyBodies.value[commentId] || '').trim();
    if (!body) return;
    router.post(
        `/anime/${props.anime.id}/comments`,
        { body, parent_id: commentId },
        {
            preserveScroll: true,
            onSuccess: () => {
                replyBodies.value[commentId] = '';
                activeReplyId.value = null;
            },
        }
    );
};

const startEdit = (comment) => {
    activeReplyId.value = null;
    activeEditId.value = comment.id;
    editBodies.value[comment.id] = comment.text;
};
const closeInlineForms = () => {
    activeReplyId.value = null;
    activeEditId.value = null;
};

const submitEdit = (comment) => {
    const body = (editBodies.value[comment.id] || '').trim();
    if (!body) return;
    router.put(
        `/anime/${props.anime.id}/comments/${comment.id}`,
        { body },
        {
            preserveScroll: true,
            onSuccess: () => {
                activeEditId.value = null;
            },
        }
    );
};

const deleteComment = (comment) => {
    if (!confirm('Удалить комментарий?')) return;
    router.delete(`/anime/${props.anime.id}/comments/${comment.id}`, {
        preserveScroll: true,
    });
};

const react = (commentId, value) => {
    router.post(
        `/anime/${props.anime.id}/comments/${commentId}/react`,
        { value },
        { preserveScroll: true }
    );
};

const toggleReplies = (commentId) => {
    hiddenReplies.value[commentId] = !hiddenReplies.value[commentId];
};

const toggleListOptions = () => {
    showListOptions.value = !showListOptions.value;
};

const selectListStatus = (value) => {
    showListOptions.value = false;
    if (!props.current_user) {
        return;
    }
    if (value === 'remove') {
        selectedListStatus.value = '';
        router.delete(`/anime/${props.anime.id}/list`, { preserveScroll: true });
        return;
    }
    selectedListStatus.value = value;
    router.post(
        `/anime/${props.anime.id}/list`,
        { status: value },
        { preserveScroll: true }
    );
};

const openFrame = (index) => {
    if (!framesAll.value.length) return;
    currentFrameIndex.value = Math.max(0, Math.min(index, framesAll.value.length - 1));
    showFramesModal.value = true;
};

const closeFramesModal = () => {
    showFramesModal.value = false;
};

const nextFrame = () => {
    if (!framesAll.value.length) return;
    currentFrameIndex.value = (currentFrameIndex.value + 1) % framesAll.value.length;
};

const prevFrame = () => {
    if (!framesAll.value.length) return;
    currentFrameIndex.value =
        (currentFrameIndex.value - 1 + framesAll.value.length) % framesAll.value.length;
};
</script>

<template>
    <div class="w-full">
        <div class="max-w-[1440px] mx-auto p-5 space-y-6">
            <div class="flex flex-wrap items-start gap-6">
                <div class="w-full max-w-[340px] self-start rounded-xs bg-accent p-5 border border-secondary/60">
                    <div class="aspect-[3/4] overflow-hidden rounded-xs bg-secondary">
                        <img
                            v-if="anime.cover_url"
                            :src="anime.cover_url"
                            :alt="anime.title"
                            class="h-full w-full cursor-zoom-in object-cover"
                            @click="showCoverModal = true"
                        />
                    </div>
                    <div class="mt-4 space-y-2">
                        <button class="w-full rounded-xs bg-primary py-2 text-sm cursor-pointer text-white transition hover:bg-primary-hover">
                            Смотреть онлайн
                        </button>
                        <button class="w-full rounded-xs bg-secondary py-2 text-sm cursor-pointer text-text-primary transition hover:bg-secondary-hover">
                            Написать рецензию
                        </button>
                        <button
                            class="w-full rounded-xs cursor-pointer bg-secondary py-2 text-sm text-text-primary transition hover:bg-secondary-hover border-b-3"
                            :class="selectedListStatus ? (listOptions.find((option) => option.value === selectedListStatus)?.color || 'border-transparent') : 'border-transparent'"
                            type="button"
                            @click="toggleListOptions"
                        >
                            {{ listOptions.find((option) => option.value === selectedListStatus)?.label || 'Добавить в список' }}
                        </button>
                        <transition
                            enter-active-class="transition-[max-height] duration-300 ease-linear overflow-hidden"
                            enter-from-class="max-h-0"
                            enter-to-class="max-h-60"
                            leave-active-class="transition-[max-height] duration-300 ease-linear overflow-hidden"
                            leave-from-class="max-h-60"
                            leave-to-class="max-h-0"
                        >
                            <div v-if="showListOptions" class="space-y-2 overflow-hidden">
                                <button
                                    v-for="option in availableListOptions"
                                    :key="option.value"
                                    class="w-full rounded-xs cursor-pointer bg-secondary px-3 py-2 text-left text-sm text-text-primary transition hover:bg-secondary-hover border-b-2"
                                    :class="selectedListStatus === option.value ? option.color : 'border-transparent'"
                                    type="button"
                                    @click="selectListStatus(option.value)"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>

                <div class="flex-1 self-stretch rounded-xs bg-accent p-6 border border-secondary/60">
                    <h1 class="text-2xl font-semibold text-text-primary">{{ anime.title }}</h1>
                    <div v-if="altTitles.length" class="mt-1 flex flex-col gap-1 text-sm text-text-secondary">
                        <span v-for="(title, index) in altTitles" :key="`${title}-${index}`">{{ title }}</span>
                    </div>
                    <p v-else class="mt-1 text-sm text-text-secondary">-</p>

                    <div class="mt-5 grid gap-x-20 gap-y-2 text-sm md:grid-cols-[180px_1fr]">
                        <template v-for="row in infoRows" :key="row.label">
                            <div class="text-text-primary">{{ row.label }}</div>
                            <div class="text-text-secondary">
                                <template v-if="Array.isArray(row.value) && row.label === 'Главные герои'">
                                    <div v-for="(item, index) in row.value" :key="`${row.label}-${index}`">
                                        <span class="text-text-primary">{{ item.name }}</span>
                                        <span v-if="item.voice"> (озвучивает {{ item.voice }})</span>
                                    </div>
                                </template>
                                <template v-else-if="Array.isArray(row.value)">
                                    <div v-for="(item, index) in row.value" :key="`${row.label}-${index}`">
                                        {{ item }}
                                    </div>
                                </template>
                                <template v-else>
                                    {{ row.value }}
                                </template>
                            </div>
                        </template>
                    </div>

                    <p v-if="anime.description" class="mt-6 text-sm text-text-secondary" v-html="descriptionHtml"></p>
                </div>
            </div>

            <div class="rounded-xs bg-accent p-6 border border-secondary/60">
                <div class="grid gap-4 lg:grid-cols-4 items-start">
                    <h2 class="text-lg font-semibold lg:col-span-3">Кадры</h2>
                    <h2 class="text-lg font-semibold">Трейлер</h2>
                </div>
                <div class="mt-3 grid gap-4 lg:grid-cols-4 items-start">
                    <div class="grid gap-4 lg:col-span-3 lg:grid-cols-3">
                        <div
                            v-for="(frame, index) in framesPreview"
                            :key="`frame-${index}`"
                            class="h-[180px] overflow-hidden rounded-xs bg-secondary"
                        >
                            <img
                                :src="frame"
                                alt=""
                                class="h-full w-full cursor-zoom-in object-cover"
                                @click="openFrame(index)"
                            />
                        </div>
                        <div
                            v-if="!framesPreview.length"
                            class="h-[180px] rounded-xs bg-secondary p-6 text-sm text-text-secondary lg:col-span-3"
                        >
                            Кадры отсутствуют.
                        </div>
                    </div>
                    <div>
                        <div class="flex h-[180px] w-full items-center justify-center rounded-xs bg-secondary p-4">
                            <a
                                v-if="anime.trailer_url"
                                :href="anime.trailer_url"
                                target="_blank"
                                rel="noreferrer"
                                class="flex h-14 w-14 items-center justify-center rounded-full bg-black/30 hover:bg-secondary-hover transition text-primary"
                            >
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </a>
                            <span v-else class="text-sm text-text-secondary">Нет трейлера</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="anime.related_items?.length" class="rounded-xs bg-accent p-6 border border-secondary/60">
                <h3 class="text-lg font-semibold text-text-primary">Связанное</h3>
                <div class="mt-3 flex gap-6 ">
                    <div v-for="item in anime.related_items" :key="`related-${item.id}-${item.relation_type}`">
                        
                        <div class="mt-1 flex gap-4">
                            
                            <a :href="`/anime/${item.id}`" class="h-25 w-18 overflow-hidden rounded-xs bg-secondary block">
                                <img v-if="item.cover_url" :src="item.cover_url" alt="" class="h-full w-full object-cover" />
                            </a>
                            <div class="text-xs mt-1 text-text-secondary">
                                <a :href="`/anime/${item.id}`" class="text-sm text-text-primary hover:underline">
                                    {{
                                        item.title && item.title.length > 20
                                            ? `${item.title.slice(0, 20)}...`
                                            : item.title
                                    }}
                                </a>
                                <div class="mt-2">{{ formatRelatedMeta(item) }}</div>
                                <div class="mt-1 text-text-secondary">{{ item.relation_type }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xs bg-accent p-5 pb-1 border border-secondary/60">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">
                        Комментарии <span class="text-sm text-text-secondary">({{ totalComments }})</span>
                    </h2>
                </div>
                <div class="mt-4 space-y-3">
                    <textarea
                        v-if="current_user"
                        v-model="commentForm.body"
                        class="w-full rounded-xs bg-secondary p-3 text-sm text-text-primary focus:outline-none resize-y min-h-24 max-h-40 overflow-auto"
                        rows="5"
                        placeholder="Комментарий"
                    ></textarea>
                    <div v-else class="rounded-xs bg-secondary px-4 py-3 text-sm text-text-secondary">
                        Войдите, чтобы оставить комментарий.
                    </div>
                    <button
                        v-if="current_user"
                        :disabled="commentForm.processing"
                        @click="submitComment"
                        class="rounded-xs bg-primary px-4 py-2 text-sm text-white transition hover:bg-primary-hover disabled:opacity-60"
                        type="button"
                    >
                        Отправить
                    </button>
                </div>

                <div class="mt-6 space-y-6 text-sm" :class="{ 'pb-4': hasComments }">
                    <div v-for="comment in comments" :key="comment.id" :id="'comment-' + comment.id" class="relative flex gap-3 min-w-0 scroll-mt-24 transition">
                        <span
                            class="pointer-events-none absolute -inset-2 rounded-xs bg-secondary/50 transition-opacity duration-500 ease-out"
                            :class="isHighlighted(comment.id) ? 'opacity-100' : 'opacity-0'"
                        ></span>
                        <a :href="`/profile/${comment.user}`" class="relative z-10 shrink-0">
                            <img :src="comment.avatar_url" alt="" class="h-13 w-13 rounded-xs object-cover" />
                        </a>
                        <div class="relative z-10 flex-1 min-w-0">
                            <div class="flex items-center gap-1 text-text-primary">
                                <a :href="`/profile/${comment.user}`" class="font-semibold hover:underline">{{ comment.user }}</a>
                                <span v-if="comment.role === 'admin'" class="inline-flex">
                                    <svg
                                        class="h-3.5 w-3.5 text-blue-400"
                                        fill="currentColor"
                                        version="1.0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 64 64"
                                        xml:space="preserve"
                                    >
                                        <g>
                                            <path
                                                d="M63.652,14.49c-0.277-1.584-1.283-1.544-2.209-0.618s-9.787,9.786-9.787,9.786c-3.123,3.123-8.189,3.123-11.312-0.001c-3.125-3.124-3.125-8.188,0-11.313c0,0,8.963-8.963,9.787-9.786c0.822-0.823,0.9-1.901-0.621-2.21C48.375,0.117,47.201,0,46,0c-9.941,0-18,8.06-18,18s8.059,18,18,18s18-8.06,18-18C64,16.799,63.852,15.631,63.652,14.49z"
                                            />
                                            <path d="M2.342,50.344c-3.123,3.123-3.123,8.189,0.001,11.313s8.19,3.124,11.313,0.001l7.794-7.794L10.136,42.55L2.342,50.344z M8,59c-1.656,0-3-1.344-3-3s1.344-3,3-3s3,1.344,3,3S9.657,59,8,59z" />
                                            <path d="M27.393,25.293L11.55,41.136L22.865,52.45l15.842-15.843C33.535,34.578,29.422,30.466,27.393,25.293z" />
                                        </g>
                                    </svg>
                                </span>
                                <span v-else-if="comment.role === 'moderator'" class="inline-flex">
                                    <svg class="h-3.5 w-3.5 text-blue-400" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4.35009 13.3929L8 16L11.6499 13.3929C13.7523 11.8912 15 9.46667 15 6.88306V3L8 0L1 3V6.88306C1 9.46667 2.24773 11.8912 4.35009 13.3929Z" />
                                    </svg>
                                </span>
                                <span class="text-text-secondary">{{ formatTimeAgo(comment.created_at) }}</span>
                            </div>
                            <div v-if="activeEditId === comment.id" class="mt-2 space-y-2">
                                <textarea
                                    v-model="editBodies[comment.id]"
                                    class="w-full rounded-xs bg-secondary p-2 text-sm text-text-primary focus:outline-none resize-y min-h-24 max-h-40 overflow-auto"
                                    rows="4"
                                ></textarea>
                                <div class="flex gap-2">
                                    <button
                                        class="rounded-xs bg-primary px-3 py-2 cursor-pointer hover:bg-primary-hover text-sm text-white"
                                        type="button"
                                        @click="submitEdit(comment)"
                                    >
                                        Сохранить
                                    </button>
                                    <button
                                        class="rounded-xs bg-secondary px-3 py-2 cursor-pointer hover:bg-secondary-hover text-sm text-text-primary"
                                        type="button"
                                        @click="activeEditId = null"
                                    >
                                        Отмена
                                    </button>
                                </div>
                            </div>
                            <div v-else class="mt-1 text-text-secondary break-words overflow-wrap-anywhere max-w-full">
                                {{ comment.text }}
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-text-secondary">
                                <button class="hover:text-text-primary cursor-pointer" type="button" @click="startReply(comment.id)">
                                    ОТВЕТИТЬ
                                </button>
                                <button
                                    v-if="comment.can_edit"
                                    class="hover:text-text-primary"
                                    type="button"
                                    aria-label="Редактировать"
                                    @click="startEdit(comment)"
                                >
                                    <svg class="cursor-pointer" width="13px" height="13px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="#838287" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="#838287" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                </button>
                                <button
                                    v-if="comment.can_delete"
                                    class="hover:text-text-primary"
                                    type="button"
                                    aria-label="Удалить"
                                    @click="deleteComment(comment)"
                                >
                                     <svg width="13px" class="cursor-pointer" height="13px" viewBox="-3 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
                                        <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-261.000000, -205.000000)" fill="#000000">
                                            <path fill="#838287" d="M268,220 C268,219.448 268.448,219 269,219 C269.552,219 270,219.448 270,220 L270,232 C270,232.553 269.552,233 269,233 C268.448,233 268,232.553 268,232 L268,220 L268,220 Z M273,220 C273,219.448 273.448,219 274,219 C274.552,219 275,219.448 275,220 L275,232 C275,232.553 274.552,233 274,233 C273.448,233 273,232.553 273,232 L273,220 L273,220 Z M278,220 C278,219.448 278.448,219 279,219 C279.552,219 280,219.448 280,220 L280,232 C280,232.553 279.552,233 279,233 C278.448,233 278,232.553 278,232 L278,220 L278,220 Z M263,233 C263,235.209 264.791,237 267,237 L281,237 C283.209,237 285,235.209 285,233 L285,217 L263,217 L263,233 L263,233 Z M277,209 L271,209 L271,208 C271,207.447 271.448,207 272,207 L276,207 C276.552,207 277,207.447 277,208 L277,209 L277,209 Z M285,209 L279,209 L279,207 C279,205.896 278.104,205 277,205 L271,205 C269.896,205 269,205.896 269,207 L269,209 L263,209 C261.896,209 261,209.896 261,211 L261,213 C261,214.104 261.895,214.999 262.999,215 L285.002,215 C286.105,214.999 287,214.104 287,213 L287,211 C287,209.896 286.104,209 285,209 L285,209 Z" id="trash" sketch:type="MSShapeGroup"></path>
                                        </g>
                                    </g>
                                </svg>
                                </button>
                                <div class="flex items-center gap-1">
                                    <button type="button" class="hover:text-text-primary" @click="react(comment.id, 1)">
                                          <svg
                                              xmlns="http://www.w3.org/2000/svg"
                                              width="15"
                                              height="15"
                                              viewBox="0 0 30 30"
                                              fill="currentColor"
                                              class="duration-200 cursor-pointer"
                                              :class="comment.user_reaction === 1 ? 'text-green-400' : 'text-text-secondary'"
                                          >
                                            <path d="M5.048 10.901H.016v14.846h5.032V10.9zM16.362.926L7.76 9.641a.573.573 0 0 0-.165.403v14.264c0 .167.073.326.2.435l1.32 1.13a.573.573 0 0 0 .372.139H23.43c.225 0 .429-.132.521-.337l4.409-9.745c1.127-3.758-1.1-5.6-2.434-6.09a.518.518 0 0 0-.18-.031h-7.519a.573.573 0 0 1-.566-.661l1.045-6.684a.573.573 0 0 0-.218-.544L17.117.873a.573.573 0 0 0-.755.053z"></path>
                                        </svg>
                                    </button>
                                    <span>{{ comment.likes }}</span>
                                    <button type="button" class="hover:text-text-primary" @click="react(comment.id, -1)">
                                          <svg
                                              xmlns="http://www.w3.org/2000/svg"
                                              width="15"
                                              height="15"
                                              viewBox="0 0 30 30"
                                              fill="currentColor"
                                              class="duration-200 rotate-180 cursor-pointer"
                                              :class="comment.user_reaction === -1 ? 'text-red-400' : 'text-text-secondary'"
                                          >
                                            <path d="M5.048 10.901H.016v14.846h5.032V10.9zM16.362.926L7.76 9.641a.573.573 0 0 0-.165.403v14.264c0 .167.073.326.2.435l1.32 1.13a.573.573 0 0 0 .372.139H23.43c.225 0 .429-.132.521-.337l4.409-9.745c1.127-3.758-1.1-5.6-2.434-6.09a.518.518 0 0 0-.18-.031h-7.519a.573.573 0 0 1-.566-.661l1.045-6.684a.573.573 0 0 0-.218-.544L17.117.873a.573.573 0 0 0-.755.053z"></path>
                                        </svg>
                                    </button>
                                    <span>{{ (comment.dislikes ?? 0) > 0 ? '-' + comment.dislikes : '0' }}</span>
                                </div>
                            </div>

                            <div v-if="activeReplyId === comment.id && current_user" class="mt-3 space-y-2">
                                <textarea
                                    v-model="replyBodies[comment.id]"
                                    class="w-full rounded-xs bg-secondary p-2 text-sm text-text-primary focus:outline-none resize-y min-h-24 max-h-40 overflow-auto"
                                    rows="4"
                                    placeholder="Ответить..."
                                ></textarea>
                                <div class="flex gap-2">
                                    <button class="rounded-xs bg-primary px-3 py-2 text-sm cursor-pointer hover:bg-primary-hover text-white" type="button" @click="submitReply(comment.id)">
                                        Ответить
                                    </button>
                                    <button class="rounded-xs bg-secondary px-3 py-2 text-sm cursor-pointer hover:bg-secondary-hover text-text-primary" type="button" @click="closeInlineForms">
                                        Отмена
                                    </button>
                                </div>
                            </div>

                            <button
                                v-if="comment.replies.length"
                                class="mt-2 text-sm text-text-secondary cursor-pointer hover:text-text-primary"
                                type="button"
                                @click="toggleReplies(comment.id)"
                            >
                                {{
                                    hiddenReplies[comment.id]
                                        ? `Показать ответы (${comment.replies.length})`
                                        : `Скрыть ответы (${comment.replies.length})`
                                }}
                            </button>

                            <div v-if="comment.replies.length && !hiddenReplies[comment.id]" class="mt-4 space-y-4">
                                <div v-for="reply in comment.replies" :key="reply.id" :id="'comment-' + reply.id" class="relative flex gap-3 min-w-0 scroll-mt-24 transition">
                                    <span
                                        class="pointer-events-none absolute -inset-2 rounded-xs bg-secondary/50 transition-opacity duration-500 ease-out"
                                        :class="isHighlighted(reply.id) ? 'opacity-100' : 'opacity-0'"
                                    ></span>
                                    <a :href="`/profile/${reply.user}`" class="relative z-10 shrink-0">
                                        <img :src="reply.avatar_url" alt="" class="h-13 w-13 rounded-xs object-cover" />
                                    </a>
                                    <div class="relative z-10 flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-1 text-text-primary">
                                            <a :href="`/profile/${reply.user}`" class="font-semibold hover:underline">{{ reply.user }}</a>
                                            <span v-if="reply.role === 'admin'" class="inline-flex">
                                                <svg
                                                    class="h-3.5 w-3.5 text-blue-400"
                                                    fill="currentColor"
                                                    version="1.0"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 64 64"
                                                    xml:space="preserve"
                                                >
                                                    <g>
                                                        <path
                                                            d="M63.652,14.49c-0.277-1.584-1.283-1.544-2.209-0.618s-9.787,9.786-9.787,9.786c-3.123,3.123-8.189,3.123-11.312-0.001c-3.125-3.124-3.125-8.188,0-11.313c0,0,8.963-8.963,9.787-9.786c0.822-0.823,0.9-1.901-0.621-2.21C48.375,0.117,47.201,0,46,0c-9.941,0-18,8.06-18,18s8.059,18,18,18s18-8.06,18-18C64,16.799,63.852,15.631,63.652,14.49z"
                                                        />
                                                        <path d="M2.342,50.344c-3.123,3.123-3.123,8.189,0.001,11.313s8.19,3.124,11.313,0.001l7.794-7.794L10.136,42.55L2.342,50.344z M8,59c-1.656,0-3-1.344-3-3s1.344-3,3-3s3,1.344,3,3S9.657,59,8,59z" />
                                                        <path d="M27.393,25.293L11.55,41.136L22.865,52.45l15.842-15.843C33.535,34.578,29.422,30.466,27.393,25.293z" />
                                                    </g>
                                                </svg>
                                            </span>
                                            <span v-else-if="reply.role === 'moderator'" class="inline-flex">
                                                <svg class="h-3.5 w-3.5 text-blue-400" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M4.35009 13.3929L8 16L11.6499 13.3929C13.7523 11.8912 15 9.46667 15 6.88306V3L8 0L1 3V6.88306C1 9.46667 2.24773 11.8912 4.35009 13.3929Z" />
                                                </svg>
                                            </span>
                                          <svg class="mt-[2px]" width="15px" height="15px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4 12H20M20 12L16 8M20 12L16 16" stroke="#838287" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                            <span class="text-text-secondary">{{ reply.reply_to }}</span>
                                            <span class="text-text-secondary text-xs mt-[1px]">{{ formatTimeAgo(reply.created_at) }}</span>
                                        </div>
                                        <div v-if="activeEditId === reply.id" class="mt-2 space-y-2">
                                            <textarea
                                                v-model="editBodies[reply.id]"
                                                class="w-full rounded-xs bg-secondary p-2 text-sm text-text-primary focus:outline-none resize-y min-h-24 max-h-40 overflow-auto"
                                                rows="4"
                                            ></textarea>
                                            <div class="flex gap-2">
                                                <button
                                                    class="rounded-xs bg-primary px-3 py-2 text-sm cursor-pointer hover:bg-primary-hover text-white"
                                                    type="button"
                                                    @click="submitEdit(reply)"
                                                >
                                                    Сохранить
                                                </button>
                                                <button
                                                    class="rounded-xs bg-secondary px-3 py-2 cursor-pointer hover:bg-secondary-hover text-sm text-text-primary"
                                                    type="button"
                                                    @click="closeInlineForms"
                                                >
                                                    Отмена
                                                </button>
                                            </div>
                                        </div>
                                        <div v-else class="mt-1 text-text-secondary break-words overflow-wrap-anywhere max-w-full">
                                            {{ reply.text }}
                                        </div>
                                          <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-text-secondary">
                                              <button class="hover:text-text-primary cursor-pointer" type="button" @click="startReply(reply.id)">
                                                  ОТВЕТИТЬ
                                              </button>
                                            <button
                                                v-if="reply.can_edit"
                                                class="hover:text-text-primary"
                                                type="button"
                                                aria-label="Редактировать"
                                                @click="startEdit(reply)"
                                            >
                                                     <svg class="cursor-pointer" width="13px" height="13px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="#838287" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="#838287" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                            </button>
                                            <button
                                                v-if="reply.can_delete"
                                                class="hover:text-text-primary"
                                                type="button"
                                                aria-label="Удалить"
                                                @click="deleteComment(reply)"
                                            >
                                                <svg width="13px" class="cursor-pointer" height="13px" viewBox="-3 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
                                        <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-261.000000, -205.000000)" fill="#000000">
                                            <path fill="#838287" d="M268,220 C268,219.448 268.448,219 269,219 C269.552,219 270,219.448 270,220 L270,232 C270,232.553 269.552,233 269,233 C268.448,233 268,232.553 268,232 L268,220 L268,220 Z M273,220 C273,219.448 273.448,219 274,219 C274.552,219 275,219.448 275,220 L275,232 C275,232.553 274.552,233 274,233 C273.448,233 273,232.553 273,232 L273,220 L273,220 Z M278,220 C278,219.448 278.448,219 279,219 C279.552,219 280,219.448 280,220 L280,232 C280,232.553 279.552,233 279,233 C278.448,233 278,232.553 278,232 L278,220 L278,220 Z M263,233 C263,235.209 264.791,237 267,237 L281,237 C283.209,237 285,235.209 285,233 L285,217 L263,217 L263,233 L263,233 Z M277,209 L271,209 L271,208 C271,207.447 271.448,207 272,207 L276,207 C276.552,207 277,207.447 277,208 L277,209 L277,209 Z M285,209 L279,209 L279,207 C279,205.896 278.104,205 277,205 L271,205 C269.896,205 269,205.896 269,207 L269,209 L263,209 C261.896,209 261,209.896 261,211 L261,213 C261,214.104 261.895,214.999 262.999,215 L285.002,215 C286.105,214.999 287,214.104 287,213 L287,211 C287,209.896 286.104,209 285,209 L285,209 Z" id="trash" sketch:type="MSShapeGroup"></path>
                                        </g>
                                    </g>
                                </svg>
                                            </button>
                                              <div class="flex items-center gap-1">
                                                  <button type="button" class="hover:text-text-primary" @click="react(reply.id, 1)">
                                                      <svg
                                                          xmlns="http://www.w3.org/2000/svg"
                                                          width="15"
                                                          height="15"
                                                          viewBox="0 0 30 30"
                                                          fill="currentColor"
                                                          class="duration-200 cursor-pointer"
                                                          :class="reply.user_reaction === 1 ? 'text-green-400' : 'text-text-secondary'"
                                                      >
                                                          <path d="M5.048 10.901H.016v14.846h5.032V10.9zM16.362.926L7.76 9.641a.573.573 0 0 0-.165.403v14.264c0 .167.073.326.2.435l1.32 1.13a.573.573 0 0 0 .372.139H23.43c.225 0 .429-.132.521-.337l4.409-9.745c1.127-3.758-1.1-5.6-2.434-6.09a.518.518 0 0 0-.18-.031h-7.519a.573.573 0 0 1-.566-.661l1.045-6.684a.573.573 0 0 0-.218-.544L17.117.873a.573.573 0 0 0-.755.053z"></path>
                                                      </svg>
                                                  </button>
                                                  <span>{{ reply.likes }}</span>
                                                  <button type="button" class="hover:text-text-primary" @click="react(reply.id, -1)">
                                                      <svg
                                                          xmlns="http://www.w3.org/2000/svg"
                                                          width="15"
                                                          height="15"
                                                          viewBox="0 0 30 30"
                                                          fill="currentColor"
                                                          class="duration-200 rotate-180 cursor-pointer"
                                                          :class="reply.user_reaction === -1 ? 'text-red-400' : 'text-text-secondary'"
                                                      >
                                                          <path d="M5.048 10.901H.016v14.846h5.032V10.9zM16.362.926L7.76 9.641a.573.573 0 0 0-.165.403v14.264c0 .167.073.326.2.435l1.32 1.13a.573.573 0 0 0 .372.139H23.43c.225 0 .429-.132.521-.337l4.409-9.745c1.127-3.758-1.1-5.6-2.434-6.09a.518.518 0 0 0-.18-.031h-7.519a.573.573 0 0 1-.566-.661l1.045-6.684a.573.573 0 0 0-.218-.544L17.117.873a.573.573 0 0 0-.755.053z"></path>
                                                      </svg>
                                                  </button>
                                                  <span>{{ (reply.dislikes ?? 0) > 0 ? '-' + reply.dislikes : '0' }}</span>
                                              </div>
                                          </div>
                                          <div v-if="activeReplyId === reply.id && current_user" class="mt-3 space-y-2">
                                              <textarea
                                                  v-model="replyBodies[reply.id]"
                                                  class="w-full rounded-xs bg-secondary p-2 text-sm text-text-primary focus:outline-none resize-y min-h-24 max-h-40 overflow-auto"
                                                  rows="4"
                                                  placeholder="Ответить..."
                                              ></textarea>
                                              <div class="flex gap-2">
                                                  <button class="rounded-xs bg-primary px-3 py-2 text-sm cursor-pointer hover:bg-primary-hover text-white" type="button" @click="submitReply(reply.id)">
                                                      Ответить
                                                  </button>
                                                  <button class="rounded-xs bg-secondary px-3 py-2 hover:bg-secondary-hover  cursor-pointer text-sm text-text-primary" type="button" @click="closeInlineForms">
                                                      Отмена
                                                  </button>
                                              </div>
                                          </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                </div>
            </div>
        </div>

        <teleport to="body">
            <div
                v-if="showCoverModal"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-6"
                @click.self="showCoverModal = false"
            >
                <button
                    class="absolute right-6 top-6 flex h-9 w-9 cursor-pointer items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                    type="button"
                    @click="showCoverModal = false"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
                <img
                    v-if="anime.cover_url"
                    :src="anime.cover_url"
                    :alt="anime.title"
                    class="max-h-[95vh] max-w-[95vw] rounded-xs object-contain"
                />
            </div>
        </teleport>

        <teleport to="body">
            <div
                v-if="showFramesModal"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-6"
                @click.self="closeFramesModal"
            >
                <button
                    class="absolute right-6 top-6 flex h-9 w-9 items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                    type="button"
                    @click="closeFramesModal"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
                <div class="absolute top-6 left-6 rounded-xs bg-secondary px-3 py-1 text-sm text-text-primary">
                    {{ currentFrameIndex + 1 }} / {{ framesAll.length }}
                </div>
                <button
                    class="absolute left-6 top-1/2 -translate-y-1/2 flex h-10 w-10 cursor-pointer items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                    type="button"
                    @click.stop="prevFrame"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                        <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <button
                    class="absolute right-6 top-1/2 -translate-y-1/2 flex h-10 w-10 cursor-pointer items-center justify-center rounded-xs bg-secondary text-text-primary transition hover:bg-secondary-hover"
                    type="button"
                    @click.stop="nextFrame"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                        <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <img
                    :src="framesAll[currentFrameIndex]"
                    alt=""
                    class="max-h-[90vh] max-w-[90vw] rounded-xs object-contain"
                />
            </div>
        </teleport>
    </div>
</template>


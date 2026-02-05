<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
    pagination: {
        type: Object,
        default: () => ({ current_page: 1, last_page: 1, has_more: false, total: 0 }),
    },
    unread: {
        type: Number,
        default: 0,
    },
});

const items = ref([...props.notifications]);
const pagination = ref({ ...props.pagination });
const isLoadingMore = ref(false);
const loadMoreTrigger = ref(null);
const observer = ref(null);
const unreadCount = ref(props.unread ?? 0);

const clampText = (value, max = 140) => {
    const text = String(value ?? '');
    if (text.length <= max) return text;
    if (max <= 3) return text.slice(0, max);
    return `${text.slice(0, max - 3)}...`;
};


const formatNotificationMessage = (item, max) => {
    if (!item) return '';
    const body = item.comment_body;
    if (body && (item.type === 'comment_reply' || item.type === 'comment_like')) {
        const actorName = item.actor?.username || '';
        const action = item.type === 'comment_reply'
            ? 'ответил(а) на ваш комментарий:'
            : 'лайкнул(а) ваш комментарий:';
        const snippet = clampText(body, max);
        return `${actorName} ${action}\n"${snippet}".`;
    }
    if (item.message && max) {
        return clampText(item.message, max);
    }
    return item.message || '';
};

const markNotificationsRead = async () => {
    if (unreadCount.value <= 0) return;
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        await fetch('/notifications/read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        });
        unreadCount.value = 0;
        const now = new Date().toISOString();
        items.value = items.value.map((item) => (item.read_at ? item : { ...item, read_at: now }));
    } catch (error) {
    }
};

const getNotificationCommentId = (item) => {
    const directId = item?.comment_id;
    if (directId) return String(directId);
    const url = item?.url;
    if (!url) return null;
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.searchParams.get('comment');
    } catch (error) {
        return null;
    }
};

const handleNotificationClick = (item) => {
    const id = getNotificationCommentId(item);
    if (id) {
        sessionStorage.setItem('scroll_comment_id', id);
        sessionStorage.setItem('scroll_comment_once', '1');
    }
};
const deleteNotification = async (item) => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(`/notifications/${item.id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        });
        if (!response.ok) return;
        const data = await response.json().catch(() => ({}));
        items.value = items.value.filter((notification) => notification.id !== item.id);
        if (typeof data.unread === 'number') {
            unreadCount.value = data.unread;
        }
        if (pagination.value?.has_more) {
            await loadMore();
        }
    } catch (error) {
    }
};

const loadMore = async () => {
    if (isLoadingMore.value || !pagination.value?.has_more) return;
    isLoadingMore.value = true;
    const nextPage = (pagination.value?.current_page || 1) + 1;

    try {
        const response = await fetch(`/notifications?page=${nextPage}&per_page=30`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        const data = await response.json();
        const newItems = Array.isArray(data.items) ? data.items : [];
        items.value = [...items.value, ...newItems];
        if (data.pagination) {
            pagination.value = { ...pagination.value, ...data.pagination };
        }
    } catch (error) {
    } finally {
        isLoadingMore.value = false;
    }
};

onMounted(() => {
    markNotificationsRead();
    observer.value = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '200px' },
    );

    if (loadMoreTrigger.value) {
        observer.value.observe(loadMoreTrigger.value);
    }
});

onBeforeUnmount(() => {
    if (observer.value && loadMoreTrigger.value) {
        observer.value.unobserve(loadMoreTrigger.value);
    }
});
</script>

<template>
    <div class="w-full">
        <div class="max-w-[1440px] mx-auto p-5">
            <div class="rounded bg-accent p-6 border border-secondary/60">
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-semibold">Уведомления</h1>
                    <span class="text-sm text-text-secondary">{{ items.length }}</span>
                </div>

                <div v-if="!items.length" class="mt-6 text-sm text-text-secondary">Нет уведомлений.</div>

                <div v-else class="mt-6 space-y-3">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-start gap-3 rounded-xs p-3 transition hover:bg-secondary"
                        :class="item.read_at ? 'bg-secondary/60' : 'bg-secondary'"
                    >
                        <Link :href="item.url || '#'" class="col-span-2 flex min-w-0 items-start gap-3" @click="handleNotificationClick(item)">
                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xs bg-secondary">
                                <img
                                    :src="(item.actor && item.actor.avatar_url) || '/storage/images/placeholders/avatar-placeholder.png'"
                                    alt=""
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="text-xs text-text-secondary min-w-0 flex-1">
                                <div class="text-sm text-text-primary">{{ item.title }}</div>
                                <div class="whitespace-pre-line break-words overflow-wrap-anywhere w-full">{{ formatNotificationMessage(item, 145) }}</div>
                            </div>
                        </Link>
                        <button
                            class="flex h-8 w-8 self-center shrink-0 items-center justify-center rounded-xs bg-secondary text-text-secondary transition hover:bg-secondary-hover hover:text-text-primary"
                            type="button"
                            @click.stop.prevent="deleteNotification(item)"
                        >
                            <svg width="13px" class="cursor-pointer" height="13px" viewBox="-3 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
        <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-261.000000, -205.000000)" fill="#000000">
            <path fill="#838287" d="M268,220 C268,219.448 268.448,219 269,219 C269.552,219 270,219.448 270,220 L270,232 C270,232.553 269.552,233 269,233 C268.448,233 268,232.553 268,232 L268,220 L268,220 Z M273,220 C273,219.448 273.448,219 274,219 C274.552,219 275,219.448 275,220 L275,232 C275,232.553 274.552,233 274,233 C273.448,233 273,232.553 273,232 L273,220 L273,220 Z M278,220 C278,219.448 278.448,219 279,219 C279.552,219 280,219.448 280,220 L280,232 C280,232.553 279.552,233 279,233 C278.448,233 278,232.553 278,232 L278,220 L278,220 Z M263,233 C263,235.209 264.791,237 267,237 L281,237 C283.209,237 285,235.209 285,233 L285,217 L263,217 L263,233 L263,233 Z M277,209 L271,209 L271,208 C271,207.447 271.448,207 272,207 L276,207 C276.552,207 277,207.447 277,208 L277,209 L277,209 Z M285,209 L279,209 L279,207 C279,205.896 278.104,205 277,205 L271,205 C269.896,205 269,205.896 269,207 L269,209 L263,209 C261.896,209 261,209.896 261,211 L261,213 C261,214.104 261.895,214.999 262.999,215 L285.002,215 C286.105,214.999 287,214.104 287,213 L287,211 C287,209.896 286.104,209 285,209 L285,209 Z" id="trash" sketch:type="MSShapeGroup"></path>
        </g>
    </g>
</svg>
                        </button>
                    </div>
                </div>

                <div v-if="isLoadingMore" class="flex items-center justify-center gap-3 py-2 text-sm text-text-secondary">
                    <span class="catalog-spinner h-6 w-6"></span>
                    Загружаем еще...
                </div>
                <div ref="loadMoreTrigger" class="h-1"></div>
            </div>
        </div>
    </div>
</template>
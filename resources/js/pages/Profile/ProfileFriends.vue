<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    friends: {
        type: Array,
        default: () => [],
    },
    incoming: {
        type: Array,
        default: () => [],
    },
    outgoing: {
        type: Array,
        default: () => [],
    },
    username: {
        type: String,
        default: '',
    },
    is_owner: {
        type: Boolean,
        default: false,
    },
});

const isAdmin = (role) => role === 'admin';
const isModerator = (role) => role === 'moderator';

const acceptRequest = (id) => {
    router.post(`/friends/request/${id}/accept`, {}, { preserveScroll: true });
};

const declineRequest = (id) => {
    router.post(`/friends/request/${id}/decline`, {}, { preserveScroll: true });
};

const cancelRequest = (id) => {
    router.delete(`/friends/request/${id}`, { preserveScroll: true });
};

const removeFriend = (id) => {
    router.delete(`/friends/${id}`, { preserveScroll: true });
};
const fakeAvatar = '/images/placeholders/avatar-placeholder.png';
const roleForIndex = (index) => {
    if (index % 13 === 0) return 'admin';
    if (index % 9 === 0) return 'moderator';
    return 'user';
};

const fakeFriends = Array.from({ length: 0 }, (_, index) => {
    const id = index + 1;
    return {
        id,
        username: `friend_${id}`,
        avatar_url: fakeAvatar,
        is_online: index % 3 !== 0,
        role: roleForIndex(index),
    };
});

const fakeIncoming = Array.from({ length: 0 }, (_, index) => {
    const id = index + 101;
    return {
        id,
        user: {
            id,
            username: `incoming_${index + 1}`,
            avatar_url: fakeAvatar,
            role: roleForIndex(index + 2),
        },
    };
});

const fakeOutgoing = Array.from({ length: 0 }, (_, index) => {
    const id = index + 201;
    return {
        id,
        user: {
            id,
            username: `outgoing_${index + 1}`,
            avatar_url: fakeAvatar,
            role: roleForIndex(index + 4),
        },
    };
});

const friendsList = computed(() => (props.friends.length ? props.friends : fakeFriends));
const incomingList = computed(() => (props.incoming.length ? props.incoming : fakeIncoming));
const outgoingList = computed(() => (props.outgoing.length ? props.outgoing : fakeOutgoing));

const friendsInitialBatch = 36;
const friendsBatchSize = 12;
const friendsVisibleCount = ref(Math.min(friendsInitialBatch, friendsList.value.length));
const friendsVisible = computed(() => friendsList.value.slice(0, friendsVisibleCount.value));
const hasMoreFriends = computed(() => friendsVisibleCount.value < friendsList.value.length);
const isLoadingFriends = ref(false);
const friendsLoadTrigger = ref(null);
const friendsObserver = ref(null);
const initialBatch = 5;
const batchSize = 10;
const incomingVisibleCount = ref(initialBatch);
const outgoingVisibleCount = ref(initialBatch);

const incomingVisible = computed(() => incomingList.value.slice(0, incomingVisibleCount.value));
const outgoingVisible = computed(() => outgoingList.value.slice(0, outgoingVisibleCount.value));

const showMoreIncoming = () => {
    incomingVisibleCount.value += batchSize;
};

const showMoreOutgoing = () => {
    outgoingVisibleCount.value += batchSize;
};

const loadMoreFriends = () => {
    if (isLoadingFriends.value || !hasMoreFriends.value) return;
    isLoadingFriends.value = true;
    friendsVisibleCount.value = Math.min(
        friendsVisibleCount.value + friendsBatchSize,
        friendsList.value.length,
    );
    requestAnimationFrame(() => {
        isLoadingFriends.value = false;
    });
};

onMounted(() => {
    friendsObserver.value = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMoreFriends();
            }
        },
        { rootMargin: '200px' },
    );

    if (friendsLoadTrigger.value) {
        friendsObserver.value.observe(friendsLoadTrigger.value);
    }
});

onBeforeUnmount(() => {
    if (friendsObserver.value && friendsLoadTrigger.value) {
        friendsObserver.value.unobserve(friendsLoadTrigger.value);
    }
});
</script>

<template>
    <div class="w-full">
        <div class="max-w-[1440px] mx-auto p-5">
            <div class="grid items-start gap-6 lg:grid-cols-[2fr_1fr]">
                <div class="rounded bg-accent p-6 border border-secondary/60 flex flex-col" :class="!friendsList.length ? 'self-stretch' : ''">
                    <div class="flex items-center gap-1">
                        <h1 class="text-lg font-semibold">Друзья</h1>
                        <span class="text-sm mt-[3px] text-text-secondary">{{ friendsList.length }}</span>
                    </div>

                    <div v-if="!friendsList.length" class="mt-6 flex flex-1 items-center justify-center text-center text-sm text-text-secondary">
                        Список друзей пуст.
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="friend in friendsVisible"
                            :key="friend.id"
                            class="rounded-xs bg-secondary/60 p-3 transition hover:bg-secondary"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <Link :href="`/profile/${friend.username}`" class="flex items-center gap-3">
                                    <img
                                        :src="friend.avatar_url"
                                        alt=""
                                        class="h-12 w-12 rounded-xs object-cover border-b-2"
                                        :class="friend.is_online ? 'border-green-500' : 'border-red-400'"
                                    />
                                    <div class="flex items-center gap-1 text-sm text-text-primary">
                                        <span>{{ friend.username }}</span>
                                        <span v-if="isAdmin(friend.role)" class="inline-flex">
                                            <svg
                                                class="h-4 w-4 text-blue-400"
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
                                        <span v-else-if="isModerator(friend.role)" class="inline-flex">
                                            <svg class="h-4 w-4 text-blue-400" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M4.35009 13.3929L8 16L11.6499 13.3929C13.7523 11.8912 15 9.46667 15 6.88306V3L8 0L1 3V6.88306C1 9.46667 2.24773 11.8912 4.35009 13.3929Z" />
                                            </svg>
                                        </span>
                                    </div>
                                </Link>
                                <button
                                    v-if="is_owner"
                                    class="rounded-xs bg-secondary px-2 py-2 text-text-secondary hover:text-text-primary hover:bg-secondary-hover"
                                    type="button"
                                    @click="removeFriend(friend.id)"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        <path d="M8 6V4h8v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        <path d="M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="isLoadingFriends" class="flex items-center justify-center gap-3 py-2 text-sm text-text-secondary">
                        <span class="catalog-spinner h-6 w-6"></span>
                        Загружаем еще...
                    </div>
                    <div ref="friendsLoadTrigger" class="h-1"></div>
                </div>

                <div class="space-y-6">
                    <div class="rounded bg-accent p-6 border border-secondary/60">
                        <div class="flex items-center gap-1">
                            <h2 class="text-sm font-semibold text-text-primary">Заявки в друзья</h2>
                            <span class="text-sm text-text-secondary">{{ incomingList.length }}</span>
                        </div>
                        <div v-if="!incomingList.length" class="mt-4 text-sm text-text-secondary">
                            Нет входящих заявок.
                        </div>
                        <div v-else class="mt-4 space-y-3">
                            <div
                                v-for="request in incomingVisible"
                                :key="request.id"
                                class="flex items-center justify-between gap-3 rounded-xs bg-secondary/60 p-3"
                            >
                                <Link :href="`/profile/${request.user.username}`" class="flex items-center gap-3">
                                    <img :src="request.user.avatar_url" alt="" class="h-10 w-10 rounded object-cover" />
                                    <span class="flex items-center gap-1 text-sm text-text-primary">
                                        {{ request.user.username }}
                                        <span v-if="isAdmin(request.user.role)" class="inline-flex">
                                            <svg
                                                class="h-4 w-4 text-blue-400"
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
                                        <span v-else-if="isModerator(request.user.role)" class="inline-flex">
                                            <svg class="h-4 w-4 text-blue-400" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M4.35009 13.3929L8 16L11.6499 13.3929C13.7523 11.8912 15 9.46667 15 6.88306V3L8 0L1 3V6.88306C1 9.46667 2.24773 11.8912 4.35009 13.3929Z" />
                                            </svg>
                                        </span>
                                    </span>
                                </Link>
                                <div class="flex items-center gap-2">
                                    <button
                                        class="rounded-xs bg-primary px-3 py-[6px] cursor-pointer text-xs text-white hover:bg-primary-hover"
                                        type="button"
                                        @click="acceptRequest(request.id)"
                                    >
                                        Принять
                                    </button>
                                    <button
                                        class="rounded-xs bg-secondary px-3 py-[6px] cursor-pointer text-xs text-text-primary hover:bg-secondary-hover"
                                        type="button"
                                        @click="declineRequest(request.id)"
                                    >
                                        Отклонить
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="incomingList.length > incomingVisibleCount" class="mt-4">
                            <button
                                class="w-full rounded-xs bg-secondary px-3 py-2 cursor-pointer text-sm text-text-primary hover:bg-secondary-hover"
                                type="button"
                                @click="showMoreIncoming"
                            >
                                Показать еще
                            </button>
                        </div>
                    </div>

                    <div class="rounded bg-accent p-6 border border-secondary/60">
                        <div class="flex items-center gap-1">
                            <h2 class="text-sm font-semibold text-text-primary">Мои заявки</h2>
                            <span class="text-sm text-text-secondary">{{ outgoingList.length }}</span>
                        </div>
                        <div v-if="!outgoingList.length" class="mt-4 text-sm text-text-secondary">
                            Нет отправленных заявок.
                        </div>
                        <div v-else class="mt-4 space-y-3">
                            <div
                                v-for="request in outgoingVisible"
                                :key="request.id"
                                class="flex items-center justify-between gap-3 rounded-xs bg-secondary/60 p-3"
                            >
                                <Link :href="`/profile/${request.user.username}`" class="flex items-center gap-3">
                                    <img :src="request.user.avatar_url" alt="" class="h-10 w-10 rounded object-cover" />
                                    <span class="flex items-center gap-1 text-sm text-text-primary">
                                        {{ request.user.username }}
                                        <span v-if="isAdmin(request.user.role)" class="inline-flex">
                                            <svg
                                                class="h-4 w-4 text-blue-400"
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
                                        <span v-else-if="isModerator(request.user.role)" class="inline-flex">
                                            <svg class="h-4 w-4 text-blue-400" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M4.35009 13.3929L8 16L11.6499 13.3929C13.7523 11.8912 15 9.46667 15 6.88306V3L8 0L1 3V6.88306C1 9.46667 2.24773 11.8912 4.35009 13.3929Z" />
                                            </svg>
                                        </span>
                                    </span>
                                </Link>
                                <button
                                    class="rounded-xs bg-secondary px-3 py-[6px] cursor-pointer text-xs text-text-primary hover:bg-secondary-hover"
                                    type="button"
                                    @click="cancelRequest(request.id)"
                                >
                                    Отозвать
                                </button>
                            </div>
                        </div>
                        <div v-if="outgoingList.length > outgoingVisibleCount" class="mt-4">
                            <button
                                class="w-full rounded-xs bg-secondary px-3 py-2 cursor-pointer text-sm text-text-primary hover:bg-secondary-hover"
                                type="button"
                                @click="showMoreOutgoing"
                            >
                                Показать еще
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

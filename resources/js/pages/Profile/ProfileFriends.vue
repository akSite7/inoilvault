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

const friendsList = computed(() => props.friends);
const incomingList = computed(() => props.incoming);
const outgoingList = computed(() => props.outgoing);

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
            <div
                class="grid items-start gap-6"
                :class="is_owner ? 'lg:grid-cols-[2fr_1fr]' : 'lg:grid-cols-1'"
            >
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
                                    class="rounded-xs cursor-pointer bg-secondary px-2 py-2 text-text-secondary hover:text-text-primary hover:bg-secondary-hover"
                                    type="button"
                                    @click="removeFriend(friend.id)"
                                >
                                    <svg width="13px"  height="13px" viewBox="-3 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
                                        <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-261.000000, -205.000000)" fill="#000000">
                                            <path fill="#838287" d="M268,220 C268,219.448 268.448,219 269,219 C269.552,219 270,219.448 270,220 L270,232 C270,232.553 269.552,233 269,233 C268.448,233 268,232.553 268,232 L268,220 L268,220 Z M273,220 C273,219.448 273.448,219 274,219 C274.552,219 275,219.448 275,220 L275,232 C275,232.553 274.552,233 274,233 C273.448,233 273,232.553 273,232 L273,220 L273,220 Z M278,220 C278,219.448 278.448,219 279,219 C279.552,219 280,219.448 280,220 L280,232 C280,232.553 279.552,233 279,233 C278.448,233 278,232.553 278,232 L278,220 L278,220 Z M263,233 C263,235.209 264.791,237 267,237 L281,237 C283.209,237 285,235.209 285,233 L285,217 L263,217 L263,233 L263,233 Z M277,209 L271,209 L271,208 C271,207.447 271.448,207 272,207 L276,207 C276.552,207 277,207.447 277,208 L277,209 L277,209 Z M285,209 L279,209 L279,207 C279,205.896 278.104,205 277,205 L271,205 C269.896,205 269,205.896 269,207 L269,209 L263,209 C261.896,209 261,209.896 261,211 L261,213 C261,214.104 261.895,214.999 262.999,215 L285.002,215 C286.105,214.999 287,214.104 287,213 L287,211 C287,209.896 286.104,209 285,209 L285,209 Z" id="trash" sketch:type="MSShapeGroup"></path>
                                        </g>
                                    </g>
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

                <div v-if="is_owner" class="space-y-6">
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

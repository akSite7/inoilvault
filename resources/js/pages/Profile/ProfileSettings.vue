<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  avatar: null,
  cover: null,
  bio: props.user.bio ?? '',
  username: props.user.username ?? '',
});

const avatarPreviewUrl = ref(props.user.avatar_url ?? '');
const coverPreviewUrl = ref(props.user.cover_url ?? '');
const tempObjectUrls = new Set();

const cropper = reactive({
  open: false,
  mode: 'avatar',
  title: '',
  imageUrl: '',
  naturalWidth: 0,
  naturalHeight: 0,
  baseWidth: 0,
  baseHeight: 0,
  scale: 1,
  displayWidth: 0,
  displayHeight: 0,
  rectX: 0,
  rectY: 0,
  rectW: 0,
  rectH: 0,
  dragging: false,
  resizing: false,
  resizeHandle: '',
  startX: 0,
  startY: 0,
  startRectX: 0,
  startRectY: 0,
  startRectW: 0,
  startRectH: 0,
});

const outputByMode = computed(() => {
  return cropper.mode === 'cover'
    ? { width: 1400, height: 400 }
    : { width: 400, height: 400 };
});

const aspectRatioByMode = computed(() => {
  return cropper.mode === 'cover' ? 1400 / 400 : 136 / 136;
});

const stageStyle = computed(() => ({
  width: `${cropper.displayWidth}px`,
  height: `${cropper.displayHeight}px`,
}));

const gridStyle = computed(() => ({
  left: `${cropper.rectX}px`,
  top: `${cropper.rectY}px`,
  width: `${cropper.rectW}px`,
  height: `${cropper.rectH}px`,
}));

const overlayStyles = computed(() => {
  const displayW = Math.round(cropper.displayWidth);
  const displayH = Math.round(cropper.displayHeight);
  const rectX = Math.round(cropper.rectX);
  const rectY = Math.round(cropper.rectY);
  const rectW = Math.round(cropper.rectW);
  const rectH = Math.round(cropper.rectH);

  const topHeight = Math.max(0, Math.floor(rectY));
  const leftWidth = Math.max(0, Math.floor(rectX));

  const bottomTopPos = Math.min(displayH, Math.ceil(rectY + rectH));
  const bottomHeight = Math.max(0, displayH - bottomTopPos);

  const rightLeftPos = Math.min(displayW, Math.ceil(rectX + rectW));
  const rightWidth = Math.max(0, displayW - rightLeftPos);

  return {
    top: {
      left: '0px',
      top: '0px',
      width: `${displayW}px`,
      height: `${topHeight}px`,
    },
    bottom: {
      left: '0px',
      top: `${bottomTopPos}px`,
      width: `${displayW}px`,
      height: `${bottomHeight}px`,
    },
    left: {
      left: '0px',
      top: `${rectY}px`,
      width: `${leftWidth}px`,
      height: `${rectH}px`,
    },
    right: {
      left: `${rightLeftPos}px`,
      top: `${rectY}px`,
      width: `${rightWidth}px`,
      height: `${rectH}px`,
    },
  };
});

const createObjectUrl = (file) => {
  const url = URL.createObjectURL(file);
  tempObjectUrls.add(url);
  return url;
};

const clampRect = () => {
  const aspect = aspectRatioByMode.value;
  const minSize = Math.min(80, cropper.displayWidth, cropper.displayHeight);

  cropper.rectW = Math.max(minSize, Math.min(cropper.rectW, cropper.displayWidth));
  cropper.rectH = cropper.rectW / aspect;

  if (cropper.rectH > cropper.displayHeight) {
    cropper.rectH = cropper.displayHeight;
    cropper.rectW = cropper.rectH * aspect;
  }

  cropper.rectX = Math.min(cropper.displayWidth - cropper.rectW, Math.max(0, cropper.rectX));
  cropper.rectY = Math.min(cropper.displayHeight - cropper.rectH, Math.max(0, cropper.rectY));
};

const openCropper = (file, mode) => {
  if (!file) return;

  cropper.mode = mode;
  cropper.title = mode === 'cover' ? 'Обрезать обложку' : 'Обрезать аватар';
  cropper.imageUrl = createObjectUrl(file);

  const image = new Image();
  image.onload = () => {
    const maxWidth = Math.floor(window.innerWidth * 0.9);
    const maxHeight = Math.floor(window.innerHeight * 0.75);
    const scale = Math.min(1, maxWidth / image.naturalWidth, maxHeight / image.naturalHeight);

    cropper.naturalWidth = image.naturalWidth;
    cropper.naturalHeight = image.naturalHeight;
    cropper.baseWidth = Math.max(1, Math.round(image.naturalWidth * scale));
    cropper.baseHeight = Math.max(1, Math.round(image.naturalHeight * scale));
    cropper.scale = 1;
    cropper.displayWidth = cropper.baseWidth;
    cropper.displayHeight = cropper.baseHeight;

    const aspect = aspectRatioByMode.value;
    let rectW = cropper.displayWidth * 0.7;
    let rectH = rectW / aspect;

    if (cropper.mode === 'cover') {
      rectH = cropper.displayHeight * 0.9;
      rectW = rectH * aspect;

      if (rectW > cropper.displayWidth * 0.95) {
        rectW = cropper.displayWidth * 0.95;
        rectH = rectW / aspect;
      }
    } else if (rectH > cropper.displayHeight * 0.7) {
      rectH = cropper.displayHeight * 0.7;
      rectW = rectH * aspect;
    }

    cropper.rectW = rectW;
    cropper.rectH = rectH;
    cropper.rectX = (cropper.displayWidth - rectW) / 2;
    cropper.rectY = (cropper.displayHeight - rectH) / 2;
    clampRect();

    cropper.open = true;
  };

  image.src = cropper.imageUrl;
};

const onAvatarChange = (event) => {
  const file = event.target.files?.[0];
  openCropper(file, 'avatar');
};

const onCoverChange = (event) => {
  const file = event.target.files?.[0];
  openCropper(file, 'cover');
};

const onDrop = (event, mode) => {
  const file = event.dataTransfer?.files?.[0];
  openCropper(file, mode);
};

const submit = () => {
  form.post(`/profile/${props.user.username}/settings`);
};

const startDrag = (event) => {
  event.currentTarget?.setPointerCapture?.(event.pointerId);
  cropper.dragging = true;
  cropper.startX = event.clientX;
  cropper.startY = event.clientY;
  cropper.startRectX = cropper.rectX;
  cropper.startRectY = cropper.rectY;
};

const startResize = (handle, event) => {
  event.currentTarget?.setPointerCapture?.(event.pointerId);
  cropper.resizing = true;
  cropper.resizeHandle = handle;
  cropper.startX = event.clientX;
  cropper.startY = event.clientY;
  cropper.startRectX = cropper.rectX;
  cropper.startRectY = cropper.rectY;
  cropper.startRectW = cropper.rectW;
  cropper.startRectH = cropper.rectH;
};

const onPointerMove = (event) => {
  if (cropper.dragging) {
    const dx = event.clientX - cropper.startX;
    const dy = event.clientY - cropper.startY;
    cropper.rectX = cropper.startRectX + dx;
    cropper.rectY = cropper.startRectY + dy;
    clampRect();
    return;
  }

  if (!cropper.resizing) return;

  const dx = event.clientX - cropper.startX;
  const dy = event.clientY - cropper.startY;
  let rectW = cropper.startRectW;
  let rectH = cropper.startRectH;
  let rectX = cropper.startRectX;
  let rectY = cropper.startRectY;

  const applyResize = (nextW, anchorX, anchorY) => {
    const aspect = aspectRatioByMode.value;
    let width = nextW;
    let height = width / aspect;
    let nextX = anchorX;
    let nextY = anchorY;

    if (nextX + width > cropper.displayWidth) {
      width = cropper.displayWidth - nextX;
      height = width / aspect;
    }

    if (nextY + height > cropper.displayHeight) {
      height = cropper.displayHeight - nextY;
      width = height * aspect;
    }

    cropper.rectW = width;
    cropper.rectH = height;
    cropper.rectX = nextX;
    cropper.rectY = nextY;
    clampRect();
  };

  if (cropper.resizeHandle === 'se') {
    rectW = cropper.startRectW + dx;
    rectX = cropper.startRectX;
    rectY = cropper.startRectY;
    applyResize(rectW, rectX, rectY);
  } else if (cropper.resizeHandle === 'sw') {
    rectW = cropper.startRectW - dx;
    rectX = cropper.startRectX + dx;
    rectY = cropper.startRectY;
    applyResize(rectW, rectX, rectY);
  } else if (cropper.resizeHandle === 'ne') {
    rectW = cropper.startRectW + dx;
    rectH = rectW / aspectRatioByMode.value;
    rectX = cropper.startRectX;
    rectY = cropper.startRectY + (cropper.startRectH - rectH);
    applyResize(rectW, rectX, rectY);
  } else if (cropper.resizeHandle === 'nw') {
    rectW = cropper.startRectW - dx;
    rectH = rectW / aspectRatioByMode.value;
    rectX = cropper.startRectX + dx;
    rectY = cropper.startRectY + (cropper.startRectH - rectH);
    applyResize(rectW, rectX, rectY);
  } else if (cropper.resizeHandle === 'e') {
    rectW = cropper.startRectW + dx;
    rectX = cropper.startRectX;
    rectY = cropper.startRectY + (cropper.startRectH - rectW / aspectRatioByMode.value) / 2;
    applyResize(rectW, rectX, rectY);
  } else if (cropper.resizeHandle === 'w') {
    rectW = cropper.startRectW - dx;
    rectX = cropper.startRectX + dx;
    rectY = cropper.startRectY + (cropper.startRectH - rectW / aspectRatioByMode.value) / 2;
    applyResize(rectW, rectX, rectY);
  } else if (cropper.resizeHandle === 's') {
    rectH = cropper.startRectH + dy;
    rectW = rectH * aspectRatioByMode.value;
    rectX = cropper.startRectX + (cropper.startRectW - rectW) / 2;
    rectY = cropper.startRectY;
    applyResize(rectW, rectX, rectY);
  } else if (cropper.resizeHandle === 'n') {
    rectH = cropper.startRectH - dy;
    rectW = rectH * aspectRatioByMode.value;
    rectX = cropper.startRectX + (cropper.startRectW - rectW) / 2;
    rectY = cropper.startRectY + dy;
    applyResize(rectW, rectX, rectY);
  }
};

const endPointer = (event) => {
  event?.currentTarget?.releasePointerCapture?.(event.pointerId);
  cropper.dragging = false;
  cropper.resizing = false;
  cropper.resizeHandle = '';
};

const setScale = (nextScale) => {
  if (!cropper.baseWidth || !cropper.baseHeight) return;

  const maxScale = Math.max(
    1,
    Math.min(3, cropper.naturalWidth / cropper.baseWidth, cropper.naturalHeight / cropper.baseHeight),
  );

  const clamped = Math.min(maxScale, Math.max(1, nextScale));
  const centerX = cropper.rectX + cropper.rectW / 2;
  const centerY = cropper.rectY + cropper.rectH / 2;
  const ratioX = cropper.displayWidth ? centerX / cropper.displayWidth : 0.5;
  const ratioY = cropper.displayHeight ? centerY / cropper.displayHeight : 0.5;

  cropper.scale = clamped;
  cropper.displayWidth = Math.round(cropper.baseWidth * cropper.scale);
  cropper.displayHeight = Math.round(cropper.baseHeight * cropper.scale);

  cropper.rectX = ratioX * cropper.displayWidth - cropper.rectW / 2;
  cropper.rectY = ratioY * cropper.displayHeight - cropper.rectH / 2;
  clampRect();
};

const onWheel = (event) => {
  event.preventDefault();
  const delta = event.deltaY < 0 ? 0.1 : -0.1;
  setScale(cropper.scale + delta);
};

const applyCrop = () => {
  if (!cropper.imageUrl) return;

  const output = outputByMode.value;
  const scaleX = cropper.naturalWidth / cropper.displayWidth;
  const scaleY = cropper.naturalHeight / cropper.displayHeight;

  const sx = cropper.rectX * scaleX;
  const sy = cropper.rectY * scaleY;
  const sw = cropper.rectW * scaleX;
  const sh = cropper.rectH * scaleY;

  const canvas = document.createElement('canvas');
  canvas.width = output.width;
  canvas.height = output.height;
  const ctx = canvas.getContext('2d');

  if (!ctx) return;

  const image = new Image();
  image.onload = () => {
    ctx.drawImage(image, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);

    canvas.toBlob((blob) => {
      if (!blob) return;

      const fileName = cropper.mode === 'cover' ? 'cover.jpg' : 'avatar.jpg';
      const croppedFile = new File([blob], fileName, { type: blob.type || 'image/jpeg' });
      const previewUrl = createObjectUrl(croppedFile);

      if (cropper.mode === 'cover') {
        form.cover = croppedFile;
        coverPreviewUrl.value = previewUrl;
      } else {
        form.avatar = croppedFile;
        avatarPreviewUrl.value = previewUrl;
      }

      closeCropper();
    }, 'image/jpeg', 0.92);
  };

  image.src = cropper.imageUrl;
};

const closeCropper = () => {
  cropper.open = false;
  cropper.imageUrl = '';
};

onBeforeUnmount(() => {
  tempObjectUrls.forEach((url) => URL.revokeObjectURL(url));
});
</script>

<template>
  <div class="w-full">
    <div class="max-w-[1440px] mx-auto p-5">
      <div class="bg-accent rounded p-8 border border-secondary/60">
        <div class="flex items-center justify-between">
          <h1 class="font-bold text-3xl">Редактирование профиля</h1>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
          <div class="mt-5">
            <label class="block text-sm font-medium text-text-secondary">Ник</label>
            <input
              v-model="form.username"
              class="mt-1 w-full px-4 py-2 rounded-xs bg-secondary focus:outline-none"
              maxlength="24"
              type="text"
            />
            <span v-if="form.errors.username" class="text-xs text-rose-400">{{ form.errors.username }}</span>
          </div>

          <div class="mt-5">
            <label class="block text-sm font-medium text-text-secondary">Описание</label>
            <textarea
              v-model="form.bio"
              class="mt-1 w-full px-4 py-2 rounded-xs bg-secondary focus:outline-none min-h-[6rem] max-h-[15rem] resize-y overflow-auto"
              maxlength="500"
            ></textarea>
            <span v-if="form.errors.bio" class="text-xs text-rose-400">{{ form.errors.bio }}</span>
          </div>

          <div class="flex flex-row gap-2">
            <div class="">
              <label class="text-text-secondary">Аватар</label>
              <label
                class="relative mt-1 flex flex-col items-center justify-center w-32 h-32 border-2 border-gray-700 rounded cursor-pointer hover:border-primary hover:text-primary overflow-hidden bg-center bg-cover"
                :style="avatarPreviewUrl ? { backgroundImage: `url(${avatarPreviewUrl})` } : null"
                @dragover.prevent
                @drop.prevent="onDrop($event, 'avatar')"
              >
                <div class="flex flex-col items-center justify-center z-20 bg-black/50 text-white w-full h-full text-center transition-opacity duration-200 hover:bg-black/60">
                  <svg class="w-6 h-6 mb-1 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8.414a2 2 0 00-.586-1.414l-4.414-4.414A2 2 0 0012.586 2H4zm9 5a1 1 0 00-2 0v2H9a1 1 0 000 2h2v2a1 1 0 002 0v-2h2a1 1 0 100-2h-2V8z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-xs text-gray-300">Выбрать фото</span>
                </div>
                <input class="hidden" type="file" accept="image/*" @change="onAvatarChange" />
              </label>
              <span v-if="form.errors.avatar" class="text-xs text-rose-400">{{ form.errors.avatar }}</span>
            </div>
            <div class=" w-9/10">
              <label class="text-text-secondary">Обложка</label>
              <label
                class="mt-1 relative flex flex-col items-center justify-center w-full h-32 border-2 border-gray-700 rounded-xs cursor-pointer hover:border-primary hover:text-primary transition-all overflow-hidden bg-center bg-cover"
                :style="coverPreviewUrl ? { backgroundImage: `url(${coverPreviewUrl})` } : null"
                @dragover.prevent
                @drop.prevent="onDrop($event, 'cover')"
              >
                <div class="flex flex-col items-center justify-center pt-5 pb-6 z-20 bg-black/50 text-white w-full h-full text-center transition-opacity duration-200 hover:bg-black/60">
                  <svg class="w-8 h-8 mb-2 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8.414a2 2 0 00-.586-1.414l-4.414-4.414A2 2 0 0012.586 2H4zm9 5a1 1 0 00-2 0v2H9a1 1 0 000 2h2v2a1 1 0 002 0v-2h2a1 1 0 100-2h-2V8z" clip-rule="evenodd" />
                  </svg>
                  <p class="text-sm text-gray-300">Выбрать фото</p>
                </div>
                <input class="hidden" type="file" accept="image/*" @change="onCoverChange" />
              </label>
              <span v-if="form.errors.cover" class="text-xs text-rose-400">{{ form.errors.cover }}</span>
            </div>
          </div>

          <button
            class="py-2 px-6 rounded-xs cursor-pointer mt-2 bg-primary hover:bg-primary-hover text-white transition duration-200"
            type="submit"
            :disabled="form.processing"
          >
            Сохранить изменения
          </button>
        </form>
      </div>
    </div>
  </div>

  <div v-if="cropper.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-6">
    <div class="inline-block max-w-none bg-accent rounded p-6 border border-secondary/60">
      <h2 class="text-xl font-semibold">{{ cropper.title }}</h2>
      <div class="mt-5 pb-[24px]">
        <div class="relative" :style="stageStyle" @wheel="onWheel">
          <img
            v-if="cropper.imageUrl"
            :src="cropper.imageUrl"
            alt="Crop"
            class="block h-full w-full select-none"
            draggable="false"
          />
          <div class="absolute inset-0 pointer-events-none">
            <div class="absolute bg-black/50" :style="overlayStyles.top"></div>
            <div class="absolute bg-black/50" :style="overlayStyles.bottom"></div>
            <div class="absolute bg-black/50" :style="overlayStyles.left"></div>
            <div class="absolute bg-black/50" :style="overlayStyles.right"></div>
          </div>
          <div
            class="absolute border border-blue-400"
            :style="gridStyle"
            @pointerdown.prevent="startDrag"
            @pointermove="onPointerMove"
            @pointerup="endPointer"
            @pointerleave="endPointer"
          >
            <div class="absolute inset-0">
              <div class="absolute left-1/3 top-0 h-full w-px border-l border-dashed border-white/50"></div>
              <div class="absolute left-2/3 top-0 h-full w-px border-l border-dashed border-white/50"></div>
              <div class="absolute top-1/3 left-0 h-px w-full border-t border-dashed border-white/50"></div>
              <div class="absolute top-2/3 left-0 h-px w-full border-t border-dashed border-white/50"></div>
            </div>
            <div
              class="absolute left-1/2 -top-1 h-3 w-3 -translate-x-1/2 bg-blue-400"
              @pointerdown.stop.prevent="startResize('n', $event)"
              @pointermove="onPointerMove"
              @pointerup="endPointer"
              @pointerleave="endPointer"
            ></div>
            <div
              class="absolute left-1/2 -bottom-1 h-3 w-3 -translate-x-1/2 bg-blue-400"
              @pointerdown.stop.prevent="startResize('s', $event)"
              @pointermove="onPointerMove"
              @pointerup="endPointer"
              @pointerleave="endPointer"
            ></div>
            <div
              class="absolute -left-1 top-1/2 h-3 w-3 -translate-y-1/2 bg-blue-400"
              @pointerdown.stop.prevent="startResize('w', $event)"
              @pointermove="onPointerMove"
              @pointerup="endPointer"
              @pointerleave="endPointer"
            ></div>
            <div
              class="absolute -right-1 top-1/2 h-3 w-3 -translate-y-1/2 bg-blue-400"
              @pointerdown.stop.prevent="startResize('e', $event)"
              @pointermove="onPointerMove"
              @pointerup="endPointer"
              @pointerleave="endPointer"
            ></div>
            <div
              class="absolute -left-1 -top-1 h-3 w-3 bg-blue-400"
              @pointerdown.stop.prevent="startResize('nw', $event)"
              @pointermove="onPointerMove"
              @pointerup="endPointer"
              @pointerleave="endPointer"
            ></div>
            <div
              class="absolute -right-1 -top-1 h-3 w-3 bg-blue-400"
              @pointerdown.stop.prevent="startResize('ne', $event)"
              @pointermove="onPointerMove"
              @pointerup="endPointer"
              @pointerleave="endPointer"
            ></div>
            <div
              class="absolute -left-1 -bottom-1 h-3 w-3 bg-blue-400"
              @pointerdown.stop.prevent="startResize('sw', $event)"
              @pointermove="onPointerMove"
              @pointerup="endPointer"
              @pointerleave="endPointer"
            ></div>
            <div
              class="absolute -right-1 -bottom-1 h-3 w-3 bg-blue-400"
              @pointerdown.stop.prevent="startResize('se', $event)"
              @pointermove="onPointerMove"
              @pointerup="endPointer"
              @pointerleave="endPointer"
            ></div>
          </div>
        </div>
      </div>
      <div class=" flex justify-end gap-3">
        <button
          class="px-4 py-2 bg-secondary hover:bg-secondary-hover text-white rounded-xs cursor-pointer transition duration-200"
          type="button"
          @click="closeCropper"
        >
          Отмена
        </button>
        <button
          class="px-4 py-2 bg-primary hover:bg-primary-hover transition duration-200 text-white rounded-xs cursor-pointer"
          type="button"
          @click="applyCrop"
        >
          Обрезать и загрузить
        </button>
      </div>
    </div>
  </div>
</template>


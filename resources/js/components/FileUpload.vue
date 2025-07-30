<script setup>
import { FileText, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
	modelValue: {
		type: File,
		default: null,
	},
});

const emit = defineEmits(['update:modelValue', 'error']);

const dragOver = ref(false);

const handleFileSelect = (event) => {
	const target = event.target;
	if (target.files && target.files.length > 0) {
		handleFile(target.files[0]);
	}
};

const handleFileDrop = (event) => {
	event.preventDefault();
	dragOver.value = false;

	if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
		handleFile(event.dataTransfer.files[0]);
	}
};

const handleFile = (file) => {
	// Validate file type
	const allowedTypes = ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

	if (!allowedTypes.includes(file.type) && !file.name.match(/\.(doc|docx)$/i)) {
		emit('error', 'Пожалуйста, выберите действительный документ Word (.doc или .docx)');
		return;
	}

	// Validate file size (10MB)
	if (file.size > 10 * 1024 * 1024) {
		emit('error', 'Размер файла должен быть меньше 10МБ');
		return;
	}

	emit('update:modelValue', file);
	emit('error', null);
};

const removeFile = () => {
	emit('update:modelValue', null);
	emit('error', null);
};
</script>

<template>
	<div class="flex flex-col gap-4">
		<!-- File Upload Area -->
		<div
			v-if="!modelValue"
			class="relative min-h-[120px] rounded-lg border-2 border-dashed p-4 text-center transition-colors sm:min-h-[160px] sm:p-8"
			:class="dragOver ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600'"
			@dragover.prevent="dragOver = true"
			@dragleave.prevent="dragOver = false"
			@drop="handleFileDrop"
		>
			<Upload class="mx-auto h-8 w-8 text-gray-400 sm:h-12 sm:w-12" />
			<h3 class="mt-2 text-base font-medium text-gray-900 sm:mt-4 sm:text-lg dark:text-white">Загрузить документ Word</h3>
			<p class="mt-1 px-2 text-xs text-gray-500 sm:mt-2 sm:text-sm dark:text-gray-400">
				<span class="hidden sm:inline">Перетащите ваш документ Word сюда или нажмите для выбора файла</span>
				<span class="sm:hidden">Нажмите для выбора файла</span>
			</p>
			<p class="mt-1 px-2 text-xs text-gray-400 dark:text-gray-500">Поддерживаются файлы .doc и .docx размером до 10МБ</p>
			<input
				type="file"
				class="absolute inset-0 h-full w-full cursor-pointer touch-manipulation opacity-0"
				accept=".doc,.docx"
				@change="handleFileSelect"
			/>
		</div>

		<!-- Selected File Display -->
		<div v-else class="rounded-lg border-2 border-gray-200 p-4 dark:border-gray-700">
			<div class="flex items-center justify-between">
				<div class="flex items-center">
					<FileText class="h-8 w-8 text-blue-500" />
					<div class="ml-3">
						<p class="text-sm font-medium text-gray-900 dark:text-white">{{ modelValue.name }}</p>
						<p class="text-xs text-gray-500 dark:text-gray-400">{{ (modelValue.size / 1024 / 1024).toFixed(2) }} MB</p>
					</div>
				</div>
				<button @click="removeFile" class="p-1 text-gray-400 transition-colors hover:text-red-500">
					<X class="h-5 w-5" />
				</button>
			</div>
		</div>
	</div>
</template>

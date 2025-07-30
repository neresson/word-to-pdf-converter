<script setup>
import ConvertButton from '@/components/ConvertButton.vue';
import ErrorDisplay from '@/components/ErrorDisplay.vue';
import FileUpload from '@/components/FileUpload.vue';
import Instructions from '@/components/Instructions.vue';
import TextReplacements from '@/components/TextReplacements.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const selectedFile = ref(null);
const textReplacements = ref([
	{
		id: Date.now().toString(),
		search: '',
		replace: '',
	},
]);
const isConverting = ref(false);
const error = ref(null);

const handleFileUpdate = (file) => {
	selectedFile.value = file;
};

const handleFileError = (errorMessage) => {
	error.value = errorMessage;
};

const convertToPdf = async () => {
	if (!selectedFile.value) {
		error.value = 'Сначала выберите документ Word';
		return;
	}

	isConverting.value = true;
	error.value = null;

	try {
		const formData = new FormData();
		formData.append('file', selectedFile.value);

		if (textReplacements.value.length > 0) {
			formData.append('text_replacements', JSON.stringify(textReplacements.value));
		}

		const response = await fetch(route('word-to-pdf.convert'), {
			method: 'POST',
			body: formData,
			headers: {
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
			},
		});

		if (!response.ok) {
			const errorData = await response.json();
			throw new Error(errorData.error || 'Ошибка конвертации');
		}

		const blob = await response.blob();
		const url = window.URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = selectedFile.value.name.replace(/\.(doc|docx)$/i, '.pdf');
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		window.URL.revokeObjectURL(url);
	} catch (err) {
		error.value = err instanceof Error ? err.message : 'Произошла ошибка во время конвертации';
	} finally {
		isConverting.value = false;
	}
};
</script>

<template>
	<Head title="Конвертер Word в PDF">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<link rel="preconnect" href="https://rsms.me/" />
		<link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
		<meta name="csrf-token" :content="$page.props.csrfToken || ''" />
	</Head>
	<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
		<header class="container mx-auto px-4 py-3 sm:px-6 sm:py-4">
			<h1 class="text-xl font-bold text-gray-900 sm:text-2xl dark:text-white text-center">Конвертер Word в PDF</h1>
		</header>

		<main class="container mx-auto max-w-4xl px-4 py-4 sm:px-6 sm:py-8">
			<Card>
				<CardHeader>
					<CardTitle>Загрузить документ Word</CardTitle>
				</CardHeader>
				<CardContent class="space-y-6">
					<ErrorDisplay :error="error" />

					<FileUpload v-model="selectedFile" @error="handleFileError" />
				</CardContent>
			</Card>

			<Card class="mt-6">
				<CardHeader>
					<CardTitle>Замена текста</CardTitle>
					<CardDescription> Замените любой текст в вашем документе используя переменные или ключевые слова. </CardDescription>
				</CardHeader>
				<CardContent>
					<TextReplacements v-model="textReplacements" />
				</CardContent>
			</Card>

			<Card class="mt-6">
				<CardContent class="p-4 sm:p-6">
					<ConvertButton :disabled="!selectedFile" :loading="isConverting" @convert="convertToPdf" />
				</CardContent>
			</Card>

			<Card class="mt-6 sm:mt-8">
				<CardHeader class="pb-3 sm:pb-6">
					<CardTitle class="text-base sm:text-lg">Как использовать:</CardTitle>
				</CardHeader>
				<CardContent class="pt-0">
					<Instructions />
				</CardContent>
			</Card>
		</main>
	</div>
</template>

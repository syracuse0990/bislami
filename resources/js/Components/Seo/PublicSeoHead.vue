<script setup>
import { toAbsoluteUrl } from '@/Support/seo';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    canonicalUrl: {
        type: String,
        required: true,
    },
    imageUrl: {
        type: String,
        default: '/images/bizlami_icon.png',
    },
    type: {
        type: String,
        default: 'website',
    },
    structuredData: {
        type: [Array, Object],
        default: () => [],
    },
});

const page = usePage();

const siteName = computed(() => page.props.seo?.siteName ?? 'BizLami');
const appUrl = computed(() => page.props.seo?.appUrl ?? '');
const documentTitle = computed(() => `${props.title} | ${siteName.value}`);
const canonicalHref = computed(() => toAbsoluteUrl(appUrl.value, props.canonicalUrl));
const imageHref = computed(() => toAbsoluteUrl(appUrl.value, props.imageUrl));
const structuredDataEntries = computed(() => {
    const entries = Array.isArray(props.structuredData)
        ? props.structuredData
        : props.structuredData
            ? [props.structuredData]
            : [];

    return entries.map((entry) => JSON.stringify(entry));
});
</script>

<template>
    <Head :title="title">
        <meta head-key="meta-description" name="description" :content="description">
        <meta head-key="meta-robots" name="robots" content="index,follow">
        <link v-if="canonicalHref" head-key="canonical" rel="canonical" :href="canonicalHref">

        <meta head-key="og:title" property="og:title" :content="documentTitle">
        <meta head-key="og:description" property="og:description" :content="description">
        <meta head-key="og:type" property="og:type" :content="type">
        <meta v-if="canonicalHref" head-key="og:url" property="og:url" :content="canonicalHref">
        <meta head-key="og:site_name" property="og:site_name" :content="siteName">
        <meta v-if="imageHref" head-key="og:image" property="og:image" :content="imageHref">

        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image">
        <meta head-key="twitter:title" name="twitter:title" :content="documentTitle">
        <meta head-key="twitter:description" name="twitter:description" :content="description">
        <meta v-if="imageHref" head-key="twitter:image" name="twitter:image" :content="imageHref">

        <component
            :is="'script'"
            v-for="(entry, index) in structuredDataEntries"
            :key="`structured-data-${index}`"
            :head-key="`structured-data-${index}`"
            type="application/ld+json"
            v-text="entry"
        />
    </Head>
</template>
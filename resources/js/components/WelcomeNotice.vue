<template>
    <div v-show="!is_hidden" class="z-900 bg-white rounded-lg p-4 absolute top-4 left-16 mr-4">
        <button @click="is_hidden = true" type="button" class="float-right p-2 -mr-2 -mt-2">
            <span class="sr-only">Close</span>

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <p class="font-bold mb-2">Welcome to Property Heatmap!</p>
        <p class="mb-2">This is a hastily-made prototype to replace the previous version that broke. It's now open-source!</p>
        <p>Click the map to find the average five-year price for that area. You can zoom in to the postcode level.</p>
    </div>
</template>

<script>
export default {
    data() {
        return {
            is_hidden: false,
        };
    },
};
</script>
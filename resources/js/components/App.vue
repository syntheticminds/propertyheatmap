<template>
    <div>
        <div ref="map" class="w-screen h-screen bg-black!"></div>

        <WelcomeNotice />
    </div>
</template>

<script>
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import HeatmapLayer from '@/HeatmapLayer.js';
import WelcomeNotice from '@/components/WelcomeNotice.vue';

export default {
    components: {
        WelcomeNotice,
    },
    data() {
        return {
            map: null,
            heatmap_layer: null,
        };
    },
    mounted() {
        var api_key = import.meta.env.VITE_STADIAMAPS_API_KEY;

        this.map = new L.Map(this.$refs.map, {
            attributionControl: false,
            layers: [
                L.tileLayer('   https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    minZoom: 0,
                    maxZoom: 20,
                    type: 'png'
                }),
            ],
        });

        this.map.fitBounds([[49.82, -5.91], [56.35, 2.24]]);

        this.heatmap_layer = new HeatmapLayer();
        this.heatmap_layer.addTo(this.map);

        this.map.on('click', (event) => {
            const closest = this.heatmap_layer.closestTo(event.latlng);

            let content = '<h4 class="text-xl mb-2">' + closest.id + (closest.description ? ' <small>' + closest.description + '</small>' : '') + '</h4>';
            content += '<p class="m-0!"><strong>Average price:</strong> ' + new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 0 }).format(closest.five_year_average) + '</p>';
            content += '<p class="m-0!"><strong>Number of sales:</strong> ' + new Intl.NumberFormat().format(closest.five_year_count) + '</p>';

            L.popup()
                .setLatLng(event.latlng)
                .setContent(content)
                .openOn(this.map);
        });

        this.map.on('zoomstart', () => this.map.closePopup())
    },
};
</script>

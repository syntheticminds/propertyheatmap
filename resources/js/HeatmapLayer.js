import L from 'leaflet';
import { GPU } from 'gpu.js';
import { mande } from 'mande';

export default L.HeatmapLayer = L.Layer.extend({
    _results: [],
    initialize() {
        this._canvas = L.DomUtil.create('canvas', 'heatmap-layer');
        this._canvas.classList.add('transition-opacity', 'duration-1000', 'pointer-events-none');

        const gpu = new GPU({
            canvas: this._canvas,
        });

        this._renderer = gpu.createKernel(function (input, length) {
            let weighted = 0;
            let total = 0;

            for (let i = 0; i < length; i++) {
                const a = input[i][0] - this.thread.x;
                const b = input[i][1] - (this.constants.height - this.thread.y);

                const distance = Math.sqrt((a * a) + (b * b));
                const weight = distance === 0 ? 1000000 : 1 / Math.pow(distance, 5);

                weighted += weight * input[i][2];
                total += weight;
            }

            const score = total > 0 ? weighted / total : 0;

            const scale = 10;
            const log_score = Math.log1p(scale * score) / Math.log1p(scale);

            this.color(log_score, 0, 1 - log_score);
        }, {
            graphical: true,
            dynamicOutput: true,
            dynamicArguments: true
        });
    },
    onAdd(map) {
        this._map = map;

        this._map.getPane('tilePane').classList.add('grayscale');
        this._map.getPane('overlayPane').classList.add('mix-blend-multiply');
        this._map.getPane('overlayPane').classList.add('opacity-66');
        this._map.getPane('mapPane').classList.add('contrast-150');

        this._canvas.classList.add('opacity-0');

        this.getPane().appendChild(this._canvas);

        this._resize();
    },
    getEvents() {
        return {
            movestart: this._clear,
            moveend: this._update,
            resize: this._resize,
            zoom: this._clear,
            zoomend: this._update,
        };
    },
    _clear() {
        const radius = Math.min(this._canvas.width, this._canvas.height) * 0.25;

        this._canvas.style.filter = 'blur(' + radius + 'px)';

        this._canvas.classList.remove('opacity-100');
        this._canvas.classList.add('opacity-0');
    },
    _resize() {
        const size = this._map.getSize();

        this._canvas.width = size.x;
        this._canvas.height = size.y;

        this._renderer.setOutput([size.x, size.y]);
        this._renderer.setConstants({height: size.y});

        this._update();
    },
    async _update() {
        // TODO Working nicely but we need to debounce updates from stuff like resize.

        const bounds = this._map.getBounds();

        this._results = await mande('/api/query')
            .post({
                north: bounds.getNorth(),
                east: bounds.getEast(),
                south: bounds.getSouth(),
                west: bounds.getWest(),
            });

        const values = this._results.map(result => result.five_year_average);
        const minimum_value = Math.min(...values);
        const maximum_value = Math.max(...values);

        const data = this._results.map(result => {
            const point = this._map.latLngToContainerPoint(L.latLng(result.latitude, result.longitude));
            
            return [
                point.x,
                point.y,
                (result.five_year_average - minimum_value) / (maximum_value - minimum_value),
            ];
        });

        if (!data.length) {
            return;
        }

        const top_left = this._map.latLngToLayerPoint(bounds.getNorthWest());

        L.DomUtil.setPosition(this._canvas, top_left);

        this._renderer(data, data.length);

        this._canvas.style.filter = '';
        this._canvas.classList.remove('opacity-0');
        this._canvas.classList.add('opacity-100');
    },
    closestTo(latlng) {
        const closest = this._results.reduce((carry, item) => {
            const distance = latlng.distanceTo(L.latLng(item.latitude, item.longitude));

            if (distance < carry.distance) {
                carry = {distance: distance, result: item};
            }

            return carry;
        }, {distance: Infinity, result: null});

        return closest.result;
    }
});

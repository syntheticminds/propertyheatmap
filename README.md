# Property Heatmap
Property analytics for England and Wales.

This is a prototype I have been working on for some time. It's far from complete. The map for the original version kept hitting Stadia's usage limits.

## Requirements
* PHP >= 8.2
* SQLite >= 3.26
* 10GB storage space

## Installation
1. `composer install`
2. `npm install`
3. `cp .env.example .env` and configure accordingly.
4. `php artisan app:bootstrap`

# Notes
* SQLite is bloody fast, if you treat it well.
* Needs further benchmarking but it seems query performance is improved by the following factors in this order: indexes, queries and then settings.
* Having spent the evening learning R*-trees, it a simple index is just as fast. This is probably owing to it being a covered query instead of a join. It could also be that by using single points instead of rectangles, it's not gaining any advantage. Do R*-trees only get good if the boxes are nested?
* A composite key seems to be marginally faster than an index on latitude only.

# Todos
* Debounce updates!
* Animate blur
* Scale heatmap layer nicely when zooming.
* Resurect the statistics pages.
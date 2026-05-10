(function () {
    'use strict';

    const Adminz_map = {
        init() {
            window.addEventListener('resize', () => this.onWindowResize());
            document.addEventListener('DOMContentLoaded', () => this.onDOMContentLoaded());
            document.addEventListener('adminz_map_initmap', (event) => this.adminz_map_initmap(event));
            document.addEventListener('adminz_map_focus_item', (event) => this.adminz_map_focus_item(event));
        },

        onWindowResize() {
            // Something here
        },

        onDOMContentLoaded() {
            // 
        },

        // ---------------- Your custom event here ---------------- //

        adminz_map_focus_item(event) {
            console.log("___adminz_map_focus_item"); 
            const id = event.detail.context.element.id;
            const latlng = event.detail.latlng;
            if (this[id].markerGroup) {
                for (const marker of this[id].markerGroup) {
                    if (
                        marker.getLatLng().lat === latlng.lat &&
                        marker.getLatLng().lng === latlng.lng
                    ) {
                        this[id].map.panTo(latlng, 10);
                        setTimeout(() => {
                            marker.openPopup(); // Đảm bảo gọi đúng phương thức mở popup
                        }, 300);
                    }
                }
            }
        },

        adminz_map_initmap(event) {
            // console.log("adminz_map_initmap"); 
            // init data
            const id = event.detail.context.element.id;

            // make sure initial
            if (!this[id]) {
                this[id] = {};
            }

            const __itemsFound = event.detail.context.__itemsFound;
            const __items = event.detail.context.__items;

            // get items
            let mapItems = [];
            for (let i = 0; i < __items.length; i++) {
                if (__itemsFound.includes(__items[i].id)) {
                    mapItems.push(__items[i]);
                }
            }

            this[id].mapItems = mapItems;
            this[id].element = event.detail.context.map;
            this.initMap(event, event.detail.auto_focus);
        },

        initMap(event, auto_focus) {
            const id = event.detail.context.element.id;

            /* Default center coordinates */
            let center_lat = 0;
            let center_lng = 0;

            /* Calculate center lat lng */
            let map_items = this[id].mapItems;
            if (map_items.length) {
                for (let i = 0; i < map_items.length; i++) {
                    // let latLngParts = map_items[i].latlong.split(", ");
                    let latLngParts = map_items[i].latlong.split(",").map(s => s.trim());
                    let float_lat = parseFloat(latLngParts[0]);
                    let float_lng = parseFloat(latLngParts[1]);
                    center_lat += float_lat;
                    center_lng += float_lng;
                }
                center_lat /= map_items.length;
                center_lng /= map_items.length;
            }

            // data map
            const dataMap = JSON.parse(this[id].element.closest('.adminz_map').getAttribute('data-map'));

            // Map options
            let options = {
                zoom: parseInt(dataMap.mapzoom),
                center: [center_lat, center_lng],
                mapTypeControl: false,
                mapId: this[id].element.id
            };
            console.log('Map options:', options); 

            // Clear current markers
            if (this[id].markerGroup) {
                this[id].markerGroup.forEach(marker => this[id].map.removeLayer(marker));
            } else {
                this[id].markerGroup = [];
            }

            // Create map if it doesn't exist
            if (!this[id].map) {
                this[id].map = L.map(this[id].element, options);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(this[id].map);
            }

            // Move to center for multiple time init 
            if (center_lat && center_lng) {
                this[id].map.panTo({ lat: center_lat, lng: center_lng }, 12);
            }

            // Parse data and add new markers
            this[id].markerGroup = [];
            for (const item of map_items) {
                let iconSize = item.is_custom_marker ? [40, 40] : [28, 39];
                let iconAnchor = item.is_custom_marker ? [20, 0] : [14, 0];

                var icon = L.icon({
                    iconUrl: item.marker,
                    iconSize: iconSize,
                    iconAnchor: iconAnchor,
                });

                var [lat, lng] = item.latlong.split(",").map(s => parseFloat(s.trim()));
                var marker = L.marker([lat, lng], { icon: icon });


                var infoboxContent = ` <div> <h5 class="uppercase">${item.title}</h5> <div style="max-width: 150px">${item.description}</div> </div> `;

                // Bind popup to marker
                marker.bindPopup(infoboxContent);
                marker.itemData = item;
                marker.addTo(this[id].map);

                // save markers
                this[id].markerGroup.push(marker);
            }

            // focus
            // khi length = 1 
            // hoặc auto_focus và có prop là popup = true

            for (let i = 0; i < this[id].markerGroup.length; i++) {
                if (
                    (this[id].markerGroup[i].itemData.popup === 'true' && auto_focus) || 
                    (this[id].markerGroup.length === 1)
                ){
                    const _marker = this[id].markerGroup[i];
                    this[id].map.panTo(_marker.getLatLng(), 10);
                    setTimeout(() => {
                        _marker.openPopup();
                    }, 300);
                }
            }
        }
    }

    Adminz_map.init();
    window.Adminz_map = Adminz_map;
})();

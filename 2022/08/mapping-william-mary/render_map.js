const map = L.map('map').setView([37.271,-436.712], 17);

const new_wm_map = L.tileLayer('new_wm_map/{z}/{x}/{y}.png', {
    minZoom: 15,
    maxZoom: 19,
    attribution: '© OpenStreetMap'
}).addTo(map);

var old_wm_map = L.tileLayer('old_wm_map/{z}/{x}/{y}.png', {
    minZoom: 15,
    maxZoom: 19,
    attribution: '© OpenStreetMap'
}).addTo(map);

const compare = L.control.compare([old_wm_map], [new_wm_map]).addTo(map);

import React, { useMemo, useRef, useEffect } from 'react';
import { MapContainer, TileLayer, Marker, useMapEvents } from 'react-leaflet';
import L from 'leaflet';

const pickerIcon = new L.Icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

// Capture map click events to place the pin
function MapClickHandler({ onClick }) {
  useMapEvents({
    click(e) {
      onClick([e.latlng.lat, e.latlng.lng]);
    },
  });
  return null;
}

export default function LocationPicker({ value, onChange, height = '250px' }) {
  const markerRef = useRef(null);
  const center = value ? [value.latitude, value.longitude] : [43.238949, 76.889709]; // Almaty default

  const eventHandlers = useMemo(
    () => ({
      dragend() {
        const marker = markerRef.current;
        if (marker != null) {
          const latLng = marker.getLatLng();
          onChange({ latitude: latLng.lat, longitude: latLng.lng });
        }
      },
    }),
    [onChange]
  );

  const handleMapClick = (latLngArray) => {
    onChange({ latitude: latLngArray[0], longitude: latLngArray[1] });
  };

  return (
    <div style={{ height, width: '100%', position: 'relative', borderRadius: '16px', overflow: 'hidden' }}>
      <MapContainer 
        center={center} 
        zoom={13} 
        style={{ height: '100%', width: '100%' }}
        zoomControl={false}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
          url="https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png"
        />
        
        <MapClickHandler onClick={handleMapClick} />

        {value && (
          <Marker
            draggable={true}
            eventHandlers={eventHandlers}
            position={[value.latitude, value.longitude]}
            icon={pickerIcon}
            ref={markerRef}
          />
        )}
      </MapContainer>
    </div>
  );
}

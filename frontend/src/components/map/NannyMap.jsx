import React, { useEffect, useMemo, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { MapContainer, TileLayer, Marker, Popup, Circle, useMap, useMapEvents } from 'react-leaflet';
import L from 'leaflet';

const userIcon = new L.Icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

const nannyIcon = new L.Icon({
  iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-cyan.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

function ChangeMapView({ center }) {
  const map = useMap();
  useEffect(() => {
    if (center) {
      map.setView(center, map.getZoom());
    }
  }, [center, map]);
  return null;
}

function MapClickHandler({ onClick }) {
  useMapEvents({
    click(e) {
      onClick({ latitude: e.latlng.lat, longitude: e.latlng.lng });
    },
  });
  return null;
}

const createNannyAvatarIcon = (avatarUrl, firstName) => {
  const innerContent = avatarUrl
    ? `<img src="${avatarUrl}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2.5px solid #FF7A59;box-shadow:0 3px 8px rgba(0,0,0,0.35);" />`
    : `<div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg, #FF7A59 0%, #FF5252 100%);color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:15px;border:2px solid #ffffff;box-shadow:0 3px 8px rgba(0,0,0,0.35);">${firstName?.[0] || 'Н'}</div>`;

  return L.divIcon({
    className: 'custom-nanny-avatar-marker',
    html: `<div style="cursor:pointer;transform:translate(-50%, -50%);">${innerContent}</div>`,
    iconSize: [40, 40],
    iconAnchor: [20, 20],
    popupAnchor: [0, -22],
  });
};

export default function NannyMap({ userLocation, radiusKm = 2, nannies = [], onNannyClick, onUserLocationChange, height = '300px' }) {
  const { t } = useTranslation();
  const center = userLocation ? [userLocation.latitude, userLocation.longitude] : [43.238949, 76.889709];
  const markerRef = useRef(null);

  const eventHandlers = useMemo(
    () => ({
      dragend() {
        const marker = markerRef.current;
        if (marker != null && onUserLocationChange) {
          const latLng = marker.getLatLng();
          onUserLocationChange({ latitude: latLng.lat, longitude: latLng.lng });
        }
      },
    }),
    [onUserLocationChange]
  );

  return (
    <div style={{ height, width: '100%', position: 'relative', overflow: 'hidden' }}>
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
        
        <ChangeMapView center={center} />
        {onUserLocationChange && <MapClickHandler onClick={onUserLocationChange} />}

        {userLocation && (
          <>
            <Marker 
              position={[userLocation.latitude, userLocation.longitude]} 
              icon={userIcon}
              draggable={true}
              eventHandlers={eventHandlers}
              ref={markerRef}
            >
              <Popup>Вы здесь (перетащите маркер) / Сіз осындасыз</Popup>
            </Marker>
            <Circle
              center={[userLocation.latitude, userLocation.longitude]}
              radius={radiusKm * 1000}
              pathOptions={{
                color: '#6C5CE7',
                fillColor: '#6C5CE7',
                fillOpacity: 0.12,
                weight: 2
              }}
            />
          </>
        )}

        {nannies.map((nanny) => {
          if (!nanny.latitude || !nanny.longitude) return null;
          const icon = createNannyAvatarIcon(nanny.avatar_url, nanny.first_name);
          return (
            <Marker 
              key={nanny.id} 
              position={[nanny.latitude, nanny.longitude]} 
              icon={icon}
              eventHandlers={{
                click: () => onNannyClick && onNannyClick(nanny),
              }}
            >
              <Popup>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', padding: '2px 0' }}>
                  {nanny.avatar_url ? (
                    <img src={nanny.avatar_url} alt="" style={{ width: '32px', height: '32px', borderRadius: '50%', objectFit: 'cover' }} />
                  ) : (
                    <div style={{ width: '32px', height: '32px', borderRadius: '50%', background: '#FF7A59', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 'bold' }}>
                      {nanny.first_name?.[0]}
                    </div>
                  )}
                  <div>
                    <div style={{ color: '#2C3E50', fontWeight: 'bold', fontSize: '0.9rem', lineHeight: 1.2 }}>
                      {nanny.first_name} {nanny.last_name}
                    </div>
                    <div style={{ color: '#FF7A59', fontWeight: '700', fontSize: '0.82rem' }}>
                      {nanny.effective_hourly_rate || nanny.hourly_rate} {t('common.per_hour')}
                    </div>
                  </div>
                </div>
              </Popup>
            </Marker>
          );
        })}
      </MapContainer>
    </div>
  );
}

const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/leaflet-CLLlcC51.css"])))=>i.map(i=>d[i]);
import{_ as ne,r as p,u as oe,o as le,c as ie,a as se,b as ue,d as W,n as pe}from"./app-BXkoNELi.js";function ce(s){s&&(delete s.Icon.Default.prototype._getIconUrl,s.Icon.Default.mergeOptions({iconRetinaUrl:"https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png",iconUrl:"https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png",shadowUrl:"https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png"}))}const f="42fd1052b23ee1f0ad1fe09ac2357b41",de=(s=2)=>{const c=new Date;return c.setUTCDate(c.getUTCDate()-Math.max(0,s)),c.toISOString().split("T")[0]};function me(s){if(!s)throw new Error("Leaflet instance is required before creating map layers.");const c=de(2),b={"🗺️ Street Map":s.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"© OpenStreetMap contributors",maxZoom:19}),"🛰️ Satellite View":s.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",{attribution:"© Esri",opacity:.8,maxZoom:19}),"🌍 NASA True Color":s.tileLayer(`https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/VIIRS_SNPP_CorrectedReflectance_TrueColor/default/${c}/GoogleMapsCompatible_Level9/{z}/{y}/{x}.jpg`,{attribution:"Imagery © NASA EOSDIS GIBS",maxZoom:9,minZoom:2,opacity:.9,noWrap:!0,bounds:[[-85.05112878,-180],[85.05112878,180]]})},d={"☁️ Live Clouds":s.tileLayer(`https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png?appid=${f}`,{attribution:"© OpenWeatherMap",opacity:.95,maxZoom:12,className:"enhanced-clouds-layer"}),"🌧️ Live Precipitation":s.tileLayer(`https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=${f}`,{attribution:"© OpenWeatherMap",opacity:.95,maxZoom:12,className:"enhanced-precipitation-layer"}),"🌡️ Live Temperature":s.tileLayer(`https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png?appid=${f}`,{attribution:"© OpenWeatherMap",opacity:.95,maxZoom:12,className:"enhanced-temperature-layer"}),"💨 Live Wind":s.tileLayer(`https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid=${f}`,{attribution:"© OpenWeatherMap",opacity:.95,maxZoom:12,className:"enhanced-wind-layer"}),"🌪️ Pressure Systems":s.tileLayer(`https://tile.openweathermap.org/map/pressure_new/{z}/{x}/{y}.png?appid=${f}`,{attribution:"© OpenWeatherMap",opacity:.95,maxZoom:12,className:"enhanced-pressure-layer"})};return{baseLayers:b,overlayLayers:d}}function ye(){const s=document.createElement("style");s.textContent=`
    .enhanced-clouds-layer,
    .enhanced-precipitation-layer,
    .enhanced-temperature-layer,
    .enhanced-wind-layer,
    .enhanced-pressure-layer {
      z-index: 400 !important;
      pointer-events: none;
    }
    
    .enhanced-clouds-layer {
      filter: contrast(2.2) brightness(1.5) saturate(1.8) hue-rotate(200deg) sepia(0.4);
      mix-blend-mode: screen;
      opacity: 1.0 !important;
    }
    
    .enhanced-precipitation-layer {
      filter: contrast(2.4) brightness(1.4) saturate(2.0) hue-rotate(240deg) sepia(0.5);
      mix-blend-mode: multiply;
      opacity: 1.0 !important;
    }
    
    .enhanced-temperature-layer {
      filter: contrast(2.1) brightness(1.6) saturate(2.0) hue-rotate(-30deg) sepia(0.6);
      mix-blend-mode: overlay;
      opacity: 1.0 !important;
    }
    
    .enhanced-wind-layer {
      filter: contrast(2.3) brightness(1.7) saturate(1.9) hue-rotate(150deg) sepia(0.4);
      mix-blend-mode: soft-light;
      opacity: 1.0 !important;
    }
    
    .enhanced-pressure-layer {
      filter: contrast(2.0) brightness(1.5) saturate(1.7) hue-rotate(120deg) sepia(0.5);
      mix-blend-mode: overlay;
      opacity: 1.0 !important;
    }
    
    .leaflet-control-layers {
      box-shadow: none !important;
      border: none !important;
      background: transparent !important;
    }
    
    .leaflet-control-layers label {
      color: white !important;
      font-weight: 500 !important;
      padding: 8px 0 !important;
      cursor: pointer !important;
    }
    
    .leaflet-container {
      cursor: crosshair !important;
    }
  `,document.head.appendChild(s)}const ve={class:"weather-map-container"},ge={__name:"WeatherMap",emits:["map-click","map-ready","location-update"],setup(s,{expose:c,emit:b}){const d=b,m=p(null),t=p(null),y=p(null),w=p({}),h=p({}),x=p(null),_=p(null),v=p("weather"),n=p(null),g=p(null),z=p(null),T=p("street"),P=p(new Set),{formatTemperature:A,formatWindSpeed:D}=oe();let u=null,M=null;const $=async()=>u||(M||(M=Promise.all([W(()=>import("./leaflet-src-jreTq87Q.js").then(e=>e.l),[]),W(()=>Promise.resolve({}),__vite__mapDeps([0]))]).then(([e])=>(u=e.default??e,ce(u),u))),M),S={street:"🗺️ Street Map",satellite:"🛰️ Satellite View",nasa:"🌍 NASA True Color"},V=e=>Number.isFinite(e)?Math.max(-90,Math.min(90,e)):null,j=e=>Number.isFinite(e)?((e+180)%360+360)%360-180:null,O=e=>{if(!t.value||!h.value)return;const r=S[e]?e:"street",a=S[r],o=h.value[a];if(!o||z.value===r&&t.value.hasLayer(o))return;const i=Array.from(P.value).map(l=>({name:l,layer:w.value[l]})).filter(l=>l.layer&&t.value.hasLayer(l.layer));Object.values(h.value).forEach(l=>{t.value.hasLayer(l)&&t.value.removeLayer(l)}),o.addTo(t.value),z.value=r,i.forEach(({layer:l})=>{l&&!t.value.hasLayer(l)&&t.value.addLayer(l)})},F=e=>{T.value=S[e]?e:"street",!(!t.value||!Object.keys(h.value).length)&&O(T.value)},Z=async()=>{if(await $(),await pe(),!m.value)return;m.value.style.width="100%",m.value.style.height="100%",m.value.style.minHeight="100vh",t.value=u.map(m.value,{zoomControl:!1,attributionControl:!0}).setView([7.5,124.5],7),u.control.zoom({position:"bottomright"}).addTo(t.value),t.value.on("click",a=>{H(a.latlng)});const{baseLayers:e,overlayLayers:r}=me(u);h.value=e,w.value=r,O(T.value),x.value=u.layerGroup().addTo(t.value),_.value=u.layerGroup().addTo(t.value),ye(),setTimeout(()=>{t.value&&(t.value.invalidateSize(),d("map-ready"))},100)},R=(e,r)=>{if(!t.value)return;const o={clouds:"☁️ Live Clouds",precipitation:"🌧️ Live Precipitation",temperature:"🌡️ Live Temperature",wind:"💨 Live Wind",pressure:"🌪️ Pressure Systems"}[e],i=w.value[o];i&&(r?(t.value.hasLayer(i)&&t.value.removeLayer(i),t.value.addLayer(i),P.value.add(o)):(t.value.removeLayer(i),P.value.delete(o)))},U=(e,r,a=10)=>{t.value&&t.value.setView([e,r],a,{animate:!1})},G=(e,r,a)=>{if(!t.value)return;y.value?y.value.setLatLng([e,r]):(y.value=u.marker([e,r],{draggable:!0}).addTo(t.value),y.value.on("dragend",()=>{const{lat:i,lng:l}=y.value.getLatLng();d("location-update",{lat:i,lon:l})}));const o=q(e,r,a);y.value.bindPopup(o).openPopup(),d("location-update",{lat:e,lon:r})},q=(e,r,a)=>{const o=A(a.main.temp,{decimals:0}),i=D(a.wind.speed,{decimals:1});return`
    <div style="text-align: center; min-width: 200px; font-family: Arial, sans-serif;">
      <h3 style="margin: 0 0 10px 0; color: #333;">${a.name}</h3>
      <div style="font-size: 28px; font-weight: bold; margin: 10px 0; color: #2563eb;">
        ${o}
      </div>
      <div style="text-transform: capitalize; margin-bottom: 10px; color: #666; font-size: 16px;">
        ${a.weather[0].description}
      </div>
      <div style="font-size: 12px; color: #888; line-height: 1.5;">
        <div>💧 Humidity: ${a.main.humidity}%</div>
        <div>💨 Wind: ${i}</div>
        <div>📊 Pressure: ${a.main.pressure} mb</div>
      </div>
    </div>
  `},H=({lat:e,lng:r})=>{if(n.value){Y(e,r);return}if(g.value){ae(e,r);return}const a=V(e),o=j(r);if(a===null||o===null){console.warn("Ignoring map click: invalid coordinates",{lat:e,lng:r});return}d("map-click",{lat:a,lng:o})},J=({farmFeatures:e=[],pointFeatures:r=[]})=>{t.value&&(x.value.clearLayers(),_.value.clearLayers(),e.length&&u.geoJSON({type:"FeatureCollection",features:e},{style:a=>({color:E(a.properties?.farm_id),weight:a.properties?.type==="centroid"?0:2,opacity:.8,fillOpacity:.15}),pointToLayer:(a,o)=>u.circleMarker(o,{radius:6,color:E(a.properties?.farm_id),fillOpacity:.9}),onEachFeature:(a,o)=>{if(!a?.properties)return;const{farm_name:i,size_hectares:l,soil_type:L,description:I}=a.properties,re=`
          <div style="min-width: 220px;">
            <h3 style="margin: 0 0 8px 0; color: #1e40af;">${i??"Unnamed Farm"}</h3>
            ${l?`<div><strong>Size:</strong> ${Number(l).toFixed(2)} ha</div>`:""}
            ${L?`<div><strong>Soil:</strong> ${L}</div>`:""}
            ${I?`<div style="margin-top: 6px;">${I}</div>`:""}
          </div>
        `;o.bindPopup(re)}}).addTo(x.value),r.length&&u.geoJSON({type:"FeatureCollection",features:r},{pointToLayer:(a,o)=>u.marker(o,{icon:u.divIcon({className:"farm-point-icon",html:`<div class="farm-point-marker">${a.properties?.label?.[0]??"P"}</div>`})}),onEachFeature:(a,o)=>{const{label:i,point_type:l}=a.properties??{},L=`
          <div style="min-width:180px;">
            <strong>${i??"Point"}</strong>
            ${l?`<div style="margin-top:4px;">Type: ${l}</div>`:""}
          </div>
        `;o.bindPopup(L)}}).addTo(_.value))},K=({initialCoordinates:e=[],onComplete:r,onCancel:a})=>{if(!t.value)return null;v.value="boundary",C();const o=e.map(l=>[l.lat,l.lng]),i=u.polygon(o,{color:"#2563eb",weight:2,opacity:.9,fillOpacity:.15});return n.value={points:[...o],polygonLayer:i,onComplete:r,onCancel:a},o.length&&i.addTo(t.value),{finish:k,cancel:N,reset:()=>B(e)}},Y=(e,r)=>{n.value&&(n.value.points.push([e,r]),t.value&&(t.value.hasLayer(n.value.polygonLayer)||n.value.polygonLayer.addTo(t.value),n.value.polygonLayer.setLatLngs([n.value.points])))},Q=()=>{!n.value||!n.value.points.length||(n.value.points.pop(),t.value&&(n.value.points.length<3?t.value.hasLayer(n.value.polygonLayer)&&t.value.removeLayer(n.value.polygonLayer):(n.value.polygonLayer.setLatLngs([n.value.points]),t.value.hasLayer(n.value.polygonLayer)||n.value.polygonLayer.addTo(t.value))))},X=()=>n.value?.points?.length??0,k=()=>{if(!n.value)return;const{points:e,onComplete:r}=n.value;if(e.length<3){alert("A boundary requires at least three points.");return}const a=e.map(([o,i])=>({lat:o,lng:i}));r&&r(a),C(),v.value="weather"},N=()=>{n.value?.onCancel&&n.value.onCancel(),C(),v.value="weather"},B=(e=[])=>{if(!n.value)return;const r=e.map(a=>[a.lat,a.lng]);n.value.points=[...r],n.value.polygonLayer.setLatLngs([r]),r.length?t.value.hasLayer(n.value.polygonLayer)||n.value.polygonLayer.addTo(t.value):n.value.polygonLayer.removeFrom(t.value)},C=()=>{n.value?.polygonLayer&&t.value?.hasLayer(n.value.polygonLayer)&&t.value.removeLayer(n.value.polygonLayer),n.value=null},ee=({onPlace:e})=>{v.value="point",g.value={onPlace:e}},ae=(e,r)=>{if(!g.value)return;const{onPlace:a}=g.value;v.value="weather",g.value=null,a&&a({lat:e,lng:r})},te=()=>{v.value="weather",g.value=null},E=e=>{const r=["#2563eb","#16a34a","#dc2626","#7c3aed","#d97706","#0891b2"],a=Math.abs(parseInt(e??0,10))%r.length;return r[a]};return le(async()=>{await $(),await new Promise(e=>setTimeout(e,500)),await Z()}),c({moveToLocation:U,updateMarker:G,toggleWeatherLayer:R,setBaseLayer:F,renderFarmOverlays:J,startBoundaryDrawing:K,finishBoundaryDrawing:k,cancelBoundaryDrawing:N,resetBoundaryDrawing:B,deleteLastBoundaryVertex:Q,getBoundaryPointCount:X,startPointPlacement:ee,cancelPointPlacement:te}),(e,r)=>(se(),ie("div",ve,[ue("div",{ref_key:"mapContainer",ref:m,class:"map"},null,512)]))}},fe=ne(ge,[["__scopeId","data-v-67e4fee8"]]);export{fe as default};

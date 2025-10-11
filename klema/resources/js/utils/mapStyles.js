export function applyMapStyles() {
  const style = document.createElement('style');
  style.textContent = `
    .enhanced-clouds-layer {
      filter: contrast(1.4) brightness(1.2) saturate(1.3);
      mix-blend-mode: overlay;
    }
    
    .enhanced-precipitation-layer {
      filter: contrast(1.6) brightness(1.1) saturate(1.5) hue-rotate(220deg);
      mix-blend-mode: multiply;
    }
    
    .enhanced-temperature-layer {
      filter: contrast(1.3) brightness(1.2) saturate(1.4);
      mix-blend-mode: overlay;
    }
    
    .enhanced-wind-layer {
      filter: contrast(1.5) brightness(1.3) saturate(1.2) hue-rotate(180deg);
      mix-blend-mode: soft-light;
    }
    
    .enhanced-pressure-layer {
      filter: contrast(1.4) brightness(1.1) saturate(1.3) hue-rotate(90deg);
      mix-blend-mode: overlay;
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
  `;
  document.head.appendChild(style);
}
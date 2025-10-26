export function applyMapStyles() {
  const style = document.createElement('style');
  style.textContent = `
    .enhanced-clouds-layer {
      filter: contrast(1.8) brightness(1.3) saturate(1.6) hue-rotate(200deg) sepia(0.3);
      mix-blend-mode: screen;
      opacity: 0.9 !important;
    }
    
    .enhanced-precipitation-layer {
      filter: contrast(2.0) brightness(1.2) saturate(1.8) hue-rotate(240deg) sepia(0.4);
      mix-blend-mode: multiply;
      opacity: 0.95 !important;
    }
    
    .enhanced-temperature-layer {
      filter: contrast(1.7) brightness(1.4) saturate(1.8) hue-rotate(-30deg) sepia(0.5);
      mix-blend-mode: overlay;
      opacity: 0.9 !important;
    }
    
    .enhanced-wind-layer {
      filter: contrast(1.9) brightness(1.5) saturate(1.7) hue-rotate(150deg) sepia(0.3);
      mix-blend-mode: soft-light;
      opacity: 0.9 !important;
    }
    
    .enhanced-pressure-layer {
      filter: contrast(1.6) brightness(1.3) saturate(1.5) hue-rotate(120deg) sepia(0.4);
      mix-blend-mode: overlay;
      opacity: 0.9 !important;
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
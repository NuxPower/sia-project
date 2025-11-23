export function applyMapStyles() {
  const style = document.createElement('style');
  style.textContent = `
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
  `;
  document.head.appendChild(style);
}
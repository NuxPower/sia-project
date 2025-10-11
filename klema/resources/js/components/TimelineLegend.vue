<template>
  <div class="timeline-legend">
    <div class="legend-item">
      <div class="legend-dot history"></div>
      <span>Historical</span>
    </div>
    <div class="legend-item">
      <div class="legend-dot today"></div>
      <span>Today</span>
    </div>
    <div class="legend-item">
      <div class="legend-dot future"></div>
      <span>Forecast</span>
    </div>
  </div>
</template>

<style scoped>
.timeline-legend {
  position: fixed;
  bottom: 100px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  background: rgba(0, 0, 0, 0.8);
  border-radius: 8px;
  padding: 6px 12px;
  gap: 16px;
  z-index: 999;
  font-size: 10px;
  color: #9ca3af;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.legend-dot.history {
  background: rgba(156, 163, 175, 0.5);
}

.legend-dot.today {
  background: rgba(59, 130, 246, 0.8);
}

.legend-dot.future {
  background: rgba(34, 197, 94, 0.5);
}
</style>
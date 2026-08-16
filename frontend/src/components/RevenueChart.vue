<template>
  <svg class="chart" :viewBox="`0 0 ${width} ${height}`" preserveAspectRatio="none">
    <line
      v-for="tick in 4"
      :key="tick"
      class="chart__grid"
      x1="0"
      :y1="(height / 4) * tick"
      :x2="width"
      :y2="(height / 4) * tick"
    />
    <polyline v-if="points" class="chart__line" :points="points" />
    <circle v-for="dot in dots" :key="dot.key" class="chart__dot" :cx="dot.x" :cy="dot.y" r="3" />
  </svg>
</template>

<script>
export default {
  name: 'RevenueChart',
  props: {
    items: { type: Array, default: () => [] },
  },
  data() {
    return {
      width: 600,
      height: 180,
    };
  },
  computed: {
    dots() {
      if (this.items.length === 0) {
        return [];
      }

      const max = this.items.reduce((acc, item) => Math.max(acc, item.revenue), 0) || 1;
      const step = this.items.length > 1 ? this.width / (this.items.length - 1) : this.width;

      return this.items.map((item, index) => ({
        key: item.period,
        x: Math.round(index * step),
        y: Math.round(this.height - (item.revenue / max) * (this.height - 20) - 10),
      }));
    },
    points() {
      return this.dots.map((dot) => `${dot.x},${dot.y}`).join(' ');
    },
  },
};
</script>

<style scoped>
.chart {
  width: 100%;
  height: 180px;
}

.chart__grid {
  stroke: #eef1f7;
  stroke-width: 1;
}

.chart__line {
  fill: none;
  stroke: #2f6fed;
  stroke-width: 2;
}

.chart__dot {
  fill: #2f6fed;
}
</style>

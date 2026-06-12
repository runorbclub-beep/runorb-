<template>
  <e-chart autoresize v-if="loaded" :loading="!loaded" class="chart" :option="option" />
</template>

<script>
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import EChart, { THEME_KEY, INIT_OPTIONS_KEY } from 'vue-echarts'
import {
  GridComponent,
  LegendComponent,
  TitleComponent,
  ToolboxComponent,
  DataZoomComponent,
  VisualMapComponent,
  TimelineComponent,
  CalendarComponent,
  TooltipComponent
} from 'echarts/components'
import { BarChart, LineChart } from 'echarts/charts'
use([
  GridComponent,
  BarChart,
  LineChart,
  CanvasRenderer,
  LegendComponent,
  TitleComponent,
  ToolboxComponent,
  DataZoomComponent,
  VisualMapComponent,
  TimelineComponent,
  CalendarComponent,
  TooltipComponent
])

export default {
  components: {
    EChart
  },
  props: {
    option: {
      type: Object,
      default: null
    }
  },
  provide: {
    [THEME_KEY]: 'light',
    [INIT_OPTIONS_KEY]: {
      width: 'auto'
    }
  },
  data() {
    return {
      loading: true
    }
  },
  computed: {
    loaded: function() {
      if (Object.keys(this.option).length > 0) {
        return true
      }
      return false
    }
  },
  created() {}
}
</script>

<style scoped>
.chart {
  height: 400px;
}
</style>

<template>
  <e-chart v-if="loaded" autoresize :loading="!loaded" class="chart" :option="option" />
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
import { getAnalysis } from '@/api/analysis'
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
    return {}
  },
  computed: {
    loaded: function() {
      if (Object.keys(this.option).length > 0) {
        return true
      }
      return false
    }
  },
  created() {
    // getAnalysis({}).then((res) => {
    //   if (res.code === 1) {
    //     this.userPlayBar = res.data.echarts_bar_user_play
    //     this.loading = false
    //     console.log(res)
    //   } else {
    //     this.$message.info(res.msg)
    //   }
    // })
  }
}
</script>

<style scoped>
.chart {
  height: 400px;
}
</style>

<template>
  <a-modal
    title="运动数据"
    :width="1400"
    :visible="visible"
    :loading="loading"
    :afterClose="afterClose"
    destroyOnClose
    :footer="null"
    @ok="
      () => {
        $emit('ok')
      }
    "
    @cancel="
      () => {
        $emit('cancel')
      }
    "
  >
    <a-spin :spinning="loading">
      <a-row :gutter="24">
        <a-col :xl="12" :lg="24" :md="24" :sm="24" :xs="24">
          <a-card title="转速区间" style="margin-bottom: 24px" :body-style="{ padding: 0 }">
            <div style="min-height: 400px">
              <!-- :scale="scale" :axis1Opts="axis1Opts" :axis2Opts="axis2Opts"  -->
              <emini-bar2 :option="miniBar2Option" />
            </div>
          </a-card>
        </a-col>
        <a-col
          style="padding: 0 12px"
          :xl="12"
          :lg="24"
          :md="24"
          :sm="24"
          :xs="24">
          <a-card title="转速曲线" style="margin-bottom: 24px" :body-style="{ padding: 0 }">
            <div style="min-height: 400px">
              <!-- :scale="scale" :axis1Opts="axis1Opts" :axis2Opts="axis2Opts"  -->
              <emini-line :option="miniLineOption" />
            </div>
          </a-card>
        </a-col>
      </a-row>
      <a-row :gutter="24">
        <a-col :xl="24" :lg="24" :md="24" :sm="24" :xs="24">
          <a-card :bordered="false">
            <s-table
              ref="teamTable"
              size="default"
              rowKey="user_play_id"
              :columns="columns"
              :data="loadData"
              :scroll="{ y: 400 }"
              showPagination="auto"
            >
              <!-- <span slot="user_img" slot-scope="text, record">
                <a-avatar :size="44" icon="user" :src="imgURL + '/' + record.user_img" />
              </span> -->
              <span slot="abnormal" slot-scope="text">
                <a-tag v-if="text === 1" color="red"> 异常 </a-tag>
                <a-tag v-else color="cyan"> 正常 </a-tag>
              </span>
              <span slot="operation" slot-scope="text, record">
                <template>
                  <a @click="handlePlay(record.user_play_id)">图表数据</a>
                </template>
              </span>
            </s-table>
          </a-card>
        </a-col>
      </a-row>
    </a-spin>
  </a-modal>
</template>

<script>
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import { EminiLine, EminiBar2, STable } from '@/components'
import { matchsStageMovementData } from '@/api/matchs'
import { getUserPlayInfo } from '@/api/user'
const moment = require('moment')
const columns = [
  {
    title: '运动ID',
    dataIndex: 'user_play_id'
  },
  {
    title: '用户名',
    dataIndex: 'user_name'
  },
  // {
  //   title: 'logo',
  //   dataIndex: 'user_img',
  //   scopedSlots: { customRender: 'user_img' },
  // },

  {
    title: '运动时间/s',
    dataIndex: 'duration'
  },
  {
    title: '最高转速',
    dataIndex: 'speed_max'
  },
  {
    title: '运动圈数',
    dataIndex: 'circle_count'
  },
  {
    title: '开始时间',
    dataIndex: 'start_time'
  },
  {
    title: '结束时间',
    dataIndex: 'stop_time'
  },
  {
    title: '运动距离/km',
    dataIndex: 'distance_format'
  },
  {
    title: '数据状态',
    dataIndex: 'is_abnormal',
    scopedSlots: { customRender: 'abnormal' }
  },
  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]
export default {
  props: {
    visible: {
      type: Boolean,
      required: true
    },
    loading: {
      type: Boolean,
      default: () => false
    },
    model: {
      type: Object,
      default: () => null
    }
  },
  components: {
    STable,
    EminiLine,
    EminiBar2
  },

  data() {
    this.columns = columns
    return {
      form: this.$form.createForm(this),
      token: storage.get(ACCESS_TOKEN),
      isEdit: true,
      userPlayId: '',
      miniLineOption: {},
      miniBar2Option: {},
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      // 查询参数
      queryParam: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: (parameter) => {
        console.log(parameter)
        const requestParameters = Object.assign(
          {
            matchs_user_id: this.model.matchs_user_id,
            matchs_stage_id: this.model.matchs_stage_id,
            is_group: this.model.isGroup,
            user_id: this.model.isGroup === 0 ? this.model.user_id : null
          },
          parameter,
          this.queryParam
        )
        console.log('loadData request parameters:', requestParameters)
        return matchsStageMovementData(requestParameters).then((res) => {
          console.log(res)
          if (res.data.list.length > 0) {
            this.userPlayId = res.data.list[0].user_play_id
            console.log(this.userPlayId)
            this.getUserPlayInfo()
            return Object.assign(res.data, parameter)
          } else {
            console.log('没有数据')
            return Object.assign(res.data, parameter)
          }
        })
      },
      imgURL: ''
    }
  },
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
    this.form.resetFields()
  },
  computed: {},

  methods: {
    handlePlay(id) {
      console.log(id)
      this.userPlayId = id
      this.getUserPlayInfo()
    },
    // 获取单次运动列表数据
    getUserPlayInfo() {
      getUserPlayInfo({ user_play_id: this.userPlayId })
        .then((res) => {
          //   console.log(res)
          if (res.code === 1) {
            const userPlayDetail = res.data.user_play
            const xAxisData = userPlayDetail.user_play_detail.map((a) => moment(a.moment).format('YYYY-MM-DD HH:mm'))
            const yAxisData = userPlayDetail.user_play_detail.map((a) => a.speed - 0)
            const eminiBar2YAxisData = userPlayDetail.section_duration.map(
              (a) => `${a.start_section}~${a.stop_section}`
            )
            const eminiBar2XAxisData = userPlayDetail.section_duration.map((a) => a.section_duration)
            // console.log(eminiBar2YAxisData)
            this.miniLineOption = {
              grid: {
                containLabel: true
              },
              tooltip: {
                trigger: 'axis',
                axisPointer: {
                  type: 'shadow',
                  label: {
                    show: true
                  }
                }
              },
              xAxis: {
                type: 'category',
                data: xAxisData,
                boundaryGap: false,
                show: false
              },
              yAxis: [
                {
                  type: 'value'
                }
              ],
              series: [
                {
                  name: '当前转速',
                  type: 'line',
                  smooth: true,
                  symbol: 'none',
                  areaStyle: {},
                  sampling: 'lttb',
                  itemStyle: {
                    // color: 'rgb(255, 70, 131)',
                  },
                  data: yAxisData,
                  markPoint: {
                    data: [
                      { type: 'max', name: '最大值' },
                      { type: 'min', name: '最小值' }
                    ]
                  },
                  markLine: {
                    data: [{ type: 'average', name: '平均值' }]
                  }
                }
              ]
            }

            this.miniBar2Option = {
              title: {
                // text: '世界人口总量',
                // subtext: '数据来自网络',
              },
              tooltip: {
                trigger: 'axis',
                axisPointer: {
                  type: 'shadow'
                }
              },
              grid: {
                // left: '3%',
                // right: '4%',
                // bottom: '3%',
                containLabel: true
              },
              xAxis: {
                type: 'value',
                boundaryGap: [0, 0.01],
                axisLabel: {
                  formatter: '{value} 秒'
                }
              },
              yAxis: {
                type: 'category',
                data: eminiBar2YAxisData
              },
              series: [
                {
                  name: '持续时间',
                  type: 'bar',
                  data: eminiBar2XAxisData
                }
              ]
            }
            console.log(xAxisData)
          } else {
            this.$message.error(res.msg)
          }
        })
        .catch((err) => {
          console.log(err)
        })
    },
    // modal关闭之后回调函数
    afterClose() {
      this.miniLineOption = {}
      this.miniBar2Option = {}
      // 清除选择
    }
  }
}
</script>
<style scoped></style>

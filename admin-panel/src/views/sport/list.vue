<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="table-page-search-wrapper">
        <a-form layout="inline">
          <a-row :gutter="20">
            <a-col
              :xl="6"
              :md="24"
              :sm="24"
            >
              <a-form-item label="运动数据状态">
                <a-radio-group
                  default-value=""
                  button-style="solid"
                  v-model="queryParam.is_abnormal"
                  @change="onRadioChange"
                >
                  <a-radio-button
                    :value="item.value"
                    v-for="item of sportTypeList"
                    :key="item.value"
                  >
                    {{ item.name }}
                  </a-radio-button>
                </a-radio-group>
                <a-button
                  style="margin-left:20px"
                  type="primary"
                  @click="resetTable(1)"
                >重置</a-button>
              </a-form-item>
            </a-col>
            <a-col
              :xl="8"
              :md="24"
              :sm="24"
            >
              <a-form-item label="运动时间">
                <a-range-picker
                  style="width:100%"
                  v-model="queryParam.play_time"
                  @ok="onRangeChange"
                  :show-time="{
                    hideDisabledOptions: true,
                    defaultValue: [moment('00:00:00', 'HH:mm:ss'), moment('11:59:59', 'HH:mm:ss')],
                  }"
                  format="YYYY-MM-DD HH:mm:ss"
                />
              </a-form-item>
            </a-col>

            <a-col
              :md="2"
              :sm="24"
            >
              <span class="table-page-search-submitButtons">
                <a-button
                  type="primary"
                  @click="resetTable(2)"
                >重置</a-button>
              </span>
            </a-col>
          </a-row>
          <a-row :gutter="20">
            <a-col
              :xl="7"
              :md="12"
              :sm="24"
            >
              <a-form-item label="排序项">
                <a-radio-group
                  default-value=""
                  button-style="solid"
                  v-model="queryParam.order_by_type"
                  @change="onRadioChange"
                >
                  <a-radio
                    :value="item.value"
                    v-for="item of orderByType"
                    :key="item.value"
                  >
                    {{ item.label }}
                  </a-radio>
                </a-radio-group>
              </a-form-item>
            </a-col>
            <a-col
              :xl="6"
              :md="12"
              :sm="24"
            >
              <a-form-item label="">
                <a-radio-group
                  default-value=""
                  button-style="solid"
                  v-model="queryParam.order_by"
                  @change="onRadioChange"
                >
                  <a-radio-button
                    :value="item.value"
                    v-for="item of orderBy"
                    :key="item.value"
                  >
                    {{ item.label }}
                  </a-radio-button>
                </a-radio-group>
                <a-button
                  style="margin-left:20px"
                  type="primary"
                  @click="resetTable(3)"
                >重置</a-button>
              </a-form-item>

            </a-col>

          </a-row>
        </a-form>
      </div>

      <div class="table-operator"></div>

      <s-table
        ref="table"
        size="default"
        rowKey="user_play_id"
        :columns="columns"
        :data="loadData"
        :alert="false"
        :pagination="pagination"
      >
        <span
          slot="operation"
          slot-scope="text, record"
        >
          <template>
            <a @click="showDrawer(record.user_play_id)">数据</a>
            <a-divider type="vertical" />
            <a @click="handleInfo(record.user_id)">详情</a>
            <a-divider type="vertical" />
            <a-popconfirm
              title="确认删除?"
              @confirm="() => onDelete(record.user_play_id)"
            >
              <a href="javascript:;">删除</a>
            </a-popconfirm>
          </template>
        </span>
        <span
          slot="abnormal"
          slot-scope="text"
        >
          <a-tag
            v-if="text === 1"
            color="red"
          > 异常 </a-tag>
          <a-tag
            v-else
            color="cyan"
          > 正常 </a-tag>
        </span>
        <span
          slot="user_img"
          slot-scope="text, record"
        >
          <a-avatar
            :size="44"
            icon="user"
            :src="record.user_img"
          />
        </span>
      </s-table>
      <a-drawer
        title="运动数据"
        placement="right"
        width="640"
        :closable="false"
        :visible="visible"
        :after-visible-change="afterVisibleChange"
        @close="onClose"
      >
        <a-row :gutter="24">
          <a-col
            :xl="24"
            :lg="24"
            :md="24"
            :sm="24"
            :xs="24"
          >
            <a-card
              title="转速区间"
              style="margin-bottom: 24px"
              :body-style="{ padding: 0 }"
            >
              <div style="min-height: 400px">
                <!-- :scale="scale" :axis1Opts="axis1Opts" :axis2Opts="axis2Opts"  -->
                <emini-bar2 :option="miniBar2Option" />
              </div>
            </a-card>
          </a-col>
          <a-col
            style="padding: 0 12px"
            :xl="24"
            :lg="24"
            :md="24"
            :sm="24"
            :xs="24"
          >
            <a-card
              title="转速曲线"
              style="margin-bottom: 24px"
              :body-style="{ padding: 0 }"
            >
              <div style="min-height: 400px">
                <!-- :scale="scale" :axis1Opts="axis1Opts" :axis2Opts="axis2Opts"  -->
                <emini-line :option="miniLineOption" />
              </div>
            </a-card>
          </a-col>
        </a-row>
      </a-drawer>
    </a-card>
  </page-header-wrapper>
</template>

<script>
import moment from 'moment'
import { STable, EminiLine, EminiBar2 } from '@/components'
import { getSportList, deleteUserPlay } from '@/api/sport'
import { getUserPlayInfo } from '@/api/user'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  // {
  //   title: '用户ID',
  //   dataIndex: 'user_id'
  // },
  {
    title: '运动ID',
    dataIndex: 'user_play_id'
  },
  {
    title: '用户名',
    dataIndex: 'user_name'
  },
  {
    title: '用户头像',
    dataIndex: 'user_img',
    align: 'center',
    scopedSlots: { customRender: 'user_img' }
  },
  {
    title: '摇跑指数',
    dataIndex: 'exponent'
  },
  {
    title: '摇跑1分钟',
    dataIndex: 'exponent_molecular'
  },
  {
    title: '持续时间',
    dataIndex: 'duration_format'
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
    title: '运动距离/km',
    dataIndex: 'distance_format'
  },
  {
    title: '开始时间',
    dataIndex: 'start_time_format'
  },
  {
    title: '结束时间',
    dataIndex: 'stop_time_format'
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
const orderByType = [
  {
    key: 'duration',
    value: 'duration',
    label: '持续时间'
  },
  {
    key: 'speed_max',
    value: 'speed_max',
    label: '最高转速'
  },
  {
    key: 'circle_count',
    value: 'circle_count',
    label: '运动圈数'
  },
  {
    key: 'exponent_molecular',
    value: 'exponent_molecular',
    label: '摇跑一分钟'
  },
  {
    key: 'exponent',
    value: 'exponent',
    label: '摇跑指数'
  }
]
const orderBy = [
  {
    key: 'ASC',
    value: 'ASC',
    label: '升序'
  },
  {
    key: 'DESC',
    value: 'DESC',
    label: '降序'
  }
]
export default {
  name: 'SportList',
  components: {
    STable,
    EminiLine,
    EminiBar2
  },
  data() {
    this.columns = columns
    return {
      pagination,
      // create model
      visible: false,
      confirmLoading: false,
      mdl: null,
      // 团队表单
      visibleTeam: false,
      teamConfirmLoading: false,
      teammdl: null,
      // 高级搜索 展开/关闭
      advanced: false,
      // 查询参数
      queryParam: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: parameter => {
        console.log(parameter)
        const requestParameters = Object.assign({}, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        return getSportList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      selectedRowKeys: [],
      selectedRows: [],
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      orderByType,
      orderBy,
      sportTypeList: [
        {
          name: '正常',
          value: 0
        },
        {
          name: '异常',
          value: 1
        }
      ],
      userId: '',
      userPlayId: '',
      miniLineOption: {},
      miniBar2Option: {}
    }
  },
  filters: {},
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
  },
  computed: {},
  methods: {
    moment,
    // 切换抽屉时动画结束后的回调
    afterVisibleChange(val) {
      console.log('visible', val)
      if (!val) {
        this.miniLineOption = {}
        this.miniBar2Option = {}
      }
    },
    // 展示图表数据
    showDrawer(id) {
      this.visible = true
      this.userPlayId = id
      this.getUserPlayInfo()
    },
    // 获取单次运动列表数据
    getUserPlayInfo() {
      getUserPlayInfo({ user_play_id: this.userPlayId })
        .then(res => {
          //   console.log(res)
          if (res.code === 1) {
            const userPlayDetail = res.data.user_play
            const xAxisData = userPlayDetail.user_play_detail.map(a =>
              moment(a.moment).format('YYYY-MM-DD HH:mm')
            )
            const yAxisData = userPlayDetail.user_play_detail.map(
              a => a.speed - 0
            )
            const eminiBar2YAxisData = userPlayDetail.section_duration.map(
              a => `${a.start_section}~${a.stop_section}`
            )
            const eminiBar2XAxisData = userPlayDetail.section_duration.map(
              a => a.section_duration
            )
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
              tooltip: {
                trigger: 'axis',
                axisPointer: {
                  type: 'shadow'
                }
              },
              grid: {
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
        .catch(err => {
          console.log(err)
        })
    },
    onDelete(key) {
      console.log(key)
      const requestParameters = {
        user_play_id: key
      }
      deleteUserPlay(requestParameters).then(res => {
        console.log(res)

        // 刷新表格
        this.$refs.table.refresh()

        this.$message.info(res.msg)
      })
    },
    onClose() {
      this.visible = false
    },
    resetTable(type) {
      if (type === 1) {
        this.queryParam.is_abnormal = undefined
      } else if (type === 2) {
        this.queryParam.play_time = undefined
      } else {
        this.queryParam.order_by_type = undefined
        this.queryParam.order_by = undefined
      }

      this.$refs.table.refresh(true)
    },
    onRadioChange(event) {
      console.log(event)
      this.$refs.table.refresh(true)
    },
    onRangeChange(event) {
      this.$refs.table.refresh(true)
    },

    // 查看用户详情
    handleInfo(userID) {
      console.log('用户ID', userID)
      this.$router.push({
        path: '/users/details',
        query: {
          id: userID
        }
      })
    }
  }
}
</script>

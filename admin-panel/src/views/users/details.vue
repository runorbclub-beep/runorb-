<template>
  <page-header-wrapper>
    <template
      v-slot:content
      v-if="Object.keys(userDetail).length > 0"
    >
      <div class="page-header-content">
        <div
          class="avatar"
          style="text-align: center"
        >
          <a-avatar
            icon="user"
            :size="72"
            :src="`${userDetail.user_img}`"
          />
          <div>{{ userDetail.user_type_name }}</div>
        </div>
        <div class="content">
          <div class="content-title">
            <span class="welcome-text">
              <span>{{ userDetail.user_name }}</span>
              <span class="content_members">( <span>{{ userDetail.is_members === 0 ?'非会员':'会员' }}</span> )</span>
              <span class="content_members_status">当前状态:
                <span>{{ userDetail.members_status === 0 ?'待审核':userDetail.members_status === 1 ?'已审核': userDetail.members_status === 2 ?'已驳回':userDetail.members_status === 3 ?'已过期':'未提交' }}</span>
              </span>
            </span>
          </div>
          <div class="content-self_introduce">
            <span>自我介绍：</span>
            <span>{{ userDetail.self_description }}</span>
          </div>
          <div class="content-user-info">
            <span v-if="userDetail.birthday !== ''&&userDetail.birthday !== null ">
              <a-icon type="crown" /><span class="content-user-info-detail">{{ userDetail.birthday }}</span>
              <a-divider type="vertical" />
            </span>

            <span v-if="userDetail.phone !== ''&& userDetail.phone !== null">
              <a-icon type="phone" /><span class="content-user-info-detail">{{ userDetail.phone }}</span>
              <a-divider type="vertical" />
            </span>

            <span v-if="userDetail.address !== '' && userDetail.address !== null">
              <a-icon type="home" /><span class="content-user-info-detail">{{ userDetail.address }}</span>
            </span>
          </div>
        </div>
      </div>
    </template>
    <template v-slot:extraContent>
      <div class="extra-content">
        <div class="stat-item">
          <a-statistic
            title="总用时"
            :value="achievement.duration"
            suffix="s"
          />
        </div>
        <div class="stat-item">
          <a-statistic
            title="最高转速"
            :value="achievement.speed_max"
          />
        </div>
        <div class="stat-item">
          <a-statistic
            title="运动次数"
            :value="achievement.play_count"
          />
        </div>
        <div class="stat-item">
          <a-statistic
            title="最高圈数"
            :value="achievement.circle_count"
          />
        </div>
        <div class="stat-item">
          <a-statistic
            title="摇跑指数"
            :value="achievement.runball_exponent"
          />
        </div>
        <div class="stat-item">
          <a-statistic
            title="当前积分"
            :value="userDetail.integral"
          />
        </div>
      </div>
    </template>

    <div>
      <a-row :gutter="24">
        <a-col
          :xl="16"
          :lg="24"
          :md="24"
          :sm="24"
          :xs="24"
        >
          <a-card
            title="历史运动次数"
            style="margin-bottom: 24px"
            :bordered="false"
            :body-style="{ padding: 0 }"
          >
            <div style="min-height: 400px">
              <emini-line :option="miniLineHistoryOption" />
            </div>
          </a-card>

          <a-card
            class="project-list"
            style="margin-bottom: 24px"
            :bordered="false"
            title="运动列表"
            :body-style="{ padding: 0 }"
          >
            <s-table
              ref="table"
              size="default"
              rowKey="user_play_id"
              :columns="columns"
              :data="loadData"
              :pagination="pagination"
            >
              <span
                slot="operation"
                slot-scope="text, record"
              >
                <template>
                  <a @click="handlePlay(record.user_play_id)">图表数据</a>
                  <a-divider type="vertical" />
                  <a-popconfirm
                    title="确认删除?"
                    @confirm="() => onDelete(record.user_play_id)"
                  >
                    <a href="javascript:;">删除</a>
                  </a-popconfirm>
                </template>
              </span>
              <!-- <span slot="compare_last" slot-scope="text">
                {{ text }}
              </span> -->
              <span
                slot="user_img"
                slot-scope="text, record"
              >
                <img
                  style="width: 50px; heigth: 50px"
                  :src="imgURL + '/' + record.user_img"
                />
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
            </s-table>
          </a-card>
        </a-col>
        <a-col
          style="padding: 0 12px"
          :xl="8"
          :lg="24"
          :md="24"
          :sm="24"
          :xs="24"
        >
          <a-card
            title="转速区间"
            style="margin-bottom: 24px"
            :bordered="false"
            :body-style="{ padding: 0 }"
          >
            <div style="min-height: 400px">
              <emini-bar2 :option="miniBar2Option" />
            </div>
          </a-card>
          <a-card
            title="转速曲线"
            style="margin-bottom: 24px"
            :bordered="false"
            :body-style="{ padding: 0 }"
          >
            <div style="min-height: 400px">
              <emini-line :option="miniLineOption" />
            </div>
          </a-card>
        </a-col>
      </a-row>
    </div>
  </page-header-wrapper>
</template>

<script>
import { PageHeaderWrapper } from '@ant-design-vue/pro-layout'
import { EminiLine, EminiBar2, STable } from '@/components'
import { deleteUserPlay } from '@/api/sport'
import { getUserInfo, getUserPlayList, getUserPlayInfo } from '@/api/user'
const moment = require('moment')
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '用户运动ID',
    dataIndex: 'user_play_id'
  },
  {
    title: '运动时间',
    dataIndex: 'duration_format'
  },
  {
    title: '最高转速/rpm',
    dataIndex: 'speed_max'
  },
  {
    title: '运动圈数',
    dataIndex: 'circle_count'
  },
  // {
  //   title: '对比上一次数据',
  //   dataIndex: 'compare_last',
  //   scopedSlots: { customRender: 'compare_last' },
  // },
  {
    title: '开始时间',
    dataIndex: 'start_time_format'
  },

  {
    title: '结束时间',
    dataIndex: 'stop_time_format'
  },
  {
    title: '运动距离',
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
  name: 'UsersDetails',
  components: {
    PageHeaderWrapper,
    EminiLine,
    EminiBar2,
    STable
  },
  data() {
    return {
      pagination,
      userId: '',
      userDetail: {},
      achievement: {},
      userPlayId: '',
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      avatar: '',
      user: {},
      columns,

      miniLineOption: {},
      miniLineHistoryOption: {},
      miniBar2Option: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: parameter => {
        console.log(parameter)
        const requestParameters = Object.assign(
          { user_id: this.userId },
          parameter,
          this.queryParam
        )
        console.log('loadData request parameters:', requestParameters)
        return getUserPlayList(requestParameters).then(res => {
          if (res.code === 1) {
            console.log(res)
            console.log(res.data.list)
            if (res.data.list.length > 0) {
              this.userPlayId = res.data.list[0].user_play_id
              console.log(this.userPlayId)
              this.getUserPlayInfo()
            }

            return Object.assign(res.data, parameter)
          } else {
            this.$message.error(res.msg)
          }
        })
      }
    }
  },
  computed: {},
  created() {
    // this.userId = '49981181663383552'
    this.userId = this.$route.query.id
    // 获取用户基本信息
    getUserInfo({ user_id: this.userId })
      .then(res => {
        console.log('用户详情===', res)
        if (res.code === 1) {
          this.userDetail = res.data
          this.achievement = res.data.achievement
          const tooltip = {
            trigger: 'axis',
            axisPointer: {
              type: 'shadow',
              label: {
                show: true
              }
            }
          }
          const toolbox = {
            show: true,
            feature: {
              mark: { show: true },
              dataView: { show: true, readOnly: false },
              magicType: { show: true, type: ['line', 'bar'] },
              restore: { show: true },
              saveAsImage: { show: true }
            }
          }
          const historyData = res.data.history_play_echart
          historyData.tooltip = tooltip
          historyData.toolbox = toolbox
          this.miniLineHistoryOption = historyData
          //   this.$message.success(res.msg)
        } else {
          this.$message.error(res.msg)
        }
      })
      .catch(err => {
        console.log(err)
      })
  },
  mounted() {},
  methods: {
    handlePlay(id) {
      console.log(id)
      this.userPlayId = id
      this.getUserPlayInfo()
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
    }
  }
}
</script>

<style lang="less" scoped>
@import './less/Details.less';
</style>

<template>
  <div>
    <a-row :gutter="24">
      <a-col
        :sm="24"
        :md="10"
        :xl="10"
        :style="{ marginBottom: '24px' }"
      >
        <a-card title="用户数统计">
          <div class="chart-card-header">
            <div class="meta">
              <span class="chart-card-title">
                <div class="extra-content">
                  <div class="stat-item">
                    <a-statistic
                      title="总用户数"
                      :value="userPlayData.user_count"
                    />
                  </div>
                  <div class="stat-item">
                    <a-statistic
                      title="注册用户数"
                      :value="userPlayData.register_user_count"
                    />
                  </div>
                  <div class="stat-item">
                    <a-statistic
                      title="团队数"
                      :value="userPlayData.user_group_count"
                    />
                  </div>
                  <div class="stat-item">
                    <a-statistic
                      title="团队用户数"
                      :value="userPlayData.group_user_count"
                    />
                  </div>
                  <div class="stat-item">
                    <a-statistic
                      title="会员数"
                      :value="userPlayData.members_user_count"
                    />
                  </div>
                  <div class="stat-item">
                    <a-statistic
                      title="待审核会员数"
                      :value="userPlayData.members_wait_user_count"
                    >
                      <template #suffix>
                        <router-link :to="{name:'UsersList', query: {id:0}}"> 审核</router-link>
                      </template>
                    </a-statistic>
                  </div>
                </div>
              </span>
            </div>
          </div>
        </a-card>
      </a-col>
      <a-col
        :sm="24"
        :md="14"
        :xl="14"
        :style="{ marginBottom: '24px' }"
      >
        <a-card title="用户运动统计">
          <emini-bar :option="miniBarOption" />
        </a-card>
      </a-col>
    </a-row>
  </div>
</template>

<script>
import { EminiBar } from '@/components'
import { getAnalysis } from '@/api/analysis'
export default {
  name: 'Analysis',
  components: {
    EminiBar
  },
  data() {
    return {
      userPlayData: {},
      miniBarOption: {}
    }
  },
  computed: {},
  created() {
    // 获取图表数据
    getAnalysis({}).then(res => {
      console.log(res)
      this.userPlayData = res.data
      if (res.code === 1) {
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
        const userPlayData = res.data.echarts_bar_user_play
        // console.log(userPlayData)
        userPlayData.tooltip = tooltip
        userPlayData.toolbox = toolbox
        this.miniBarOption = userPlayData
      } else {
        this.$message.info(res.msg)
      }
    })
  }
}
</script>
<style lang="less" scoped>
@import './less/Analysis.less';
.chart-card-header {
  position: relative;
  overflow: hidden;
  width: 100%;
  margin: 0 0 20px 0;

  .meta {
    position: relative;
    overflow: hidden;
    width: 100%;
    color: rgba(0, 0, 0, 0.45);
    font-size: 14px;
    line-height: 22px;
  }
}
</style>

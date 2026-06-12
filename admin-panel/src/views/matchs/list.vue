<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="main">
        <div class="table-page-search-wrapper">
          <a-form layout="inline">
            <a-row :gutter="48">
              <a-col
                :md="6"
                :sm="24"
              >
                <a-form-item>
                  <a-input
                    v-model="search"
                    :placeholder="$t('matchs.search.placeholder')"
                  >
                    <a-icon
                      slot="prefix"
                      type="search"
                      style="color: rgba(0, 0, 0, 0.25)"
                    />
                  </a-input>
                </a-form-item>
              </a-col>
              <a-col
                :md="8"
                :sm="24"
              >
                <a-form-item>
                  <a-button
                    type="primary"
                    @click="searchClick"
                  >
                    查询
                  </a-button>
                  <a-button
                    style="margin-left: 8px"
                    @click="resetTable"
                  >重置</a-button>
                </a-form-item>
              </a-col>
            </a-row>
          </a-form>
        </div>
        <div class="table-operator">
          <a-button
            type="primary"
            icon="plus"
            @click="add_match"
          >新建</a-button>
        </div>
        <a-table
          ref="table"
          :pagination="pagination"
          :columns="columns"
          :data-source="list"
          :row-key="(record) => record.sys_match_id"
          :loading="listLoading"
        >
          <span
            slot="operation"
            slot-scope="obj"
          >
            <a-button
              type="link"
              @click="OnMatchPoint(obj)"
            >赛点列表</a-button>
            <a-divider type="vertical" />
            <a-button
              type="link"
              @click="onMedal(obj)"
            >奖章列表</a-button>
            <a-divider type="vertical" />
            <a-button
              type="link"
              @click="MatchCenter(obj)"
            >{{ $t('matchs.center') }}</a-button>
            <a-divider type="vertical" />
            <a-popconfirm
              :title="$t('match.ask.release')"
              :ok-text="$t('global.yes')"
              :cancel-text="$t('global.no')"
              @confirm="releaseMatch(obj)"
            >
              <a-button
                type="link"
                v-if="obj.status === 0"
              >{{ $t('matchs.release') }}</a-button>
            </a-popconfirm>

            <a-popconfirm
              :title="$t('match.ask.unrelease')"
              :ok-text="$t('global.yes')"
              :cancel-text="$t('global.no')"
              @confirm="unReleaseMatch(obj)"
            >
              <a-button
                type="link"
                v-if="obj.status === 1"
              >{{ $t('matchs.unrelease') }}</a-button>
            </a-popconfirm>
            <a-divider type="vertical" />
            <a-button
              type="link"
              @click="editMatch(obj)"
            >{{ $t('global.edit') }}</a-button>
            <a-divider type="vertical" />
            <a-popconfirm
              :title="$t('match.delete')"
              :ok-text="$t('global.yes')"
              :cancel-text="$t('global.no')"
              @confirm="deleteMatch(obj)"
            >
              <a-button type="link">{{ $t('global.delete') }}</a-button>
            </a-popconfirm>
          </span>
        </a-table>
      </div>
    </a-card>
  </page-header-wrapper>
</template>

<script>
import {
  SysMatchList,
  MatchRelease,
  MatchUnRelease,
  MatchDelete
} from '@/api/matchs'

const columns = [
  {
    title: 'UUID',
    dataIndex: 'sys_match_id'
  },
  {
    title: '赛事标题',
    dataIndex: 'match_title'
  },
  {
    title: '赛事类型',
    dataIndex: 'matchs_type_title'
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
    title: '状态',
    dataIndex: 'match_state'
  },
  {
    title: '报名人数',
    dataIndex: 'match_user_sign_count'
  },
  // {
  //   title: '用户类型',
  //   dataIndex: 'match_user_type_description',
  // },
  // {
  //   title: '性别',
  //   dataIndex: 'match_user_sex_description',
  // },

  {
    title: '操作',
    key: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  data() {
    return {
      columns,
      form: this.$form.createForm(this),
      search: '',
      page: 1,
      listLoading: false,
      pagination: {
        pageSize: 10,
        showSizeChanger: true,
        pageSizeOptions: ['10', '20', '30', '40'],
        total: 0,
        showQuickJumper: true,
        showTotal: (total, range) =>
          `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`,
        onChange: (page, pageSize) => this.changePage(page, pageSize),
        onShowSizeChange: (page, pageSize) =>
          this.pageSizeChange(page, pageSize)
      },
      list: []
    }
  },
  mounted() {
    this.getList()
  },
  methods: {
    resetTable() {
      this.search = ''
      this.page = 1
      this.getList()
    },
    // 获取赛段规则列表
    getList() {
      this.listLoading = true
      var params = {
        page: this.page,
        limit: this.pagination.pageSize,
        search: this.search
      }
      SysMatchList(params).then(res => this.SysMatchListSuccess(res))
    },
    SysMatchListSuccess(res) {
      console.log(res)
      this.listLoading = false
      this.list = res.data.list
      this.pagination.total = res.data.count
    },
    // 搜索按钮点击事件
    searchClick() {
      this.page = 1
      this.getList()
    },
    // 分页点击事件
    changePage(page, pageSize) {
      this.page = page
      this.pagination.pageSize = pageSize
      this.getList()
    },
    // 切换每页显示条数
    pageSizeChange(page, pageSize) {
      this.page = page
      this.pagination.pageSize = pageSize

      this.getList()
    },
    //   新增赛事
    add_match() {
      this.$router.push({
        path: '/matchs/add'
      })
    },
    // 编辑赛事类型
    editMatch(obj) {
      this.$router.push({
        path: '/matchs/add',
        query: { sys_match_id: obj.sys_match_id }
      })
    },
    // 删除比赛项目
    deleteMatch(obj) {
      var params = {
        sys_match_id: obj.sys_match_id
      }
      MatchDelete(params).then(res => {
        if (res.code === 1) {
          this.$message.success(res.msg)
          this.getList()
        } else {
          this.$message.error(res.msg)
        }
      })
    },
    // 发布赛事
    releaseMatch(obj) {
      var params = {
        sys_match_id: obj.sys_match_id
      }
      MatchRelease(params).then(res => {
        if (res.code === 1) {
          this.$message.success(res.msg)
          this.getList()
        } else {
          this.$message.error(res.msg)
        }
      })
    },
    unReleaseMatch(obj) {
      var params = {
        sys_match_id: obj.sys_match_id
      }
      MatchUnRelease(params).then(res => {
        if (res.code === 1) {
          this.$message.success(res.msg)
          this.getList()
        } else {
          this.$message.error(res.msg)
        }
      })
    },
    // 赛事管理中心
    MatchCenter(obj) {
      this.$router.push({
        path: '/matchs/center',
        query: { sys_match_id: obj.sys_match_id }
      })
    }
  }
}
</script>

<style lang="less" scoped></style>

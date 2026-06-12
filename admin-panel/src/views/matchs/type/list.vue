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
                    :placeholder="$t('matchs.search.type_placeholder')"
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
            @click="add_match_type"
          >新建</a-button>
        </div>
        <a-table
          :pagination="pagination"
          :columns="columns"
          :data-source="list"
          :row-key="(record) => record.matchs_type_id"
          :loading="listLoading"
        >
          <span
            slot="operation"
            slot-scope="obj"
          >
            <a-button
              type="link"
              @click="editMatchType(obj)"
            >{{ $t('global.edit') }}</a-button>
            <a-divider type="vertical" />
            <a-popconfirm
              :title="$t('match.type.delete')"
              :ok-text="$t('global.yes')"
              :cancel-text="$t('global.no')"
              @confirm="deleteMatchType(obj)"
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
import { MatchsTypeList, MatchsTypeDelete } from '@/api/matchs'

const columns = [
  {
    title: 'UUID',
    dataIndex: 'matchs_type_id'
  },
  {
    title: '赛事类型标题',
    dataIndex: 'matchs_type_title'
  },
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
      form: this.$form.createForm(this, { name: 'medal_search' }),
      search: '',
      page: 1,
      listLoading: false,
      pagination: {
        pageSize: 10,
        total: 0,
        showSizeChanger: true,
        pageSizeOptions: ['10', '20', '30', '40'],
        onChange: (page, pageSize) => this.changePage(page, pageSize),
        onShowSizeChange: (page, pageSize) =>
          this.pageSizeChange(page, pageSize),
        showQuickJumper: true,
        showTotal: (total, range) =>
          `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
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
      MatchsTypeList(params).then(res => this.MatchsTypeListSuccess(res))
    },
    MatchsTypeListSuccess(res) {
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
    //   新增比赛项目
    add_match_type() {
      this.$router.push({
        path: '/matchs/type/add'
      })
    },
    // 编辑赛事类型
    editMatchType(obj) {
      this.$router.push({
        path: '/matchs/type/add',
        query: {
          matchs_type_id: obj.matchs_type_id,
          matchs_type_title: obj.matchs_type_title
        }
      })
    },
    // 删除比赛项目
    deleteMatchType(obj) {
      var params = {
        matchs_type_id: obj.matchs_type_id
      }
      MatchsTypeDelete(params).then(res => this.MatchsTypeDeleteSuccess(res))
    },
    MatchsTypeDeleteSuccess(res) {
      if (res.code === 1) {
        this.$message.success(res.msg)
        this.getList()
      } else {
        this.$message.error(res.msg)
      }
    }
  }
}
</script>

<style lang="less" scoped></style>

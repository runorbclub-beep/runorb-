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
                    :placeholder="$t('matchs.search.event_type_placeholder')"
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
            @click="add_match_events"
          >新建</a-button>
        </div>

        <a-table
          :pagination="pagination"
          :columns="columns"
          :data-source="list"
          :row-key="(record) => record.matchs_event_type_id"
          :loading="listLoading"
        >
          <span
            slot="operation"
            slot-scope="obj"
          >
            <a-popconfirm
              :title="$t('match.events.type.delete')"
              :ok-text="$t('global.yes')"
              :cancel-text="$t('global.no')"
              @confirm="deleteMatchEventType(obj)"
            >
              <a-button type="link">{{ $t('global.delete') }}</a-button>
            </a-popconfirm>
          </span>
          <span
            slot="index"
            slot-scope="text, record"
          >
            <div class="index_wrap">
              <a-input-number
                class="index_input"
                :min="1"
                :disabled="projectId === record.matchs_event_type_id ? disabled : true"
                :default-value="text"
                :formatter="limitNumber"
                :parser="limitNumber"
                @change="onInputChange"
              />
              <div class="index_btn">
                <a-button
                  type="link"
                  @click="toggle(record.matchs_event_type_id)"
                >
                  <!-- {{ projectId === record.matchs_event_type_id && !disabled ? '保存' : '编辑' }} -->
                  <a-icon
                    v-if="projectId === record.matchs_event_type_id && !disabled"
                    type="save"
                  />
                  <a-icon
                    v-else
                    type="edit"
                  />
                </a-button>
              </div>
            </div>
          </span>
        </a-table>
      </div>
    </a-card>
  </page-header-wrapper>
</template>

<script>
import {
  MatchsEventTypeList,
  MatchsEventTypeDelete,
  updateProjectIndex
} from '@/api/matchs'
/* 限制数字输入框只能输入整数 */
const limitNumber = value => {
  if (typeof value === 'string') {
    return !isNaN(Number(value)) ? value.replace(/^(0+)|[^\d]/g, '') : ''
  } else if (typeof value === 'number') {
    return !isNaN(value) ? String(value).replace(/^(0+)|[^\d]/g, '') : ''
  } else {
    return ''
  }
}
const columns = [
  {
    title: 'UUID',
    dataIndex: 'matchs_event_type_id',
    key: 'matchs_event_type_id'
  },
  {
    title: '项目标题(中文)',
    dataIndex: 'match_events_type_title',
    key: 'match_events_type_title'
  },
  {
    title: '项目标题(英文)',
    dataIndex: 'match_events_type_title_en',
    key: 'match_events_type_title_en'
  },
  // {
  //   title: '运动距离',
  //   key: 'match_events_distance_value',
  //   dataIndex: 'match_events_distance_value',
  //   align: 'right',
  // },
  {
    title: '优先级排序',
    dataIndex: 'index',
    scopedSlots: { customRender: 'index' }
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
        showQuickJumper: true,
        showTotal: (total, range) =>
          `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`,
        onChange: (page, pageSize) => this.changePage(page, pageSize),
        onShowSizeChange: (page, pageSize) =>
          this.pageSizeChange(page, pageSize)
      },
      list: [],
      disabled: true,
      projectId: '',
      limitNumber,
      inputNumber: null
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
    onInputChange(value) {
      // console.log('输入框值', value)
      this.inputNumber = value
    },

    toggle(id) {
      // console.log(id)
      // console.log(this.projectId)
      if (this.projectId !== id) {
        this.disabled = true
        console.log('切换==', id)
      } else {
        if (!this.disabled) {
          console.log('改变值', this.inputNumber)
          console.log('保存提交==', id)

          const params = {
            matchs_event_type_id: id,
            index: this.inputNumber
          }
          updateProjectIndex({ ...params })
            .then(res => {
              console.log(res)
              if (res.code === 1) {
                this.$message.info('修改成功')
                this.$refs.table.refresh(true)
              } else {
                this.$message.info(res.msg)
              }
            })
            .catch(err => {
              console.log(err)
            })
        } else {
          console.log('编辑==', id)
        }
      }
      this.projectId = id
      this.disabled = !this.disabled
    },
    // 获取赛段规则列表
    getList() {
      this.listLoading = true
      var params = {
        page: this.page,
        limit: this.pagination.pageSize,
        search: this.search
      }
      MatchsEventTypeList(params).then(res =>
        this.MatchsEventTypeListSuccess(res)
      )
    },
    MatchsEventTypeListSuccess(res) {
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
    add_match_events() {
      this.$router.push({
        path: '/matchs/events/add'
      })
    },
    // 删除比赛项目
    deleteMatchEventType(obj) {
      var params = {
        matchs_event_type_id: obj.matchs_event_type_id
      }
      MatchsEventTypeDelete(params).then(res =>
        this.MatchsEventTypeDeleteSuccess(res)
      )
    },
    MatchsEventTypeDeleteSuccess(res) {
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

<style scoped>
.index_wrap {
  display: flex;
}
</style>

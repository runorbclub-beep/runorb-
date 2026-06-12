<template>
  <a-modal
    title="用户列表"
    :width="1400"
    :visible="visible"
    :loading="loading"
    :afterClose="afterClose"
    destroyOnClose
    ok-text="批量删除"
    cancel-text="关闭"
    :footer="null"
    :ok-button-props="{ props: { disabled: !isSelected } }"
    @ok="
      () => {
        $emit('ok', userId)
      }
    "
    @cancel="
      () => {
        $emit('cancel')
      }
    "
  >
    <a-spin :spinning="loading">
      <a-card :bordered="false">
        <!-- <div class="table-page-search-wrapper">
          <a-form layout="inline">
            <a-row :gutter="48">
              <a-col
                :md="8"
                :sm="24"
              >
                <a-form-item label="关键字">
                  <a-input
                    v-model="queryParam.search"
                    placeholder="用户名/团队名"
                  />
                </a-form-item>
              </a-col>
              <a-col
                :md="(!advanced && 8) || 24"
                :sm="24"
              >
                <span
                  class="table-page-search-submitButtons"
                  :style="(advanced && { float: 'right', overflow: 'hidden' }) || {}"
                >
                  <a-button
                    type="primary"
                    @click="$refs.teamTable.refresh(true)"
                  >查询</a-button>
                  <a-button
                    style="margin-left: 8px"
                    @click="() => (this.queryParam = {})"
                  >重置</a-button>
                </span>
              </a-col>
            </a-row>
          </a-form>
        </div> -->

        <s-table
          ref="teamTable"
          size="default"
          rowKey="web_match_ranking_detail_id"
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
              <a @click="handleEdit(record)">编辑</a>
              <a-divider type="vertical" />
              <a-popconfirm
                v-if="loadData.length"
                title="确认删除?"
                @confirm="() => onDelete(record.web_match_ranking_detail_id)"
              >
                <a href="javascript:;">删除</a>
              </a-popconfirm>
            </template>
          </span>
          <span
            slot="user_img"
            slot-scope="text, record"
          >
            <img
              style="width: 50px; heigth: 50px"
              :src="record.user_img"
            />
          </span>
          <span
            slot="value_format"
            slot-scope="text, record"
          >
            {{ text+record.unit }}
          </span>
        </s-table>
      </a-card>
    </a-spin>
    <user-form
      ref="userModal"
      :visible="visibleUser"
      :loading="confirmLoadingUser"
      :model="mdlUser"
      :rankingType="rankingType"
      @cancel="handleCancelUser"
      @ok="handleOkUser"
    />
  </a-modal>
</template>

<script>
import storage from 'store'
import moment from 'moment'
import UserForm from './UserForm'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import { STable, Ellipsis } from '@/components'
import {
  updateLeaderboardsListUser,
  getLeaderboardsUsersList,
  deleteLeaderboardsListUser
} from '@/api/website'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '用户ID',
    dataIndex: 'web_match_ranking_detail_id'
  },
  {
    title: '名次',
    dataIndex: 'index'
  },
  {
    title: '用户名',
    dataIndex: 'user_name'
  },
  {
    title: '用户头像',
    dataIndex: 'user_img',
    scopedSlots: { customRender: 'user_img' }
  },
  {
    title: '榜单数据',
    dataIndex: 'value_format',
    scopedSlots: { customRender: 'value_format' }
  },
  {
    title: '榜单数据获取时间',
    dataIndex: 'join_time_format'
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
    },
    leaderboardType: {
      type: Array,
      default: () => []
    }
  },
  components: {
    STable,
    Ellipsis,
    UserForm
  },

  data() {
    this.columns = columns
    return {
      pagination,
      // User model 用户编辑
      visibleUser: false,
      confirmLoadingUser: false,
      mdlUser: null,
      form: this.$form.createForm(this),
      token: storage.get(ACCESS_TOKEN),
      isEdit: true,
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      // 高级搜索 展开/关闭
      advanced: false,
      // 查询参数
      queryParam: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: parameter => {
        console.log(parameter)
        console.log(this.model)
        const obj = {
          web_match_ranking_id: this.model.web_match_ranking_id,
          rank_type: this.model.ranking_type
        }
        const requestParameters = Object.assign(obj, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        return getLeaderboardsUsersList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      imgURL: '',
      UserTypeList: [],
      isSelected: false,
      userId: '',
      selectedRowKeys: [],
      rankingType: []
    }
  },
  created() {
    console.log(this.leaderboardType)
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')

    this.form.resetFields()
  },
  computed: {
    rowSelection() {
      const that = this
      return {
        selectedRowKeys: that.selectedRowKeys,
        onChange: (selectedRowKeys, selectedRows) => {
          that.isSelected = true
          // that.userId = selectedRows[0].user_id
          that.selectedRowKeys = selectedRowKeys
          console.log(
            `selectedRowKeys: ${selectedRowKeys}`,
            'selectedRows: ',
            selectedRows
          )
        }
      }
    }
  },
  watch: {
    leaderboardType: function() {
      console.log(this.leaderboardType)
      this.rankingType = this.leaderboardType
      console.log('this.rankingType======', this.rankingType)
    }
  },
  methods: {
    handleEdit(record) {
      this.mdlUser = { ...record }
      this.mdlUser.ranking_type = this.model.ranking_type
      this.visibleUser = true
    },
    handleOkUser() {
      const form = this.$refs.userModal.form
      this.confirmLoadingUser = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const params = {
            user_name: values.user_name,
            web_match_ranking_detail_id: values.web_match_ranking_detail_id,
            rank_type: values.ranking_type,
            value: values.value,
            join_time: moment(values['join_time']).format('YYYY-MM-DD')
          }
          console.log('params=====', params)

          // 新增
          updateLeaderboardsListUser(params).then(res => {
            if (res.code === 1) {
              console.log(res)
              this.visibleUser = false
              this.confirmLoadingUser = false
              // 重置表单数据
              form.resetFields()
              // 刷新表格
              this.$refs.teamTable.refresh()
              this.$message.success(res.msg)
            } else {
              this.confirmLoadingUser = false
              this.$message.error(res.msg)
            }
          })
        } else {
          this.confirmLoadingUser = false
        }
      })
    },
    handleCancelUser() {
      this.mdlUser = null
      this.visibleUser = false
    },
    onDelete(id) {
      const requestParameters = {
        web_match_ranking_detail_id: id
      }
      deleteLeaderboardsListUser(requestParameters).then(res => {
        console.log(res)

        // 刷新表格
        this.$refs.teamTable.refresh()

        this.$message.info(res.msg)
      })
    },
    // modal关闭之后回调函数
    afterClose() {
      this.isSelected = false
      // 重置查询条件
      this.queryParam = {}
      // 清除选择
      this.selectedRowKeys = []
    }
  }
}
</script>
<style scoped></style>

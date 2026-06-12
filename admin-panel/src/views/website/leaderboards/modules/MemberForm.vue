<template>
  <a-modal
    title="新增用户"
    :width="1400"
    :visible="visible"
    :loading="loading"
    :afterClose="afterClose"
    destroyOnClose
    ok-text="添加"
    cancel-text="取消"
    :ok-button-props="{ props: { disabled: !isSelected } }"
    @ok="
     handleAdd
    "
    @cancel="
      () => {
        $emit('cancel')
      }
    "
  >
    <a-spin :spinning="loading">
      <a-card :bordered="false">
        <div class="table-page-search-wrapper">
          <a-form layout="inline">
            <a-row :gutter="48">
              <a-col
                :md="8"
                :sm="24"
              >
                <a-form-item label="关键字">
                  <a-input
                    v-model="queryParam.search"
                    placeholder="用户名/手机号"
                  />
                </a-form-item>
              </a-col>
              <a-col
                :md="4"
                :sm="24"
              >
                <a-button
                  type="primary"
                  @click="$refs.memberTable.refresh(true)"
                >查询</a-button>
                <a-button
                  style="margin-left: 8px"
                  @click="resetTable(1)"
                >重置</a-button>
              </a-col>
            </a-row>
            <a-row :gutter="48">
              <a-col
                :md="8"
                :sm="24"
              >
                <a-form-item label="用户类型">
                  <a-radio-group
                    default-value=""
                    button-style="solid"
                    v-model="queryParam.sys_user_type_id"
                    @change="onRadioChange"
                  >
                    <a-radio-button
                      :value="item.value"
                      v-for="item of UserTypeList"
                      :key="item.value"
                    >
                      {{ item.label }}
                    </a-radio-button>
                  </a-radio-group>
                </a-form-item>
              </a-col>
              <a-col
                :md="2"
                :sm="24"
              >
                <a-button
                  @click="resetTable(2)"
                  type="primary"
                >重置</a-button>
              </a-col>
            </a-row>
            <!-- 会员状态 -->
            <a-row :gutter="48">
              <a-col
                :md="8"
                :sm="24"
              >
                <a-form-item label="会员状态">
                  <a-radio-group
                    :default-value="membersStatusDefaultValue"
                    button-style="solid"
                    v-model="queryParam.members_status"
                    @change="onRadioChangeMembers"
                  >
                    <a-radio-button
                      :value="item.value"
                      v-for="item of membersStatus"
                      :key="item.value"
                    >
                      {{ item.name }}
                    </a-radio-button>
                  </a-radio-group>
                </a-form-item>
              </a-col>
              <a-col
                :md="2"
                :sm="24"
              >
                <a-button
                  type="primary"
                  @click="resetTable(3)"
                >重置</a-button>
              </a-col>
            </a-row>
          </a-form>
        </div>
        <s-table
          ref="memberTable"
          size="default"
          rowKey="user_id"
          :columns="columns"
          :data="loadData"
          :alert="false"
          :pagination="pagination"
          :row-selection="rowSelection"
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
                @confirm="() => onDelete(record.user_group_id)"
              >
                <a href="javascript:;">删除</a>
              </a-popconfirm>
            </template>
          </span>
          <span
            slot="sex_name"
            slot-scope="text"
          >
            {{ text }}
          </span>
          <span
            slot="user_type_name"
            slot-scope="text"
          >
            {{ text }}
          </span>
          <span
            slot="ranking_value_format"
            slot-scope="text, record"
          >
            {{ text+ record.unit }}
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
      </a-card>
    </a-spin>
    <add-form
      ref="addModal"
      :visible="visibleAdd"
      :loading="confirmLoadingAdd"
      :model="mdlAdd"
      @cancel="handleCancelAdd"
      @ok="handleOkAdd"
    />
  </a-modal>
</template>

<script>
import storage from 'store'
import moment from 'moment'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import { STable, Ellipsis } from '@/components'
import AddForm from './AddForm'
import { getRankingUserList, getUserTypeList } from '@/api/user'
import { addleaderboardsListUser } from '@/api/website'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const membersStatus = [
  {
    name: '待审核',
    value: 0
  },
  {
    name: '已审核',
    value: 1
  },
  {
    name: '已驳回',
    value: 2
  }
]
const columns = [
  {
    title: '用户ID',
    dataIndex: 'user_id'
  },
  {
    title: '排名',
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
    title: '性别',
    dataIndex: 'sex_name',
    scopedSlots: { customRender: 'sex_name' }
  },
  {
    title: '手机号码',
    dataIndex: 'phone'
  },

  {
    title: '用户数据',
    dataIndex: 'ranking_value_format',
    scopedSlots: { customRender: 'ranking_value_format' }
  },
  {
    title: '成绩获取时间',
    dataIndex: 'stop_time'
  },
  {
    title: '会员状态',
    dataIndex: 'members_title'
  },
  {
    title: '用户类型',
    dataIndex: 'user_type_name',
    scopedSlots: { customRender: 'user_type_name' }
  }

  // {
  //   title: '操作',
  //   dataIndex: 'operation',
  //   scopedSlots: { customRender: 'operation' }
  // }
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
    Ellipsis,
    AddForm
  },

  data() {
    this.columns = columns
    return {
      pagination,
      // Add model 新增用户到榜单
      visibleAdd: false,
      confirmLoadingAdd: false,
      mdlAdd: null,
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
        const obj = {
          ranking_type: this.model.ranking_type,
          start_time: this.model.start_time,
          stop_time: this.model.stop_time
        }
        const requestParameters = Object.assign(obj, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        getUserTypeList({}).then(res => {
          console.log('用户类型===', res)
          if (res.code === 1) {
            this.UserTypeList = res.data.list
          }
        })
        return getRankingUserList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      imgURL: '',
      UserTypeList: [],
      isSelected: false,
      userId: '',
      selectedRowKeys: [],
      membersStatusDefaultValue: '',
      membersStatus,
      selectedRows: []
    }
  },
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
    console.log(this.isSelected)
  },
  computed: {
    rowSelection() {
      const that = this
      return {
        type: 'radio',
        selectedRowKeys: that.selectedRowKeys,
        onChange: (selectedRowKeys, selectedRows) => {
          that.isSelected = true
          that.userId = selectedRows[0].user_id
          that.selectedRowKeys = selectedRowKeys
          that.selectedRows = selectedRows
          console.log(
            `selectedRowKeys: ${selectedRowKeys}`,
            'selectedRows: ',
            selectedRows
          )
        }
      }
    }
  },

  methods: {
    // 用户新增
    handleAdd() {
      console.log(this.selectedRowKeys)
      this.model.userId = this.selectedRowKeys[0]
      this.model.ranking_value_format =
        this.selectedRows[0].ranking_value_format
      this.model.unit = this.selectedRows[0].unit
      this.model.stop_time = this.selectedRows[0].stop_time
      // this.model.unit = this.selectedRows[0].start_time
      this.mdlAdd = this.model
      console.log('this.mdlAdd==', this.mdlAdd)
      this.visibleAdd = true
    },
    handleOkAdd() {
      const form = this.$refs.addModal.addForm
      this.confirmLoadingAdd = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const params = {
            user_id: values.userId,
            web_match_ranking_id: values.web_match_ranking_id,
            ranking_type: values.ranking_type,
            value: values.ranking_value_format,
            join_time: moment(values['stop_date']).format('YYYY-MM-DD HH:mm:ss')
          }
          console.log('params=====', params)

          // 新增
          addleaderboardsListUser(params).then(res => {
            if (res.code === 1) {
              console.log(res)
              this.visibleAdd = false
              this.confirmLoadingAdd = false
              this.$message.success(res.msg)
            } else {
              this.confirmLoadingAdd = false
              this.$message.error(res.msg)
            }
          })
        } else {
          this.confirmLoadingAdd = false
        }
      })
    },
    handleCancelAdd() {
      this.mdlAdd = null
      this.visibleAdd = false
      const form = this.$refs.addModal.addForm
      console.log(this.$refs)
      console.log(form)
      form.resetFields() // 清理表单数据（可不做）
    },
    // 重置
    resetTable(key) {
      console.log(key)
      switch (key) {
        case 1:
          this.queryParam.search = undefined
          break
        case 2:
          this.queryParam.sys_user_type_id = undefined
          break
        case 3:
          this.queryParam.members_status = undefined
          break
        default:
          break
      }

      this.$refs.memberTable.refresh(true)
    },

    onRadioChange(event) {
      console.log(event)
      this.$refs.memberTable.refresh(true)
    },
    onRadioChangeMembers() {
      console.log(event)
      this.$refs.memberTable.refresh(true)
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

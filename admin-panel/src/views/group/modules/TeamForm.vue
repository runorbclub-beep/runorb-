<template>
  <a-modal
    title="查看成员"
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
        </div>

        <s-table
          ref="teamTable"
          size="default"
          rowKey="user_id"
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
              <!-- <a @click="handleEdit(record)">编辑</a> -->
              <!-- <a-divider type="vertical" /> -->
              <a-popconfirm
                v-if="loadData.length"
                title="确认删除?"
                @confirm="() => onDelete(record.user_group_id, record.user_id)"
              >
                <a href="javascript:;">删除</a>
              </a-popconfirm>
            </template>
          </span>
          <span
            slot="group_property"
            slot-scope="text"
          >
            {{ text === '1' ? '官方' : '非官方' }}
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
            slot="group_logo"
            slot-scope="text, record"
          >
            <img
              style="width: 50px; heigth: 50px"
              :src="record.group_logo"
            />
          </span>
        </s-table>
      </a-card>
    </a-spin>
  </a-modal>
</template>

<script>
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import { STable, Ellipsis } from '@/components'
import { getGroupUserList, deleteGroupUser } from '@/api/group'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '用户ID',
    dataIndex: 'user_id'
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
    title: '自我介绍',
    dataIndex: 'self_description',
    ellipsis: true
  },
  {
    title: '团队名称',
    dataIndex: 'group_title',
    ellipsis: true
  },
  {
    title: '团队logo',
    dataIndex: 'group_logo',
    scopedSlots: { customRender: 'group_logo' }
  },
  {
    title: '团队性质',
    dataIndex: 'is_official',
    scopedSlots: { customRender: 'group_property' }
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
    Ellipsis
  },

  data() {
    this.columns = columns
    return {
      pagination,
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
        const requestParameters = Object.assign(
          { user_group_id: this.model.user_group_id },
          parameter,
          this.queryParam
        )
        console.log('loadData request parameters:', requestParameters)
        return getGroupUserList(requestParameters).then(res => {
          console.log(res)
          console.log('')
          return Object.assign(res.data, parameter)
        })
      },
      imgURL: '',
      UserTypeList: [],
      isSelected: false,
      userId: '',
      selectedRowKeys: []
    }
  },
  created() {
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
  activated() {
    // 只刷新数据，不改变整体的缓存
    console.log('activated')
  },

  methods: {
    onDelete(groupID, userID) {
      const requestParameters = {
        user_group_id: groupID,
        user_id: userID
      }
      deleteGroupUser(requestParameters).then(res => {
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

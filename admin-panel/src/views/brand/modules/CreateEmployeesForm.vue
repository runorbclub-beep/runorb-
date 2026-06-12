<template>
  <a-modal
    title="添加店员"
    :width="1400"
    :visible="visible"
    :loading="loading"
    :afterClose="afterClose"
    destroyOnClose
    ok-text="添加"
    cancel-text="取消"
    :ok-button-props="{ props: { disabled: !isSelected } }"
    @ok="
      () => {
        $emit('ok', phone)
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
                    placeholder="用户名/手机号"
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
                    @click="$refs.userTable.refresh(true)"
                  >查询</a-button>
                  <a-button
                    style="margin-left: 8px"
                    @click="resetTable"
                  >重置</a-button>
                </span>
              </a-col>
            </a-row>

          </a-form>
        </div>

        <s-table
          ref="userTable"
          rowKey="user_id"
          :columns="columns"
          :data="loadData"
          :alert="false"
          :pagination="pagination"
          :row-selection="rowSelection"
        >
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
  </a-modal>
</template>

<script>
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import { STable, Ellipsis } from '@/components'
import { getUserList } from '@/api/user'
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
    title: '自我介绍',
    dataIndex: 'self_description',
    ellipsis: true
  },
  {
    title: '用户地址',
    dataIndex: 'address'
  },

  {
    title: '用户类型',
    dataIndex: 'user_type_name',
    scopedSlots: { customRender: 'user_type_name' }
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
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      // 高级搜索 展开/关闭
      advanced: false,
      // 查询参数
      queryParam: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: parameter => {
        console.log(parameter)
        const requestParameters = Object.assign(
          { sys_user_type_id: '1809649560981504' },
          parameter,
          this.queryParam
        )
        console.log('loadData request parameters:', requestParameters)

        return getUserList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },

      isSelected: false,
      phone: '',
      selectedRowKeys: []
    }
  },
  created() {},
  computed: {
    rowSelection() {
      const that = this
      return {
        type: 'radio',
        selectedRowKeys: that.selectedRowKeys,
        onChange: (selectedRowKeys, selectedRows) => {
          that.isSelected = true
          that.phone = selectedRows[0].phone
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

  methods: {
    resetTable() {
      this.queryParam = {}
      this.$refs.userTable.refresh(true)
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

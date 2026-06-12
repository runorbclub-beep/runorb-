<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="table-operator">
        <a-button
          type="primary"
          icon="plus"
          @click="handleAdd"
        >新建</a-button>
        <a-dropdown
          v-action:edit
          v-if="selectedRowKeys.length > 0"
        >
          <a-menu slot="overlay">
            <a-menu-item key="1">
              <a-icon type="delete" />删除
            </a-menu-item>
          </a-menu>
          <a-button style="margin-left: 8px"> 批量操作
            <a-icon type="down" />
          </a-button>
        </a-dropdown>
      </div>

      <s-table
        ref="table"
        size="default"
        rowKey="sys_app_advertising_id"
        :columns="columns"
        :data="loadData"
        :alert="false"
        showPagination="auto"
      >
        <span
          slot="operation"
          slot-scope="text, record"
        >
          <template>
            <span v-if="record.status === 0"><a @click="handleStatus(record.sys_app_advertising_id,1)">开启</a>
            </span>
            <span v-else>
              <a-popconfirm
                v-if="loadData.length"
                title="确认禁用?"
                @confirm="() => handleStatus(record.sys_app_advertising_id,0)"
              >
                <a href="javascript:;">禁用</a>
              </a-popconfirm>
            </span>
            <span>
              <a-divider type="vertical" />
              <a-popconfirm
                v-if="loadData.length"
                title="确认删除?"
                @confirm="() => handleStatus(record.sys_app_advertising_id,-1)"
              >
                <a href="javascript:;">删除</a>
              </a-popconfirm>
            </span>

          </template>
        </span>
        <span
          slot="img_375_812"
          slot-scope="text, record"
        >
          <img
            style="width: 50px; heigth: 50px"
            :src="record.img_375_812"
          />
        </span>
        <span
          slot="img_414_896"
          slot-scope="text, record"
        >
          <img
            style="width: 50px; heigth: 50px"
            :src="record.img_414_896"
          />
        </span>
        <span
          slot="status"
          slot-scope="text"
        >
          <a-tag
            v-if="text === 0"
            color="#f50"
          >
            禁用
          </a-tag>
          <a-tag
            v-else
            color="#108ee9"
          >
            开启
          </a-tag>
        </span>
      </s-table>
    </a-card>
    <app-start-page-form
      ref="createModal"
      :visible="visible"
      :loading="confirmLoading"
      :model="mdl"
      @cancel="handleCancel"
      @ok="handleOk"
    />
  </page-header-wrapper>
</template>

<script>
import { STable, Ellipsis } from '@/components'
import AppStartPageForm from './modules/AppStartPageForm'
import {
  getAdvertisingList,
  updateAdvertising,
  addAdvertising
} from '@/api/system'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  // {
  //   title: '赛事ID',
  //   dataIndex: 'banner_matchs_id',
  // },
  {
    title: 'ID',
    dataIndex: 'sys_app_advertising_id'
  },
  {
    title: '标题',
    dataIndex: 'advertising_name'
  },
  {
    title: '图片375*812',
    dataIndex: 'img_375_812',
    align: 'center',
    scopedSlots: { customRender: 'img_375_812' }
  },
  {
    title: '图片414*896',
    dataIndex: 'img_414_896',
    align: 'center',
    scopedSlots: { customRender: 'img_414_896' }
  },
  {
    title: '状态',
    dataIndex: 'status',
    align: 'center',
    scopedSlots: { customRender: 'status' }
  },

  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'AppBanner',
  components: {
    STable,
    AppStartPageForm,
    Ellipsis
  },
  data() {
    this.columns = columns
    return {
      pagination,
      // create model
      visible: false,
      confirmLoading: false,
      mdl: null,
      // 高级搜索 展开/关闭
      advanced: false,
      // 查询参数
      queryParam: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: parameter => {
        console.log(parameter)
        const requestParameters = Object.assign({}, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        return getAdvertisingList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      selectedRowKeys: [],
      selectedRows: [],
      brandDict: [],
      categoryDict: [],
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: ''
    }
  },
  filters: {},
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
    console.log(this.imgURL)
  },
  computed: {
    rowSelection() {
      return {
        selectedRowKeys: this.selectedRowKeys,
        onChange: this.onSelectChange
      }
    }
  },

  methods: {
    handleAdd() {
      this.mdl = null
      this.visible = true
    },
    handleEdit(record) {
      this.visible = true
      this.mdl = { ...record }
    },
    handleCancel() {
      this.visible = false

      const form = this.$refs.createModal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    handleOk() {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const params = {
            advertising_name: values.advertising_name,
            img_375_812:
              values.img_375_812[0].response.data.file_path.file_path,
            img_414_896: values.img_414_896[0].response.data.file_path.file_path
          }
          console.log('params=====', params)

          addAdvertising(params)
            .then(res => {
              if (res.code === 1) {
                this.visible = false
                this.confirmLoading = false
                // 重置表单数据
                form.resetFields()
                // 刷新表格
                this.$refs.table.refresh()

                this.$message.success(res.msg)
              } else {
                this.$message.error(res.msg)
              }
            })
            .catch(err => {
              console.log(err)
            })
        } else {
          this.confirmLoading = false
        }
      })
    },
    handleStatus(id, status) {
      console.log(id)
      const params = {
        sys_app_advertising_id: id,
        status: status
      }
      updateAdvertising(params).then(res => {
        console.log(res)
        // 刷新表格
        if (res.code === 1) {
          this.$refs.table.refresh()
          this.$message.success(res.msg)
        } else {
          this.$message.error(res.msg)
        }
      })
    },

    onSelectChange(selectedRowKeys, selectedRows) {
      this.selectedRowKeys = selectedRowKeys
      this.selectedRows = selectedRows
    }
  }
}
</script>

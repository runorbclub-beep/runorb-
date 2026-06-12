<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="table-operator">
        <a-button
          type="primary"
          icon="plus"
          @click="handleAdd"
        >新建</a-button>
      </div>

      <s-table
        ref="table"
        size="default"
        rowKey="website_aboutme_id"
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
              @confirm="() => onDelete(record.website_aboutme_id)"
            >
              <a href="javascript:;">删除</a>
            </a-popconfirm>
          </template>
        </span>
        <span
          slot="content"
          slot-scope="text"
        >
          <div v-html="text"></div>
        </span>
      </s-table>
    </a-card>
    <about-form
      ref="createModal"
      :visible="visible"
      :loading="confirmLoading"
      :model="mdl"
      :data="data"
      @cancel="handleCancel"
      @ok="handleOk"
    />
  </page-header-wrapper>
</template>

<script>
import { STable, Ellipsis } from '@/components'
import aboutForm from './modules/aboutForm'
import { getAboutMeList, deleteAboutMe, addAboutMe } from '@/api/website'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  // {
  //   title: '文章ID',
  //   dataIndex: 'website_aboutme_id'
  // },
  {
    title: '文章标题',
    dataIndex: 'title'
  },
  // {
  //   title: '文章标题(英文)',
  //   dataIndex: 'title_en'
  // },

  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'AboutUs',
  components: {
    STable,
    Ellipsis,
    aboutForm
  },
  data() {
    this.columns = columns
    return {
      data: [],
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
        return getAboutMeList(requestParameters).then(res => {
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
      this.visible = true
      this.mdl = null
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

          const requestParameters = {
            title: values.title,
            content: values.content,
            title_en: values.title_en,
            content_en: values.content_en
          }
          console.log('requestParameters=====', requestParameters)
          if (values.website_aboutme_id > 0) {
            const obj = { website_aboutme_id: values.website_aboutme_id }
            console.log('编辑')
            addAboutMe({ ...requestParameters, ...obj })
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
            // 新增
            addAboutMe(requestParameters).then(res => {
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
          }
        } else {
          this.confirmLoading = false
        }
      })
    },
    onDelete(key) {
      console.log(key)
      const requestParameters = {
        website_aboutme_id: key
      }
      deleteAboutMe(requestParameters).then(res => {
        console.log(res)

        // 刷新表格
        this.$refs.table.refresh()

        this.$message.info(res.msg)
      })
    },

    onSelectChange(selectedRowKeys, selectedRows) {
      this.selectedRowKeys = selectedRowKeys
      this.selectedRows = selectedRows
    }
  }
}
</script>

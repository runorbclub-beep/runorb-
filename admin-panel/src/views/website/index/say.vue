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
        rowKey="website_home_id"
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
              @confirm="() => onDelete(record.website_home_id)"
            >
              <a href="javascript:;">删除</a>
            </a-popconfirm>
          </template>
        </span>
        <span
          slot="source_type"
          slot-scope="text"
        >
          {{ text === 0 ? '图片' : '视频' }}
        </span>
        <span
          slot="content"
          slot-scope="text"
        >
          <div v-html="text"></div>
        </span>
        <span
          slot="index"
          slot-scope="text, record"
        >
          <div class="index_wrap">
            <a-input-number
              class="index_input"
              :min="1"
              :disabled="homeId === record.website_home_id ? disabled : true"
              :default-value="text"
              :formatter="limitNumber"
              :parser="limitNumber"
              @change="onInputChange"
            />
            <div class="index_btn">
              <a-button
                type="link"
                @click="toggle(record.website_home_id, text)"
              >
                <a-icon
                  v-if="homeId === record.website_home_id && !disabled"
                  type="save"
                />
                <a-icon
                  v-else
                  type="edit"
                />
              </a-button>
              <a-button
                type="link"
                @click="cancel"
                v-if="homeId === record.website_home_id && !disabled"
              >
                <a-icon
                  class="icon_style"
                  type="undo"
                />
              </a-button>
            </div>
          </div>
        </span>
        <span
          slot="source"
          slot-scope="text, record"
        >
          <img
            v-if="record.source_type === 0"
            style="width: 50px; heigth: 50px"
            :src="imgURL + '/' + record.source"
          />
          <video
            v-else
            style="width: 50px; heigth: 50px"
            :src="imgURL + '/' + record.source"
          ></video>
        </span>
      </s-table>
    </a-card>
    <say-form
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
import sayForm from './modules/sayForm'

import {
  getHomeList,
  deleteHome,
  updateHomeIndex,
  addHome
} from '@/api/website'
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
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '首页内容ID',
    dataIndex: 'website_home_id'
  },
  {
    title: '中文标题',
    dataIndex: 'title_cn'
  },
  {
    title: '英文标题',
    dataIndex: 'title_en'
  },
  {
    title: '副标题',
    dataIndex: 'subtitle'
  },
  {
    title: '资源类型',
    dataIndex: 'source_type',
    scopedSlots: { customRender: 'source_type' }
  },

  // {
  //   title: '资源文件地址',
  //   dataIndex: 'source',
  //   scopedSlots: { customRender: 'source' },
  // },
  // {
  //   title: '文章内容',
  //   dataIndex: 'content',
  //   ellipsis: true,
  //   scopedSlots: { customRender: 'content' },
  // },
  {
    title: '优先级排序',
    dataIndex: 'index',
    scopedSlots: { customRender: 'index' }
  },

  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'Say',
  components: {
    STable,
    Ellipsis,
    sayForm
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
        return getHomeList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      selectedRowKeys: [],
      selectedRows: [],
      brandDict: [],
      categoryDict: [],
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      disabled: true,
      homeId: '',
      limitNumber,
      inputNumber: null
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
    cancel() {
      this.homeId = ''
    },
    onInputChange(value) {
      // console.log('输入框值', value)
      this.inputNumber = value
    },
    toggle(id, text) {
      // console.log(text)
      // console.log(id)
      // console.log(this.homeId)
      if (this.homeId !== id) {
        this.disabled = true
        // console.log('切换==', id)
      } else {
        if (!this.disabled) {
          // console.log('改变值', this.inputNumber)
          // console.log('保存提交==', id)

          const params = {
            website_home_id: id,
            index: this.inputNumber === null ? text : this.inputNumber
          }
          updateHomeIndex({ ...params })
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
      this.homeId = id
      this.disabled = !this.disabled
    },
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
            title_en: values.title_en,
            title_cn: values.title_cn,
            subtitle: values.subtitle,
            index: values.index,
            content: values.content,
            content_en: values.content_en,
            source:
              values.website_home_id > 0
                ? this.ModifyImg
                  ? values.source[0].response.data.file_path.file_path
                  : values.source
                : values.source[0].response.data.file_path.file_path,
            source_type: values.source_type
          }
          console.log('requestParameters=====', requestParameters)
          if (values.website_home_id > 0) {
            const obj = { website_home_id: values.website_home_id }
            console.log('编辑')
            addHome({ ...requestParameters, ...obj })
              .then(res => {
                console.log(res)
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
            addHome(requestParameters).then(res => {
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
        website_home_id: key
      }
      deleteHome(requestParameters).then(res => {
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
<style scoped>
.index_wrap {
  display: flex;
}
</style>

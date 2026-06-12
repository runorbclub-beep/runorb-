<template>
  <page-header-wrapper>
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
                  placeholder="活动标题"
                />
              </a-form-item>
            </a-col>
            <a-col
              :md="8"
              :sm="24"
            >

              <a-button
                type="primary"
                @click="$refs.table.refresh(true)"
              >查询</a-button>
              <a-button
                style="margin-left: 8px"
                @click="resetTable"
              >重置</a-button>

            </a-col>
          </a-row>
        </a-form>
      </div>

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
        rowKey="sys_activity_id"
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
              @confirm="() => onDelete(record.sys_activity_id)"
            >
              <a href="javascript:;">删除</a>
            </a-popconfirm>
          </template>
        </span>
        <span
          slot="news_type"
          slot-scope="text"
        >
          {{ text === 1 ? '资讯' : '新闻' }}
        </span>
        <span
          slot="news_content"
          slot-scope="text"
        >
          <div v-html="text"></div>
        </span>

        <span
          slot="image"
          slot-scope="text, record"
        >
          <a-avatar
            :size="80"
            shape="square"
            :src="`${record.img}`"
          />
        </span>
      </s-table>
    </a-card>
    <activities-form
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
import activitiesForm from './modules/activitiesForm'
import moment from 'moment'
import { getActivityList, deleteActivity, addActivity } from '@/api/website'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '活动ID',
    dataIndex: 'sys_activity_id'
  },
  {
    title: '活动标题',
    dataIndex: 'title_cn'
  },
  // {
  //   title: '活动类型',
  //   dataIndex: 'news_type',
  //   scopedSlots: { customRender: 'news_type' }
  // },

  {
    title: '活动首图',
    dataIndex: 'img',
    scopedSlots: { customRender: 'image' }
  },
  {
    title: '活动观看数',
    dataIndex: 'view_num'
  },
  // {
  //   title: '活动内容',
  //   dataIndex: 'news_content',
  //   scopedSlots: { customRender: 'news_content' },
  // },
  {
    title: '创建日期',
    dataIndex: 'created_date'
  },
  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'ActivitiesList',
  components: {
    STable,
    Ellipsis,
    activitiesForm
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
        return getActivityList(requestParameters).then(res => {
          this.data = res.data.list
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
    resetTable() {
      this.queryParam = {}
      this.$refs.table.refresh(true)
    },
    handleAdd() {
      this.visible = true
      this.mdl = null
      // this.$router.push({
      //   path: '/website/activitiesList/edit',
      //   query: {
      //     visible: true,
      //     mdl: null
      //   }
      // })
    },
    handleEdit(record) {
      this.visible = true
      this.mdl = { ...record }
      // this.$router.push({
      //   path: '/website/activitiesList/edit',
      //   query: {
      //     visible: true,
      //     mdl: JSON.stringify({ ...record })
      //   }
      // })
    },
    handleCancel() {
      this.visible = false

      const form = this.$refs.createModal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    handleOk(ModifyImg) {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)

          const requestParameters = {
            title_cn: values.title_cn,
            title_en: values.title_en,
            content_cn: values.content_cn,
            content_en: values.content_en,
            img:
              values.sys_activity_id > 0
                ? ModifyImg
                  ? values.img[0].response.data.file_path.file_path
                  : values.img
                : values.img[0].response.data.file_path.file_path,
            // news_type: values.news_type === '资讯' ? 1 : 2,
            created_date: moment(values['created_date']).format(
              'YYYY-MM-DD H:m:s'
            )
          }
          console.log('requestParameters=====', requestParameters)
          if (values.sys_activity_id > 0) {
            const obj = { sys_activity_id: values.sys_activity_id }
            console.log('编辑')
            addActivity({ ...requestParameters, ...obj })
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
            addActivity(requestParameters).then(res => {
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
        sys_activity_id: key
      }
      deleteActivity(requestParameters).then(res => {
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

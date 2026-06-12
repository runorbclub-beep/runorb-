<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <!-- <div class="table-page-search-wrapper">
        <a-form layout="inline">
          <a-row :gutter="48">
            <a-col :md="8" :sm="24">
              <a-form-item label="关键字">
                <a-input v-model="queryParam.search" placeholder="文章标题" />
              </a-form-item>
            </a-col>
            <a-col :md="(!advanced && 8) || 24" :sm="24">
              <span
                class="table-page-search-submitButtons"
                :style="(advanced && { float: 'right', overflow: 'hidden' }) || {}"
              >
                <a-button type="primary" @click="$refs.table.refresh(true)">查询</a-button>
                <a-button style="margin-left: 8px" @click="() => (this.queryParam = {})">重置</a-button>
              </span>
            </a-col>
          </a-row>
        </a-form>
      </div> -->

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
        rowKey="matchs_banner_id"
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
              @confirm="() => onDelete(record.matchs_banner_id)"
            >
              <a href="javascript:;">删除</a>
            </a-popconfirm>
          </template>
        </span>
        <span
          slot="image"
          slot-scope="text, record"
        >
          <img
            style="width: 50px; heigth: 50px"
            :src="record.img_path"
          />
        </span>
      </s-table>
    </a-card>
    <create-form
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
import CreateForm from './modules/CreateForm'
import {
  getAppBannerList,
  deleteAppBannerList,
  addAppBannerList
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
    title: '图片ID',
    dataIndex: 'matchs_banner_id'
  },

  {
    title: '赛事宣传图',
    dataIndex: 'img_path',
    align: 'center',
    scopedSlots: { customRender: 'image' }
  },
  {
    title: '赛事标题',
    dataIndex: 'match_title'
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
    CreateForm,
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
        return getAppBannerList(requestParameters).then(res => {
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
    handleOk(ModifyImg) {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        const bannerMatchsIdToNumber = values.match_title - 0
        let bannerMatchsId = ''
        if (!errors) {
          if (
            typeof bannerMatchsIdToNumber === 'number' &&
            !isNaN(bannerMatchsIdToNumber)
          ) {
            bannerMatchsId = values.match_title
          } else {
            bannerMatchsId = values.banner_matchs_id
          }
          console.log('values', values)
          const requestParameters = {
            banner_matchs_id: bannerMatchsId,
            img_path:
              values.matchs_banner_id > 0
                ? ModifyImg
                  ? values.img_path[0].response.data.file_path.file_path
                  : values.img_path
                : values.img_path[0].response.data.file_path.file_path
          }
          console.log('requestParameters=====', requestParameters)
          if (values.matchs_banner_id > 0) {
            const obj = { matchs_banner_id: values.matchs_banner_id }
            console.log('编辑')
            addAppBannerList({ ...requestParameters, ...obj })
              .then(res => {
                if (res.code === 1) {
                  this.visible = false
                  this.confirmLoading = false
                  // 重置表单数据
                  form.resetFields()
                  // 刷新表格
                  this.$refs.table.refresh()

                  this.$message.info(res.msg)
                } else {
                  this.$message.info(res.msg)
                }
              })
              .catch(err => {
                console.log(err)
              })
          } else {
            // 新增
            addAppBannerList(requestParameters).then(res => {
              console.log(res)
              this.visible = false
              this.confirmLoading = false
              // 重置表单数据
              form.resetFields()
              // 刷新表格
              this.$refs.table.refresh()

              this.$message.info('新增成功')
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
        matchs_banner_id: key
      }
      deleteAppBannerList(requestParameters).then(res => {
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

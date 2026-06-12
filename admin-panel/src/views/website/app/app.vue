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
        rowKey="website_app_id"
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
              @confirm="() => onDelete(record.website_app_id)"
            >
              <a href="javascript:;">删除</a>
            </a-popconfirm>
          </template>
        </span>
        <!-- <span slot="app_image" slot-scope="text, record">
          <img style="width: 50px; heigth: 50px" :src="imgURL + '/' + record.app_image" />
        </span> -->
        <span
          slot="app_image_ios"
          slot-scope="text, record"
        >
          <a-avatar
            shape="square"
            :size="44"
            icon="user"
            :src=" record.app_image_ios"
          />
        </span>
        <span
          slot="app_image_android"
          slot-scope="text, record"
        >
          <a-avatar
            shape="square"
            :size="44"
            icon="user"
            :src=" record.app_image_android"
          />
        </span>
      </s-table>

      <create-form
        ref="createModal"
        :visible="visible"
        :loading="confirmLoading"
        :model="mdl"
        @cancel="handleCancel"
        @ok="handleOk"
      />
    </a-card>
  </page-header-wrapper>
</template>

<script>
import { STable, Ellipsis } from '@/components'
import { getVersionList, deleteVersion, addVersion } from '@/api/website'

import CreateForm from './modules/CreateForm'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  // {
  //   title: '版本号ID',
  //   dataIndex: 'website_app_id'
  // },
  {
    title: 'IOS版本号',
    dataIndex: 'app_version_ios',
    align: 'center'
  },

  {
    title: 'android版本号',
    dataIndex: 'app_version_android',
    align: 'center'
  },
  {
    title: 'android版本校验号',
    dataIndex: 'app_android_code',
    align: 'center'
  },
  {
    title: 'IOS版二维码',
    dataIndex: 'app_image_ios',
    align: 'center',
    scopedSlots: { customRender: 'app_image_ios' }
  },
  {
    title: 'Android版二维码',
    dataIndex: 'app_image_android',
    align: 'center',
    scopedSlots: { customRender: 'app_image_android' }
  },
  {
    title: '更新时间',
    dataIndex: 'app_update_time'
  },

  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'App',
  components: {
    STable,
    Ellipsis,
    CreateForm
  },
  data() {
    this.columns = columns
    return {
      pagination,
      // create model 编辑/新增
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
        return getVersionList(requestParameters).then(res => {
          console.log('初始化数据==', res)
          return Object.assign(res.data, parameter)
        })
      },
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: ''
    }
  },
  filters: {},
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
  },
  computed: {},
  methods: {
    // 新增
    handleAdd() {
      this.mdl = null
      this.visible = true
    },
    // 编辑
    handleEdit(record) {
      this.visible = true
      this.mdl = { ...record }
    },

    handleOk(ModifyImg, ModifyImgIOS) {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          console.log('ModifyImg', ModifyImg)
          const requestParameters = {
            app_android_code: values.app_android_code,
            app_version_ios: values.app_version_ios,
            app_version_android: values.app_version_android,
            app_description_ios_cn: values.app_description_ios_cn,
            app_description_ios_en: values.app_description_ios_en,
            app_description_android_cn: values.app_description_android_cn,
            app_description_android_en: values.app_description_android_en,
            app_image_ios:
              values.website_app_id > 0
                ? ModifyImgIOS
                  ? values.app_image_ios[0].response.data.file_path.file_path
                  : values.app_image_ios
                : values.app_image_ios[0].response.data.file_path.file_path,
            app_image_android:
              values.website_app_id > 0
                ? ModifyImg
                  ? values.app_image_android[0].response.data.file_path
                      .file_path
                  : values.app_image_android
                : values.app_image_android[0].response.data.file_path.file_path
            // app_image:
            //   values.website_app_id > 0
            //     ? ModifyImg
            //       ? values.app_image[0].response.data.file_path.file_path
            //       : values.app_image
            //     : values.app_image[0].response.data.file_path.file_path,
          }
          console.log('requestParameters=====', requestParameters)
          if (values.website_app_id > 0) {
            const obj = { website_app_id: values.website_app_id }
            console.log('编辑')
            addVersion({ ...requestParameters, ...obj })
              .then(res => {
                this.visible = false
                this.confirmLoading = false
                // 重置表单数据
                form.resetFields()
                // 刷新表格
                this.$refs.table.refresh()

                this.$message.info('修改成功')
              })
              .catch(err => {
                console.log(err)
              })
          } else {
            // 新增
            addVersion(requestParameters).then(res => {
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
        website_app_id: key
      }
      deleteVersion(requestParameters).then(res => {
        console.log(res)

        // 刷新表格
        this.$refs.table.refresh()

        this.$message.info(res.msg)
      })
    },
    handleCancel() {
      this.visible = false
      const form = this.$refs.createModal.form
      form.resetFields() // 清理表单数据（可不做）
    },

    resetSearchForm() {
      this.queryParam = {}
    }
  }
}
</script>

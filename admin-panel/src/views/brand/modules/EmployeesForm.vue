<template>
  <a-drawer
    title="店员列表"
    width="90%"
    :visible="visible"
    :confirmLoading="loading"
    @close="
      onClose
    "
    :afterVisibleChange="afterVisibleChange"
  >

    <div class="table-operator">
      <a-button
        type="primary"
        icon="plus"
        @click="handleAdd"
      >新建</a-button>
    </div>
    <a-table
      ref="table"
      rowKey="id"
      :columns="columns"
      :data-source="employeesList"
      :pagination="pagination"
    >
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
      <span
        slot="operation"
        slot-scope="text, record"
      >
        <template>
          <span>
            <a @click="handleEdit(record)">编辑</a>
            <a-divider type="vertical" />
            <a-popconfirm
              title="确认删除?"
              @confirm="() => onDelete(record.id)"
            >
              <a href="javascript:;">删除</a>
            </a-popconfirm>
          </span>
        </template>
      </span>
    </a-table>
    <create-employees-form
      ref="createEmployeesModal"
      :visible="visibleCreateEmployees"
      :loading="createEmployeesConfirmLoading"
      :model="createEmployeesmdl"
      @cancel="handleCancelCreateEmployees"
      @ok="handleOkCreateEmployees"
    />
    <edit-employees-form
      ref="editEmployeesModal"
      :visible="visibleEditEmployees"
      :loading="editEmployeesConfirmLoading"
      :model="editEmployeesmdl"
      @cancel="handleCancelEditEmployees"
      @ok="handleOkEditEmployees"
    />
  </a-drawer>
</template>

<script>
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import CreateEmployeesForm from './CreateEmployeesForm'
import EditEmployeesForm from './EditEmployeesForm'
import {
  associatedRegistered,
  employeesList,
  deleteEmployees,
  updateEmployees
} from '@/api/brand'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: 'ID',
    dataIndex: 'id'
  },
  {
    title: '头像',
    dataIndex: 'user_img',
    scopedSlots: { customRender: 'user_img' }
  },
  {
    title: '店员真实姓名',
    dataIndex: 'real_name'
  },
  {
    title: '店员昵称',
    dataIndex: 'nickname'
  },
  {
    title: '联系电话',
    dataIndex: 'phone'
  },
  {
    title: '邮箱',
    dataIndex: 'email'
  },

  {
    title: '创建时间',
    dataIndex: 'created_at'
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
  components: { CreateEmployeesForm, EditEmployeesForm },

  data() {
    const that = this
    console.log(that)
    this.formLayout = {
      labelCol: {
        xs: { span: 24 },
        sm: { span: 4 }
      },
      wrapperCol: {
        xs: { span: 24 },
        sm: { span: 20 }
      }
    }
    return {
      form: this.$form.createForm(this),
      token: storage.get(ACCESS_TOKEN),
      columns,
      employeesList: [],
      visibleCreateEmployees: false,
      createEmployeesConfirmLoading: false,
      createEmployeesmdl: null,
      visibleEditEmployees: false,
      editEmployeesConfirmLoading: false,
      editEmployeesmdl: null,
      pagination,
      fileList: [],
      isEdit: true,
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      isLoading: false,
      uploading: false
    }
  },
  created() {},
  computed: {
    // 自定义请求头
    headers() {
      return {
        token: this.token
      }
    }
  },
  watch: {
    model() {
      if (this.model) {
        this.getEmployeesList()
      }
    }
  },
  methods: {
    getEmployeesList() {
      const params = { id: this.model.id, brand_id: this.model.brand_id }
      employeesList(params)
        .then(res => {
          console.log(res)
          this.employeesList = res.data
        })
        .catch(err => {
          console.log(err)
        })
    },
    handleAdd() {
      this.visibleCreateEmployees = true
    },
    handleEdit(record) {
      this.visibleEditEmployees = true
      this.editEmployeesmdl = { ...record }
    },
    handleOkCreateEmployees(phone) {
      console.log('用户手机', phone)
      console.log(this.model)
      const form = this.$refs.createEmployeesModal.form
      this.createEmployeesConfirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const requestParameters = {
            brand_id: this.model.brand_id,
            brand_shop_id: this.model.id,
            phone: phone
          }
          console.log('requestParameters=====', requestParameters)
          // 新增
          associatedRegistered(requestParameters)
            .then(res => {
              console.log(res)
              this.visibleCreateEmployees = false

              // 重置表单数据
              form.resetFields()
              // 刷新表格
              this.getEmployeesList()
              this.$message.success(res.msg)
            })
            .catch(err => {
              this.$message.error(err.msg)
            })
          this.createEmployeesConfirmLoading = false
        } else {
          this.createEmployeesConfirmLoading = false
        }
      })
    },
    handleCancelCreateEmployees() {
      this.visibleCreateEmployees = false
    },
    handleOkEditEmployees(ModifyImg) {
      const form = this.$refs.editEmployeesModal.form
      this.editEmployeesConfirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          console.log(ModifyImg)
          const requestParameters = {
            id: values.id,
            brand_id: values.brand_id,
            brand_shop_id: values.brand_shop_id,
            real_name: values.real_name,
            nickname: values.nickname,
            phone: values.phone,
            email: values.email,
            user_img:
              values.id > 0
                ? ModifyImg
                  ? values.user_img[0].response.data.file_path.file_path
                  : values.user_img
                : values.user_img[0].response.data.file_path.file_path
          }
          console.log('requestParameters=====', requestParameters)
          updateEmployees(requestParameters)
            .then(res => {
              console.log(res)
              this.visibleEditEmployees = false

              // 重置表单数据
              form.resetFields()
              // 刷新表格
              this.getEmployeesList()
              this.$message.success(res.msg)
            })
            .catch(err => {
              this.$message.error(err.msg)
            })
          this.editEmployeesConfirmLoading = false
        } else {
          this.editEmployeesConfirmLoading = false
        }
      })
    },
    handleCancelEditEmployees() {
      this.visibleEditEmployees = false
    },
    afterVisibleChange(visible) {
      console.log('切换抽屉时动画', visible)
      if (!visible) {
        this.fileList = []
      }
    },
    onSubmit() {
      this.$emit('ok', this.fileList)
    },
    onDelete(id) {
      // 新增
      deleteEmployees({ id: id })
        .then(res => {
          console.log(res)
          // 刷新表格
          this.getEmployeesList()
          this.$message.success(res.msg)
        })
        .catch(err => {
          this.$message.error(err.msg)
        })
    },
    onClose() {
      this.$emit('cancel')
    },

    // 删除文件
    handleRemove(file) {
      // console.log(this.fileList)
      const index = this.fileList.indexOf(file)
      const newFileList = this.fileList.slice()
      newFileList.splice(index, 1)
      this.fileList = newFileList
      // console.log(this.fileList)
    },
    // 选择文件
    beforeUpload(file) {
      const that = this
      this.isLoading = true
      console.log('上传文件状态', file)
      // 大小限制不能超过1M
      const isLt1M = file.size / 1024 / 1024 < 1
      // if (this.fileList < 2) {
      if (!isLt1M) {
        this.$message.error('文件大小不能超过 1MB!')
        this.fileList = [...this.fileList]
        this.isLoading = false
        return false
      } else {
        return new Promise((resolve, reject) => {
          const reader = new FileReader()
          reader.onload = function(e) {
            const data = e.target.result

            const workbook = XLSX.read(data, { type: 'binary' })
            const firstWorksheet = workbook.Sheets[workbook.SheetNames[0]]
            console.log(firstWorksheet)
            // 将excel转换成数组，判断data.length 就可以限制excel的行数
            const JSON = XLSX.utils.sheet_to_json(firstWorksheet, {
              header: 1
            })
            console.log(JSON)
            if (JSON && JSON.length > 10000) {
              that.$message.error('excel不能超过10000行')
              that.fileList = [...that.fileList]
              reject(new Error(false))
            } else {
              that.fileList = [...that.fileList, file]
              console.log(that.fileList)
              return false
            }
          }
          that.isLoading = false
          reader.readAsBinaryString(file)
        })
      }
      // } else {
      //   this.$message.error('只能上传一个文件')
      //   this.fileList = [...this.fileList]
      //   return false
      // }
    },
    // 开始上传
    handleUpload() {
      const { fileList } = this
      const formData = new FormData()

      fileList.forEach(file => {
        formData.append('files[]', file)
      })
      console.log(fileList)
      this.uploading = true
    },
    // 可以把 onChange 的参数转化为控件的值
    normFile(e) {
      console.log(e)
      if (Array.isArray(e)) {
        return e
      }
      return e && e.fileList
    }
  }
}
</script>
<style scoped>
</style>

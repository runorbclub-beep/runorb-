<template>
  <a-drawer
    :title="isEdit ? '编辑' : '新建'"
    width="90%"
    :visible="visible"
    :confirmLoading="loading"
    @close="
      onClose
    "
    :afterVisibleChange="afterVisibleChange"
  >
    <a-spin :spinning="loading">
      <a-form
        :form="form"
        v-bind="formLayout"
      >
        <!-- 检查是否有 id 并且大于0，大于0是修改。其他是新增，新增不显示商品ID -->
        <a-form-item
          v-show="false"
          label="ID"
        >
          <a-input
            v-decorator="['sys_qiye_shake_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="企业名称">
          <a-input v-decorator="['title', { rules: [{ required: true, message: '请输入企业名称!' }] }]" />
        </a-form-item>
        <a-form-item label="手机号码">
          <a-input v-decorator="['phone', { rules: [{ required: true, message: '请输入手机号码!' }] }]" />
        </a-form-item>
        <a-form-item label="联系人">
          <a-input v-decorator="['contacts', { rules: [{ required: true, message: '请输入联系人!' }] }]" />
        </a-form-item>
        <a-form-item label="员工数据">
          <a-upload
            v-decorator="[
              'fileList',
              {
                rules: [{ required: false, message: '请上传员工数据!' }],
              },
            ]"
            :multiple="true"
            :before-upload="beforeUpload"
            :headers="headers"
            accept=".csv,.xls,.xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel

"
            :file-list="fileList"
            :remove="handleRemove"
          >
            <a-button
              style="margin-bottom:15px;"
              icon="upload"
              :loading="isLoading"
            >
              选择文件
            </a-button>
          </a-upload>
          <div class="tips">最大支持10000条记录</div>
          <div class="tips">
            支持扩展名：csv、xls、xlsx，文件大小1Mb以内</div>
        </a-form-item>
        <a-divider />
        <div style="text-align:right">
          <a-button
            :style="{ marginRight: '8px' }"
            @click="onClose"
          >
            取消
          </a-button>
          <a-button
            type="primary"
            @click="onSubmit"
          >
            保存
          </a-button>
        </div>
      </a-form>
    </a-spin>
  </a-drawer>
</template>

<script>
import pick from 'lodash.pick'
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import XLSX from 'xlsx'

// 表单字段
const fields = ['sys_qiye_shake_id', 'title', 'phone', 'contacts']

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
    },
    data: {
      type: Array,
      default: () => null
    }
  },
  components: {},

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

      fileList: [],
      isEdit: true,

      baseUrl: process.env.VUE_APP_API_BASE_URL,

      isLoading: false,
      uploading: false
    }
  },
  created() {
    this.isCreated = true
    console.log('custom modal created')
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))
    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      // 当 model 发生改变时，为表单设置值
      if (this.model !== null) {
        this.isEdit = true
        this.model && this.form.setFieldsValue(pick(this.model, fields))
      } else {
        this.isEdit = false
      }
    })
  },
  computed: {
    // 自定义请求头
    headers() {
      return {
        token: this.token
      }
    }
  },
  methods: {
    afterVisibleChange(visible) {
      console.log('切换抽屉时动画', visible)
      if (!visible) {
        this.fileList = []
      }
    },
    onSubmit() {
      this.$emit('ok', this.fileList)
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

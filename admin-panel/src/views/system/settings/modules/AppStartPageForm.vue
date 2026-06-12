<template>
  <a-modal
    :title="isEdit ? '编辑' : '新建'"
    :width="640"
    :visible="visible"
    :confirmLoading="loading"
    :afterClose="afterClose"
    @ok="
      () => {
        $emit('ok')
      }
    "
    @cancel="
      () => {
        $emit('cancel')
      }
    "
  >
    <a-spin :spinning="loading">
      <a-form
        :form="form"
        v-bind="formLayout"
      >
        <a-form-item
          label="标题"
          has-feedback
        >
          <a-input v-decorator="['advertising_name', { rules: [{ required: true, message: '请输入标题!' }] }]" />
        </a-form-item>

        <a-form-item label="图片375*812">
          <a-upload
            v-decorator="[
              'img_375_812',
              {
                getValueFromEvent: normFile,
                rules: [{ required: true, message: '请上传像素为375*812图片!' }],
              },
            ]"
            name="file"
            :action="baseUrl + '/match/upload'"
            list-type="picture"
            :before-upload="beforeUpload"
            :headers="headers"
            accept="image/*"
            :fileList="fileList"
            @preview="handlePreview"
            @change="handleChange"
          >
            <a-button>
              <a-icon type="upload" /> 点击上传图片
            </a-button>
          </a-upload>
          <span slot="extra">
            <span style="font-size: 12px">图片尺寸限制：375*812 像素</span>
          </span>
          <a-modal
            :visible="previewVisible"
            :footer="null"
            @cancel="handleCancel"
          >
            <img
              alt="example"
              style="width: 100%"
              :src="previewImage"
            />
          </a-modal>
        </a-form-item>
        <a-form-item label="图片414*896">
          <a-upload
            v-decorator="[
              'img_414_896',
              {
                getValueFromEvent: normFile,
                rules: [{ required: true, message: '请上传像素为414*896图片!' }],
              },
            ]"
            name="file"
            :action="baseUrl + '/match/upload'"
            list-type="picture"
            :before-upload="beforeUpload1"
            :headers="headers"
            accept="image/*"
            :fileList="fileList1"
            @preview="handlePreview1"
            @change="handleChange1"
          >
            <a-button>
              <a-icon type="upload" /> 点击上传图片
            </a-button>
          </a-upload>
          <span slot="extra">
            <span style="font-size: 12px">图片尺寸限制：414*896 像素</span>
          </span>
          <a-modal
            :visible="previewVisible1"
            :footer="null"
            @cancel="handleCancel1"
          >
            <img
              alt="example"
              style="width: 100%"
              :src="previewImage1"
            />
          </a-modal>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script>
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'

// 表单字段
const fields = ['advertising_name', 'img_375_812', 'img_414_896']

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
    brandDict: {
      type: Array,
      default: () => []
    },
    categoryDict: {
      type: Array,
      default: () => []
    }
  },
  components: {
    // richText
  },

  data() {
    this.formLayout = {
      labelCol: {
        xs: { span: 24 },
        sm: { span: 7 }
      },
      wrapperCol: {
        xs: { span: 24 },
        sm: { span: 13 }
      }
    }
    return {
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      form: this.$form.createForm(this),
      token: storage.get(ACCESS_TOKEN),
      previewVisible: false,
      previewVisible1: false,
      previewImage: '',
      previewImage1: '',
      fileList: [],
      fileList1: [],
      isEdit: true,
      matchsType: [],
      content: ''
    }
  },
  created() {
    console.log('custom modal created')
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))
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
    afterClose() {
      // alert('modal 关闭')
      // this.form.resetFields()
      this.fileList = []
      this.fileList1 = []
    },
    editorChange: function(html) {
      this.content = html
    },
    // 将上传的文件转成base64格式
    getBase64(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.readAsDataURL(file)
        reader.onload = () => resolve(reader.result)
        reader.onerror = error => reject(error)
      })
    },

    // 图片预览
    async handlePreview(file) {
      if (!file.url && !file.preview) {
        file.preview = await this.getBase64(file.originFileObj)
      }
      this.previewImage = file.url || file.preview
      this.previewVisible = true
    },
    // 图片预览
    async handlePreview1(file) {
      if (!file.url && !file.preview) {
        file.preview = await this.getBase64(file.originFileObj)
      }
      this.previewImage = file.url || file.preview
      this.previewVisible1 = true
    },
    // 关闭预览图片遮罩层
    handleCancel() {
      this.previewVisible = false
    },
    handleCancel1() {
      this.previewVisible1 = false
    },

    beforeUpload(file) {
      console.log('上传图片状态', file)

      const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png'
      if (!isJpgOrPng) {
        this.$message.error('You can only upload JPG file!')
      }
      const isLt2M = file.size / 1024 / 1024 < 2
      if (!isLt2M) {
        this.$message.error('Image must smaller than 2MB!')
      }
      const checkImageWH = this.checkImageWH(file, 375, 812)
      // const checkImageWH = this.checkImageWH(file, 298, 689)
      return isJpgOrPng && isLt2M && checkImageWH
    },
    // 检测尺寸
    // 上传图片尺寸限制
    checkImageWH(file, width, height) {
      const that = this
      // 参数分别是上传的file，想要限制的宽，想要限制的高
      return new Promise(function(resolve, reject) {
        const filereader = new FileReader()
        filereader.onload = e => {
          const src = e.target.result
          const image = new Image()
          image.onload = function() {
            if (this.width === width && this.height === height) {
              resolve()
            } else {
              // 上传图片的宽高与传递过来的限制宽高作比较，超过限制则调用失败回调
              that.$message.error('图片尺寸不符合要求')
              reject()
            }
          }
          image.onerror = reject
          image.src = src
        }
        filereader.readAsDataURL(file)
      })
    },

    beforeUpload1(file) {
      console.log('上传图片状态', file)

      const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png'
      if (!isJpgOrPng) {
        this.$message.error('You can only upload JPG file!')
      }
      const isLt2M = file.size / 1024 / 1024 < 2
      if (!isLt2M) {
        this.$message.error('Image must smaller than 2MB!')
      }
      const checkImageWH = this.checkImageWH(file, 414, 896)
      // const checkImageWH = this.checkImageWH(file, 298, 689)
      return isJpgOrPng && isLt2M && checkImageWH
    },
    handleChange(info) {
      let fileList = [...info.fileList]

      // 1. Limit the number of uploaded files
      //    Only to show two recent uploaded files, and old ones will be replaced by the new
      fileList = fileList.slice(-1)
      console.log(fileList)
      this.fileList = fileList
      console.log(this.fileList1)
    },
    handleChange1(info) {
      let fileList = [...info.fileList]

      // 1. Limit the number of uploaded files
      //    Only to show two recent uploaded files, and old ones will be replaced by the new
      fileList = fileList.slice(-1)
      console.log(fileList)
      this.fileList1 = fileList
    },
    // 可以把 onChange 的参数转化为控件的值
    normFile(e) {
      if (Array.isArray(e)) {
        return e
      }
      return e && e.fileList
    }
  }
}
</script>
<style scoped>
/* tile uploaded pictures */
.upload-list-inline >>> .ant-upload-list-item {
  float: left;
  width: 200px;
  margin-right: 8px;
}
.upload-list-inline >>> .ant-upload-animate-enter {
  animation-name: uploadAnimateInlineIn;
}
.upload-list-inline >>> .ant-upload-animate-leave {
  animation-name: uploadAnimateInlineOut;
}
.edit_container {
  font-family: 'Avenir', Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
.ql-editor {
  height: 400px;
}
</style>

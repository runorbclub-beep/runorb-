<template>
  <a-drawer
    :title="isEdit ? '编辑' : '新建'"
    :width="1200"
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
          label="版本号ID"
        >
          <a-input
            v-decorator="['website_app_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="IOS版本号">
          <a-input
            placeholder="格式：v1.0.0"
            v-decorator="['app_version_ios', { rules: [{ required: true, message: '请输入IOS版本号!' }] }]"
          />
        </a-form-item>
        <a-form-item label="android版本号">
          <a-input
            placeholder="格式：v1.0.0"
            v-decorator="['app_version_android', { rules: [{ required: true, message: '请输入android版本号!' }] }]"
          />
        </a-form-item>
        <a-form-item label="android版本校验号">
          <a-input-number
            placeholder=""
            :precision="0"
            :min="min"
            v-decorator="['app_android_code', { rules: [{ required: true, message: '请输入android版本校验号!' }] }]"
          />
        </a-form-item>
        <a-form-item
          label="IOS版二维码"
          extra=""
        >
          <a-upload
            v-decorator="[
              'app_image_ios',
              {
                getValueFromEvent: normFile,
                rules: [{ required: true, message: '请上传图片!' }],
              },
            ]"
            name="file"
            :action="baseUrl + '/match/upload'"
            list-type="picture"
            :before-upload="beforeUpload"
            :headers="headers"
            accept="image/*"
            :fileList="fileListIOS"
            @preview="handlePreviewIOS"
            @change="handleChangeIOS"
          >
            <a-button>
              <a-icon type="upload" /> 点击上传图片
            </a-button>
          </a-upload>
          <a-modal
            :visible="previewVisibleIOS"
            :footer="null"
            @cancel="handleCancelIOS"
          >
            <img
              alt="example"
              style="width: 100%"
              :src="previewImageIOS"
            />
          </a-modal>
        </a-form-item>

        <a-form-item
          label="Android版二维码"
          extra=""
        >
          <a-upload
            v-decorator="[
              'app_image_android',
              {
                getValueFromEvent: normFile,
                rules: [{ required: true, message: '请上传图片!' }],
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
        <a-form-item label="IOS版本更新说明(中文)">
          <richText
            :content="app_description_ios_cn"
            @editorChange="editorChange($event,'ios','cn')"
            v-decorator="['app_description_ios_cn', { rules: [{ required: true, message: '请输入IOS版本更新说明(中文)!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item label="IOS版本更新说明(英文)">
          <richText
            :content="app_description_ios_en"
            @editorChange="editorChange($event,'ios','en')"
            v-decorator="['app_description_ios_en', { rules: [{ required: true, message: '请输入IOS版本更新说明(英文)!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item label="Android版本更新说明(中文)">
          <richText
            :content="app_description_android_cn"
            @editorChange="editorChange($event,'android','cn')"
            v-decorator="['app_description_android_cn', { rules: [{ required: true, message: '请输入Android版本更新说明(中文)!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item label="Android版本更新说明(英文)">
          <richText
            :content="app_description_android_en"
            @editorChange="editorChange($event,'android','en')"
            v-decorator="['app_description_android_en', { rules: [{ required: true, message: '请输入Android版本更新说明(英文)!' }] }]"
          >
          </richText>
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
import richText from '@/components/RichText/RichText'

// 表单字段
const fields = [
  'website_app_id',
  'app_android_code',
  'app_version_ios',
  'app_version_android',
  'app_image_ios',
  'app_image_android',
  'app_description_ios_cn',
  'app_description_ios_en',
  'app_description_android_cn',
  'app_description_android_en'
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
    richText
  },

  data() {
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
      previewVisible: false,
      previewImage: '',
      previewVisibleIOS: false,
      previewImageIOS: '',
      fileList: [],
      fileListIOS: [],
      isEdit: false,
      app_description_ios_cn: '',
      app_description_ios_en: '',
      app_description_android_cn: '',
      app_description_android_en: '',
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      ModifyImg: false,
      ModifyImgIOS: false,
      min: null
    }
  },
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
    console.log(this.token)
    console.log('custom modal created')
    this.fileList = []
    this.fileListIOS = []
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      console.log(this.model)
      console.log(this.fileList)
      if (this.model !== null) {
        this.isEdit = true
        this.min = this.model.app_android_code || 0
        console.log(this.min)
        const iosImage = this.model.app_image_ios
        this.app_description_ios_cn = this.model.app_description_ios_cn
        this.app_description_ios_en = this.model.app_description_ios_en
        this.app_description_android_cn = this.model.app_description_android_cn
        this.app_description_android_en = this.model.app_description_android_en
        const iosImageName =
          this.model.app_image_ios.split('/')[
            this.model.app_image_ios.split('/').length - 1
          ]
        const androidImage = this.model.app_image_android
        const androidImageName =
          this.model.app_image_android.split('/')[
            this.model.app_image_android.split('/').length - 1
          ]
        this.$set(this.fileListIOS, 0, {
          uid: '-1',
          name: iosImageName,
          status: 'done',
          url: iosImage
        })
        this.$set(this.fileList, 0, {
          uid: '-2',
          name: androidImageName,
          status: 'done',
          url: androidImage
        })
      } else {
        this.isEdit = false
      }
      this.model && this.form.setFieldsValue(pick(this.model, fields))
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
        this.fileListIOS = []
        this.ModifyImg = false
        this.ModifyImgIOS = false
        this.app_description_ios_cn = ''
        this.app_description_ios_en = ''
        this.app_description_android_cn = ''
        this.app_description_android_en = ''
      }
    },
    onSubmit() {
      this.$emit('ok', this.ModifyImg, this.ModifyImgIOS)
    },
    onClose() {
      this.$emit('cancel')
    },

    editorChange(html, version, lang) {
      if (version === 'android') {
        if (lang === 'cn') {
          this.app_description_android_cn = html
          this.form.setFieldsValue({
            app_description_android_cn: html
          })
          console.log(html)
        } else {
          this.app_description_android_en = html
          this.form.setFieldsValue({
            app_description_android_en: html
          })
        }
      } else {
        if (lang === 'cn') {
          this.app_description_ios_cn = html
          this.form.setFieldsValue({
            app_description_ios_cn: html
          })
        } else {
          this.app_description_ios_en = html
          this.form.setFieldsValue({
            app_description_ios_en: html
          })
        }
      }
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
    async handlePreviewIOS(file) {
      if (!file.url && !file.preview) {
        file.preview = await this.getBase64(file.originFileObj)
      }
      this.previewImageIOS = file.url || file.preview
      this.previewVisibleIOS = true
    },
    // 关闭预览图片遮罩层
    handleCancel() {
      this.previewVisible = false
    },
    handleCancelIOS() {
      this.previewVisibleIOS = false
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
      return isJpgOrPng && isLt2M
    },
    handleChange(info) {
      let fileList = [...info.fileList]

      // 1. Limit the number of uploaded files
      //    Only to show two recent uploaded files, and old ones will be replaced by the new
      fileList = fileList.slice(-1)
      console.log(fileList)
      this.ModifyImg = true
      this.fileList = fileList
    },
    handleChangeIOS(info) {
      let fileList = [...info.fileList]

      // 1. Limit the number of uploaded files
      //    Only to show two recent uploaded files, and old ones will be replaced by the new
      fileList = fileList.slice(-1)
      console.log(fileList)
      this.ModifyImgIOS = true
      this.fileListIOS = fileList
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

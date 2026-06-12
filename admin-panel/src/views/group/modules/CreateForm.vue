<template>
  <a-modal
    :title="isEdit ? '编辑' : '新建'"
    :width="640"
    :visible="visible"
    :confirmLoading="loading"
    :afterClose="afterClose"
    @ok="
      () => {
        $emit('ok', ModifyImg)
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
        <!-- 检查是否有 id 并且大于0，大于0是修改。其他是新增，新增不显示商品ID -->
        <a-form-item
          v-show="false"
          label="用户团队ID"
        >
          <a-input
            v-decorator="['user_group_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="团队名称">
          <a-input v-decorator="['group_title', { rules: [{ required: true, message: '请输入团队名称!' }] }]" />
        </a-form-item>
        <a-form-item label="团队编号">
          <a-input v-decorator="['group_num', { rules: [{ required: true, message: '请输入团队编号!' }] }]" />
        </a-form-item>
        <a-form-item
          label="团队性质"
          has-feedback
        >
          <a-select
            v-decorator="['is_official', { rules: [{ required: true, message: '请选择团队性质!' }] }]"
            placeholder="请选择团队性质"
          >
            <a-select-option
              :key="item.value"
              v-for="item in groupProperty"
              :value="item.value"
            >
              {{ item.value }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="团队描述">
          <a-input v-decorator="['official_description', { rules: [{ required: true, message: '请输入团队描述!' }] }]" />
        </a-form-item>
        <a-form-item
          label="团队logo"
          extra=""
        >
          <a-upload
            v-decorator="[
              'group_logo',
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
        <!-- <a-form-item label="文章内容">
          <richText
            :text="content"
            @editorChange="editorChange"
            v-decorator="['news_content', { rules: [{ required: true, message: '请输入文章内容!' }] }]"
          >
          </richText>
        </a-form-item> -->
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script>
import pick from 'lodash.pick'
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
// import richText from '../components/course-rich-text'

// 表单字段
const fields = [
  'user_group_id',
  'group_title',
  'group_num',
  'is_official',
  'official_description',
  'group_logo',
  'group_user_num',
  'status'
]
const groupProperty = [
  {
    value: '官方'
  },
  {
    value: '非官方'
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
      form: this.$form.createForm(this),
      token: storage.get(ACCESS_TOKEN),
      previewVisible: false,
      previewImage: '',
      fileList: [],
      isEdit: false,
      groupProperty,
      content: '',
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      ModifyImg: false
    }
  },
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
    console.log('custom modal created')
    this.fileList = []
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      console.log(this.model)
      console.log(this.fileList)
      if (this.model !== null) {
        this.model.is_official =
          this.model.is_official === '1' ? '官方' : '非官方'
        this.isEdit = true
        const groupLogo = this.model.group_logo
        const groupLogoName =
          this.model.group_logo.split('/')[
            this.model.group_logo.split('/').length - 1
          ]
        this.$set(this.fileList, 0, {
          uid: '-1',
          name: groupLogoName,
          status: 'done',
          url: groupLogo
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
    afterClose() {
      // alert('modal 关闭')
      // this.form.resetFields()
      this.fileList = []
      this.ModifyImg = false
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
    // 关闭预览图片遮罩层
    handleCancel() {
      this.previewVisible = false
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

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
        <!-- 检查是否有 id 并且大于0，大于0是修改。其他是新增，新增不显示商品ID -->
        <a-form-item
          v-show="false"
          label="榜单ID"
        >
          <a-input
            v-decorator="['web_match_ranking_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="榜单标题(中文)">
          <a-input v-decorator="['match_ranking_title', { rules: [{ required: true, message: '请输入团队名称!' }] }]" />
        </a-form-item>
        <a-form-item label="榜单标题(英文)">
          <a-input v-decorator="['ranking_title_en', { rules: [{ required: true, message: '请输入团队名称!' }] }]" />
        </a-form-item>
        <a-form-item
          label="榜单类型"
          has-feedback
        >
          <a-select
            v-decorator="['ranking_type', { rules: [{ required: true, message: '请选择榜单类型!' }] }]"
            placeholder="请选择榜单类型"
          >
            <a-select-option
              :key="item.sys_ranking_type_id"
              v-for="item in leaderboardType"
              :value="item.ranking_type"
            >
              {{ item.ranking_title_zh }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="开始时间">
          <a-date-picker
            v-decorator="[
              'start_time',
              {
                rules: [
                  {
                    required: true,
                    message: '请选择开始时间',
                  },
                ],
              },
            ]"
            placeholder="请选择开始时间"
          />
        </a-form-item>
        <a-form-item label="结束时间">
          <a-date-picker
            v-decorator="[
              'stop_time',
              {
                rules: [
                  {
                    required: true,
                    message: '请选择结束时间',
                  },
                ],
              },
            ]"
            placeholder="请选择结束时间"
          />
        </a-form-item>
        <a-form-item
          label="榜单周期"
          has-feedback
        >
          <a-select
            v-decorator="['ranking_time_type', { rules: [{ required: true, message: '请选择榜单周期!' }] }]"
            placeholder="请选择榜单周期"
          >
            <a-select-option
              :key="item.value"
              v-for="item in periodList"
              :value="item.value"
            >
              {{ item.name }}
            </a-select-option>
          </a-select>
        </a-form-item>

      </a-form>
    </a-spin>
  </a-modal>
</template>

<script>
import pick from 'lodash.pick'
import storage from 'store'
import moment from 'moment'
import { ACCESS_TOKEN } from '@/store/mutation-types'
// import richText from '../components/course-rich-text'

// 表单字段
const fields = [
  'web_match_ranking_id',
  'match_ranking_title',
  'ranking_title_en',
  'ranking_type',
  'start_time',
  'stop_time',
  'ranking_time_type'
]
const periodList = [
  { name: '周榜', value: 'week' },
  { name: '月榜', value: 'month' },
  { name: '季榜', value: 'quarter' },
  { name: '年榜', value: 'year' }
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
    leaderboardType: {
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
      periodList,
      content: '',
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: ''
    }
  },
  created() {
    console.log(this.leaderboardType)

    console.log('custom modal created')
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      console.log(this.model)
      if (this.model !== null) {
        this.model.start_time = moment(this.model.start_date)
        this.model.stop_time = moment(this.model.stop_date)
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
    moment,
    afterClose() {
      // alert('modal 关闭')
      // this.form.resetFields()
      this.fileList = []
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

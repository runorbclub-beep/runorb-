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
            v-decorator="['web_match_ranking_detail_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="用户名">
          <a-input v-decorator="['user_name', { rules: [{ required: true, message: '请输入用户名!' }] }]" />
        </a-form-item>
        <a-form-item label="榜单数据">
          <a-input-number
            style="width:50%"
            v-decorator="['value', { rules: [{ required: true, message: '请输入榜单数据!' }] }]"
            :min="0"
            :formatter="value => `${value}${model.ranking_type === 'marathon'?'秒':model.unit}`"
            :parser="value => value.replace(model.ranking_type === 'marathon'?'秒':model.unit, '')"
          />

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
              :key="item.id"
              v-for="item in rankingType"
              :value="item.type"
            >
              {{ item.title_zh }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="获取成绩时间">
          <a-date-picker
            v-decorator="[
              'join_time',
              {
                rules: [
                  {
                    required: true,
                    message: '请选择获取成绩时间',
                  },
                ],
              },
            ]"
            showTime
            placeholder="请选择获取成绩时间"
          />
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

// 表单字段
const fields = [
  'web_match_ranking_detail_id',
  'user_name',
  'ranking_type',
  'join_time',
  'value'
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
    rankingType: {
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
      content: '',
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: ''
    }
  },
  created() {
    console.log('custom modal created')
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      console.log(this.rankingType)
      console.log(this.model)
      if (this.model !== null) {
        this.isEdit = true
        this.model.join_time = moment(this.model.start_date)
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
  watch: {
    rankingType: function() {
      console.log(this.rankingType)
    }
  },
  methods: {
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

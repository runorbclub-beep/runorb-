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
          label="文章ID"
        >
          <a-input
            v-decorator="['sys_new_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="文章标题(中文)">
          <a-input v-decorator="['news_title', { rules: [{ required: true, message: '请输入文章标题(中文)!' }] }]" />
        </a-form-item>
        <a-form-item label="文章标题(英文)">
          <a-input v-decorator="['news_title_en', { rules: [{ required: true, message: '请输入文章标题(英文)!' }] }]" />
        </a-form-item>
        <a-form-item
          label="文章类型"
          has-feedback
        >
          <a-select
            v-decorator="['news_type', { rules: [{ required: true, message: '请选择文章类型!' }] }]"
            placeholder="请选择文章类型"
          >
            <a-select-option
              :key="item.value"
              v-for="item of newsType"
              :value="item.value"
            >
              {{ item.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <!-- 新闻置顶 -->
        <a-form-item
          label="文章置顶"
          v-bind="formItemLayout"
        >
          <a-radio-group
            button-style="solid"
            v-decorator="[
              'is_top',
              {
                rules: [
                  {
                    required: true,
                    message: '请选择文章置顶',

                  },
                ],
                initialValue: this.is_top,

              },
            ]"
          >
            <a-radio :value="0">
              否
            </a-radio>
            <a-radio :value="1">
              是
            </a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="创建日期">
          <a-date-picker
            v-decorator="[
              'created_date',
              {
                rules: [
                  {
                    required: true,
                    message: '请输入创建日期',
                  },
                ],
              },
            ]"
            showTime
            placeholder="请输入创建日期"
          />
        </a-form-item>
        <!-- <a-form-item label="文章观看数">
          <a-input v-decorator="['view_num', { rules: [{ required: true, message: '请输入文章观看数!' }] }]" />
        </a-form-item> -->
        <a-form-item
          label="文章首图"
          extra=""
        >
          <a-upload
            v-decorator="[
              'news_img',
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
            :file-list="fileList"
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
        <a-form-item label="文章内容(中文)">
          <richText
            :content="content"
            @editorChange="editorChange"
            v-decorator="['news_content', { rules: [{ required: true, message: '请输入文章内容(中文)!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item label="文章内容(英文)">
          <richText
            :content="content_en"
            @editorChange="editorChangeEN"
            v-decorator="['news_content_en', { rules: [{ required: true, message: '请输入文章内容(英文)!' }] }]"
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
  'sys_new_id',
  'news_title',
  'news_title_en',
  'news_type',
  'created_date',
  'news_img',
  'news_content',
  'news_content_en',
  'is_top'
]
const newsType = [
  {
    name: '新闻',
    value: 2
  },
  { name: '资讯', value: 1 }
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
    data: {
      type: Array,
      default: () => null
    }
  },
  components: {
    richText
  },

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
      previewVisible: false,
      previewImage: '',
      fileList: [],
      isEdit: true,
      newsType,
      content: '',
      content_en: '',
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      ModifyImg: false,
      is_top: 0
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
        this.content = this.model.news_content
        this.content_en = this.model.news_content_en
        const newsImg = this.model.news_img
        const newsImgName =
          this.model.news_img.split('/')[
            this.model.news_img.split('/').length - 1
          ]
        this.$set(this.fileList, 0, {
          uid: '-1',
          name: newsImgName,
          status: 'done',
          url: newsImg
        })
        console.log(this.content)

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
        this.content = ''
        this.content_en = ''
        this.fileList = []
        this.ModifyImg = false
      }
    },
    onSubmit() {
      this.$emit('ok', this.ModifyImg)
    },
    onClose() {
      this.$emit('cancel')
    },
    editorChange(html) {
      this.content = html
      this.form.setFieldsValue({
        news_content: html
      })
    },
    editorChangeEN(html) {
      this.content_en = html
      this.form.setFieldsValue({
        news_content_en: html
      })
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

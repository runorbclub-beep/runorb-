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
          label="首页内容ID"
        >
          <a-input
            v-decorator="['website_home_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="中文标题">
          <a-input
            v-decorator="['title_cn', { rules: [{ required: true, message: '请输入中文标题!' }] }]"
            @change="onChangeCN($event)"
          />
        </a-form-item>
        <a-form-item label="英文标题">
          <a-input
            v-decorator="['title_en', { rules: [{ required: true, message: '请输入英文标题!' }] }]"
            @change="onChangeEN($event)"
          />
        </a-form-item>
        <a-form-item label="副标题">
          <a-input v-decorator="['subtitle', { rules: [{ required: true, message: '请输入副标题!' }] }]" />
        </a-form-item>
        <a-form-item label="优先级排序">
          <a-input-number
            v-decorator="['index', { rules: [{ required: true, message: '请输入优先级排序!' }] }]"
            :min="1"
            :formatter="limitNumber"
            :parser="limitNumber"
          />
          <!-- <a-input v-decorator="['index', { rules: [{ required: true, message: '请输入优先级排序!' }] }]" /> -->
        </a-form-item>
        <a-form-item
          label="资源类型"
          has-feedback
        >
          <a-select
            v-decorator="['source_type', { rules: [{ required: true, message: '请选择资源类型!' }] }]"
            placeholder="请选择资源类型"
            @change="onChange"
          >
            <a-select-option
              :key="item.value"
              v-for="item of sourceType"
              :value="item.value"
            >
              {{ item.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item
          label="资源文件地址"
          extra=""
        >
          <a-upload
            v-decorator="[
              'source',
              {
                getValueFromEvent: normFile,
                rules: [{ required: true, message: `请上传${type !==''?(type===0?'图片':'视频'):''}!` }],
              },
            ]"
            name="file"
            :action="baseUrl + '/match/upload'"
            list-type="picture"
            :before-upload="beforeUpload"
            :headers="headers"
            :accept="type === 0 ? 'image/*' : 'video/*'"
            :file-list="fileList"
            @preview="handlePreview"
            @change="handleChange"
          >
            <a-button :disabled="type === ''">
              <a-icon type="upload" /> 点击上传{{ type !==''?(type===0?'图片':'视频'):'' }}
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
            v-decorator="['content', { rules: [{ required: true, message: '请输入文章内容!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item label="文章内容(英文)">
          <richText
            :content="content_en"
            @editorChange="editorChangeEN"
            v-decorator="['content_en', { rules: [{ required: true, message: '请输入文章内容!' }] }]"
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
/* 限制数字输入框只能输入整数 */
const limitNumber = value => {
  if (typeof value === 'string') {
    return !isNaN(Number(value)) ? value.replace(/^(0+)|[^\d]/g, '') : ''
  } else if (typeof value === 'number') {
    return !isNaN(value) ? String(value).replace(/^(0+)|[^\d]/g, '') : ''
  } else {
    return ''
  }
}
// 表单字段
const fields = [
  'website_home_id',
  'title_cn',
  'title_en',
  'subtitle',
  'source_type',
  'index',
  'source',
  'content',
  'content_en'
]
const sourceType = [
  {
    name: '图片',
    value: 0
  },
  { name: '视频', value: 1 }
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
      sourceType,
      content: '',
      content_en: '',
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      ModifyImg: false,
      type: '',
      limitNumber
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
        this.type = this.model.source_type
        this.content = this.model.content
        this.content_en = this.model.content_en
        const newsImg = this.model.source
        const newsImgName =
          this.model.source.split('/')[this.model.source.split('/').length - 1]
        this.$set(this.fileList, 0, {
          uid: '-1',
          name: newsImgName,
          status: 'done',
          url: this.imgURL + newsImg
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
        this.type = ''
      }
    },
    onSubmit() {
      this.$emit('ok')
    },
    onClose() {
      this.$emit('cancel')
    },
    onChangeCN(e) {
      console.log(e)
      const o = e.target
      o.value = o.value.replace(/[^\u4E00-\u9FA5]/g, '') // 只能输入中文
      return o.value
    },
    onChangeEN(e) {
      console.log(e)
      const o = e.target
      o.value = o.value.replace(/[^\w\.\s\/]/gi, '') // 只能输入英文
      return o.value
    },
    // 上传类型
    onChange(value) {
      console.log(value)
      this.type = value
    },
    editorChange(html) {
      this.content = html
      //   this.form.setFieldsValue(['content'], html)
      this.form.setFieldsValue({
        content: html
      })
    },
    editorChangeEN(html) {
      this.content_en = html
      //   this.form.setFieldsValue(['content_en'], html)
      this.form.setFieldsValue({
        content_en: html
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

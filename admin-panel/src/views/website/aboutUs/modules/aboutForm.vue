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
            v-decorator="['website_aboutme_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="标题(中文)">
          <a-textarea
            v-decorator="['title', { rules: [{ required: true, message: '请输入中文标题!' }] }]"
            placeholder="请输入中文标题!"
            auto-size
          />
        </a-form-item>
        <a-form-item label="标题(英文)">
          <a-textarea
            v-decorator="['title_en', { rules: [{ required: true, message: '请输入英文标题!' }] }]"
            placeholder="请输入英文标题!"
            auto-size
          />
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
import richText from '@/components/RichText/RichText'

// 表单字段
const fields = [
  'website_aboutme_id',
  'title',
  'content',
  'title_en',
  'content_en'
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
      isEdit: true,
      content: '',
      content_en: ''
    }
  },
  created() {
    console.log('custom modal created')
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      // 当 model 发生改变时，为表单设置值
      if (this.model !== null) {
        this.isEdit = true
        this.content = this.model.content
        this.content_en = this.model.content_en
        console.log(this.content)

        this.model && this.form.setFieldsValue(pick(this.model, fields))
      } else {
        this.isEdit = false
      }
    })
  },
  computed: {},
  methods: {
    afterVisibleChange(visible) {
      console.log('切换抽屉时动画', visible)
      if (!visible) {
        this.content = ''
        this.content_en = ''
        this.fileList = []
      }
    },
    onSubmit() {
      this.$emit('ok')
    },
    onClose() {
      this.$emit('cancel')
    },
    editorChange(html) {
      this.content = html
      this.form.setFieldsValue({
        content: html
      })
    },
    editorChangeEN(html) {
      this.content_en = html
      this.form.setFieldsValue({
        content_en: html
      })
    }
  }
}
</script>
<style scoped>
</style>

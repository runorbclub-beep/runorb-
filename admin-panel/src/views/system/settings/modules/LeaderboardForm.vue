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
          label="版本号ID"
        >
          <a-input
            v-decorator="['sys_ranking_type_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>

        <a-form-item
          label="榜单序号"
          extra="唯一性，不可重复"
        >

          <a-input-number
            v-decorator="['ranking_index',{ rules: [{ required: true, message: '请输入榜单序号!' }] }]"
            :min="1"
            :formatter="limitNumber"
            :parser="limitNumber"
          />
        </a-form-item>
        <a-form-item label="英文标题">
          <a-input v-decorator="['ranking_title_en', { rules: [{ required: true, message: '请输入英文标题!' }] }]" />
        </a-form-item>
        <a-form-item label="中文标题">
          <a-input v-decorator="['ranking_title_zh', { rules: [{ required: true, message: '请输入中文标题!' }] }]" />
        </a-form-item>
        <a-form-item label="规则说明(中文)">
          <richText
            :content="ranking_rule_zh"
            @editorChange="editorChange($event,'cn')"
            v-decorator="['ranking_rule_zh', { rules: [{ required: true, message: '请输入规则说明(中文)!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item label="规则说明(英文)">
          <richText
            :content="ranking_rule_en"
            @editorChange="editorChange($event,'en')"
            v-decorator="['ranking_rule_en', { rules: [{ required: true, message: '请输入规则说明(英文)!' }] }]"
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
/* 限制数字输入框只能输入整数 */
const limitNumber = value => {
  // console.log('榜单序号==', value)
  // console.log('榜单序号类型==', typeof value)
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
  'sys_ranking_type_id',
  'ranking_index',
  'ranking_title_en',
  'ranking_title_zh',
  'ranking_rule_zh',
  'ranking_rule_en'
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
      isCreated: false,
      limitNumber,
      ranking_rule_zh: '',
      ranking_rule_en: ''
    }
  },
  created() {
    this.isCreated = true
    console.log('custom modal created')
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      console.log(this.model)
      if (this.model !== null) {
        this.isEdit = true
        this.ranking_rule_zh = this.model.ranking_rule_zh
        this.ranking_rule_en = this.model.ranking_rule_en
      } else {
        this.isEdit = false
      }
      this.model && this.form.setFieldsValue(pick(this.model, fields))
    })
  },
  computed: {},
  methods: {
    afterVisibleChange(visible) {
      console.log('切换抽屉时动画', visible)
      if (!visible) {
        this.ranking_rule_zh = ''
        this.ranking_rule_en = ''
      }
    },
    onSubmit() {
      this.$emit('ok')
    },
    onClose() {
      this.$emit('cancel')
    },
    editorChange(html, lang) {
      if (lang === 'cn') {
        this.ranking_rule_zh = html
        this.form.setFieldsValue({
          ranking_rule_zh: html
        })
      } else {
        this.ranking_rule_en = html
        this.form.setFieldsValue({
          ranking_rule_en: html
        })
      }
    }
  }
}
</script>
<style scoped>
</style>

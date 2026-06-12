<template>
  <a-modal
    :title="isEdit ? '编辑' : '新建'"
    :width="640"
    :visible="visible"
    :confirmLoading="loading"
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
          label="分店ID"
        >
          <a-input
            v-decorator="['id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="分店名称">
          <a-input v-decorator="['shop_name', { rules: [{ required: true, message: '请输入分店名称!' }] }]" />
        </a-form-item>
        <a-form-item label="分店地址">
          <a-input v-decorator="['shop_address', { rules: [{ required: true, message: '请输入分店地址!' }] }]" />
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script>
import pick from 'lodash.pick'
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'

// 表单字段
const fields = [
  'id',
  'brand_id',
  'created_at',
  'shop_integral',
  'shop_name',
  'shop_address'
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
    }
  },
  components: {},

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
      isEdit: true
    }
  },
  created() {
    console.log('custom modal created')

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      // 防止表单未注册
      fields.forEach(v => this.form.getFieldDecorator(v))
      console.log(this.model)
      if (this.model !== null) {
        this.isEdit = true
      } else {
        this.isEdit = false
      }
      this.model && this.form.setFieldsValue(pick(this.model, fields))
    })
  },
  computed: {},

  methods: {
    // 关闭预览图片遮罩层
    handleCancel() {
      this.previewVisible = false
    }
  }
}
</script>

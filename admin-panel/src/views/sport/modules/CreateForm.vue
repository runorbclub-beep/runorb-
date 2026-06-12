<template>
  <a-modal
    :title="isEdit ? '编辑' : '新建'"
    :width="640"
    :visible="visible"
    :confirmLoading="loading"
    :afterClose="afterClose"
    @ok="
      () => {
        $emit('ok', isEdit)
      }
    "
    @cancel="
      () => {
        $emit('cancel')
      }
    "
  >
    <a-spin :spinning="loading">
      <a-form :form="form" v-bind="formLayout">
        <!-- 检查是否有 id 并且大于0，大于0是修改。其他是新增，新增不显示商品ID -->
        <a-form-item label="ID" v-show="false">
          <a-input v-decorator="['id', { initialValue: 0 }]" />
        </a-form-item>
        <a-form-item label="秒数">
          <a-input-number v-decorator="['time', { rules: [{ required: true, message: '请输入秒数!' }] }]" :min="0" />
        </a-form-item>
        <a-form-item label="最高转速">
          <a-input-number
            v-decorator="['max_speed', { rules: [{ required: true, message: '请输入最高转速!' }] }]"
            :min="0"
          />
        </a-form-item>
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
const fields = ['id', 'time', 'max_speed']

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

      isEdit: false
    }
  },
  created() {
    console.log(this.token)

    console.log('custom modal created')

    // 防止表单未注册
    fields.forEach((v) => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
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
    afterClose() {
      // alert('modal 关闭')
      // this.form.resetFields()
      this.fileList = []
      this.ModifyImg = false
    }
  }
}
</script>
<style scoped></style>

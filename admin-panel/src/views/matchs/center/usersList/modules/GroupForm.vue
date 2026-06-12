<template>
  <a-modal
    :title="isEdit ? '编辑' : '新建'"
    :width="800"
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
        <!-- <a-form-item
          v-show="false"
          label="用户团队ID"
        >
          <a-input
            v-decorator="['user_group_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item> -->

        <a-form-item label="组队名称">
          <a-textarea
            v-decorator="['team_tag', { rules: [{ required: true, message: '请输入组队名称!' }] }]"
            auto-size
          />
        </a-form-item>
        <a-form-item label="备注">
          <span style="color:#f5222d">批量添加请切换英文输入符号 ‘ , ’ 隔开，如“名称1,名称2,名称3”</span>
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
const fields = ['team_tag']

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
    teamTag: {
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
      imgURL: '',
      ModifyImg: false
    }
  },
  created() {
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      console.log(this.model)
      console.log(this.fileList)
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
    afterClose() {}
  }
}
</script>

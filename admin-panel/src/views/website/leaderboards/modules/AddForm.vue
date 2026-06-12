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
        :form="addForm"
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
        <a-form-item
          v-show="false"
          label="用户ID"
        >
          <a-input
            v-decorator="['userId', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item
          label="榜单类型"
          v-show="false"
        >
          <a-input
            disabled
            v-decorator="['ranking_type', { rules: [{ required: true, message: '请输入榜单类型!' }] }]"
          />
        </a-form-item>
        <a-form-item label="榜单标题">
          <a-textarea
            disabled
            v-decorator="['match_ranking_title', { rules: [{ required: true, message: '请输入榜单标题!' }] }]"
            auto-size
          />
        </a-form-item>
        <a-form-item label="榜单类型">
          <a-input
            disabled
            v-decorator="['rankingTitle', { rules: [{ required: true, message: '请输入榜单类型!' }] }]"
          />
        </a-form-item>
        <a-form-item label="榜单数据">
          <a-input-number
            style="width:50%"
            v-decorator="['ranking_value_format', { rules: [{ required: true, message: '请输入榜单数据!' }] }]"
            :min="0"
            :formatter="value => `${value}${model?model.ranking_type === 'marathon'?'s':model.unit:''}`"
            :parser="value => value.replace(model.ranking_type === 'marathon'?'s':model.unit, '')"
          />
        </a-form-item>

        <a-form-item label="获取成绩时间">
          <a-date-picker
            v-decorator="[
              'stop_time',
              {
                rules: [
                  {
                    required: true,
                    message: '请选择获取成绩时间',
                  },
                ],
                initialValue:null
              },
            ]"
            placeholder="请选择获取成绩时间"
            showTime
          />
        </a-form-item>

      </a-form>
    </a-spin>
  </a-modal>
</template>

<script>
import pick from 'lodash.pick'

import moment from 'moment'

// 表单字段
const fields = [
  'web_match_ranking_id',
  'userId',
  'match_ranking_title',
  'ranking_type',
  'rankingTitle',
  'ranking_value_format',
  'stop_time'
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
      addForm: this.$form.createForm(this, { name: 'add' }),
      isEdit: false,
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      dateFormat: 'YYYY/MM/DD'
    }
  },
  created() {
    console.log('custom modal created')
    // 防止表单未注册

    fields.forEach(v => this.addForm.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      console.log(this.model)
      if (this.model !== null) {
        console.log('编辑', this.model.ranking_title.title_zh)
        this.model.rankingTitle = this.model.ranking_title.title_zh
        this.model.stop_time = moment(this.model.stop_time)
        console.log(this.model.stop_time)
        this.model && this.addForm.setFieldsValue(pick(this.model, fields))
      } else {
        console.log('新建')
        this.isEdit = false
      }
    })
  },
  computed: {},

  methods: {
    moment,
    afterClose() {
      // alert('modal 关闭')
    }
  }
}
</script>
<style scoped>
</style>

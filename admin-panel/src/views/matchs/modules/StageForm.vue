<template>
  <!-- 新增赛段 -->
  <a-modal
    :width="640"
    :title="isEdit ? '编辑' : '新建'"
    :visible="visible"
    :loading="loading"
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
          label="赛段ID"
          v-show="false"
        >
          <a-input
            v-decorator="['matchs_stage_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item
          label="赛事项目ID"
          v-show="false"
        >
          <a-input
            v-decorator="['sys_match_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item
          label="赛事ID"
          v-show="false"
        >
          <a-input
            v-decorator="['sys_sys_match_id', { initialValue: 0 }]"
            disabled
          />
        </a-form-item>
        <a-form-item label="赛段标题(中文)">
          <a-input
            v-decorator="[
              'match_stage_title',
              { rules: [{ required: true, message: '请输入赛段标题' }], initialValue: '' },
            ]">
          </a-input>
        </a-form-item>
        <a-form-item label="赛段标题(英文)">
          <a-input
            v-decorator="[
              'match_stage_title_en',
              { rules: [{ required: true, message: '请输入赛段标题' }], initialValue: '' },
            ]">
          </a-input>
        </a-form-item>
        <a-form-item label="晋级规则">
          <a-radio-group
            name="radioGroup"
            v-decorator="[
              'matchs_stage_rule',
              { rules: [{ required: true, message: '请选择晋级规则' }], initialValue: 0 },
            ]"
          >
            <a-radio
              :value="item.value"
              v-for="item of stageRule"
              :key="item.value"
            > {{ item.name }} </a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="摇跑指数成绩">
          <a-radio-group
            name="radioGroup"
            v-decorator="[
              'is_exponent',
              { rules: [{ required: true, message: '请选择是否展示摇跑指数成绩' }], initialValue: 1 },
            ]"
          >
            <a-radio
              :value="item.value"
              v-for="item of isShowYP"
              :key="item.value"
            > {{ item.name }} </a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="晋级规则值">
          <a-input-number
            style="width: 40%"
            class="matchs_stage_rule_value"
            v-if="form.getFieldValue('matchs_stage_rule') == undefined || form.getFieldValue('matchs_stage_rule') === 0"
            :min="0"
            :formatter="(value) => `${value}人`"
            :parser="(value) => value.replace('人', '')"
            v-decorator="[
              'matchs_stage_rule_value',
              { rules: [{ required: true, message: '请输入晋级规则值' }], initialValue: 0 },
            ]"
          />
          <a-input-number
            v-else
            :min="0"
            :formatter="(value) => `${value}%`"
            :parser="(value) => value.replace('%', '')"
            v-decorator="['matchs_stage_rule_value', { rules: [{ required: true, message: '请输入规则值' }] }]"
          />
        </a-form-item>

        <!-- <a-form-item label="积分规则:" >
            <a-radio-group
              name="radioGroup1"
              v-decorator="['matchs_integral_type', { rules: [{ required: true }], initialValue: 0 }]"
            >
              <a-radio :value="item.value" v-for="item of integralRule" :key="item.value"> {{ item.name }} </a-radio>
            </a-radio-group>
          </a-form-item> -->
        <!-- <a-form-item label="积分规则值:" >
            <a-input-number
              v-if="
                stageForm.getFieldValue('matchs_integral_type') == undefined ||
                stageForm.getFieldValue('matchs_integral_type') === 0
              "
              :min="0"
              :formatter="(value) => `${value}人/队`"
              :parser="(value) => value.replace('人/队', '')"
              v-decorator="[
                'matchs_integral_value',
                { rules: [{ required: true, message: '请输入积分规则值' }], initialValue: 0 },
              ]"
            />
            <a-input-number
              v-else
              :min="0"
              :formatter="(value) => `${value}%`"
              :parser="(value) => value.replace('%', '')"
              v-decorator="[
                'matchs_integral_value',
                { rules: [{ required: true, message: '请输入规则值' }], initialValue: 0 },
              ]"
            />
          </a-form-item> -->
        <!-- <a-form-item label="最多积分数:" >
            <a-input-number
              :min="0"
              v-decorator="[
                'matchs_max_integral',
                { rules: [{ required: true, message: '请输入最多积分数' }], initialValue: 0 },
              ]"
            />
          </a-form-item> -->
        <!-- <a-form-item label="递减积分数:" >
            <a-input-number
              :min="0"
              v-decorator="[
                'matchs_sub_integral',
                { rules: [{ required: true, message: '请输入递减积分数' }], initialValue: 0 },
              ]"
            />
          </a-form-item> -->
        <a-form-item label="前端界面:">
          <a-select v-decorator="['fontEnd_ui', { rules: [{ required: true, message: '请选择前端UI界面' }], initialValue: '' }]">
            <a-select-option
              v-for="(item, index) in UIType"
              :key="index"
              :value="item.value"
            >
              {{ item.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="赛段距离:">
          <a-input-number
            style="width: 40%"
            class="match_stage_distance"
            :min="0"
            :formatter="(value) => `${value}m`"
            :parser="(value) => value.replace('m', '')"
            v-decorator="[
              'match_stage_distance',
              { rules: [{ required: true, message: '请选择赛段距离' }], initialValue: 0 },
            ]"
          />
        </a-form-item>
        <a-form-item :label="$t('matchs.add.form.matchs_start_date')">
          <a-date-picker
            show-time
            v-decorator="['start_time', { rules: [{ required: true, message: '请选择开始时间' }], initialValue: '' }]"
          />
        </a-form-item>

        <a-form-item :label="$t('matchs.add.form.matchs_stop_date')">
          <a-date-picker
            show-time
            v-decorator="['stop_time', { rules: [{ required: true, message: '请选择结束时间' }], initialValue: '' }]"
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
  'sys_match_id',
  'sys_sys_match_id',
  'matchs_stage_id',
  'match_stage_title',
  'match_stage_title_en',
  'matchs_stage_rule',
  'matchs_stage_rule_value',
  'official_description',
  'fontEnd_ui',
  'match_stage_distance',
  'start_time',
  'stop_time',
  'is_exponent'
]
const isShowYP = [
  {
    name: '展示',
    value: 1
  },
  {
    name: '不展示',
    value: 0
  }
]
const stageRule = [
  {
    name: '指定人数',
    value: 0
  },
  {
    name: '按百分比',
    value: 1
  }
]
const UIType = [
  {
    name: '标准赛事',
    value: 1
  },
  {
    name: '摇跑',
    value: 2
  }
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
    brandDict: {
      type: Array,
      default: () => []
    },
    categoryDict: {
      type: Array,
      default: () => []
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

      isEdit: false,
      stageRule,
      isShowYP,
      UIType,
      content: '',
      ModifyImg: false
    }
  },
  created() {
    console.log(this.token)
    console.log('custom modal created')
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      // console.log(this.model)
      if (this.model !== null) {
        this.isEdit = true
        this.model.start_time = moment(this.model.start_time)
        this.model.stop_time = moment(this.model.stop_time)
      } else {
        this.isEdit = false
      }
      // console.log(this.model)
      this.model && this.form.setFieldsValue(pick(this.model, fields))
    })
  },
  computed: {},

  methods: {
    moment,
    afterClose() {
      // alert('modal 关闭')
      this.form.resetFields()
    },
    editorChange: function(html) {
      this.content = html
    },
    // 将上传的文件转成base64格式

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

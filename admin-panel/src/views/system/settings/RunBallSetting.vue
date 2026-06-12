<template>
  <div class="system-settings-info-view">
    <a-row :gutter="16">
      <a-col :md="24" :lg="24">
        <a-form class="system_settings_form" :form="form" v-bind="formLayout">
          <a-form-item v-show="false" label="配置ID">
            <a-input v-decorator="['sys_setting_id', { initialValue: 0 }]" disabled />
          </a-form-item>
          <a-form-item label="摇跑指数英文解释">
            <a-row>
              <a-col :md="20" :lg="20">
                <a-input
                  v-decorator="[
                    'exponent_title_description_en',
                    { rules: [{ required: true, message: '请输入摇跑指数英文解释!' }] },
                  ]"
                  :disabled="fieldId === 'exponent_title_description_en' ? disabled : true"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('exponent_title_description_en', form.getFieldValue('exponent_title_description_en'))"
                >
                  <a-icon v-if="fieldId === 'exponent_title_description_en' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="cancel">
                  <a-icon
                    class="icon_style"
                    v-if="fieldId === 'exponent_title_description_en' && !disabled"
                    type="undo"
                  />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>

          <a-form-item label="摇跑指数中文解释">
            <a-row>
              <a-col :md="20" :lg="20">
                <a-input
                  v-decorator="[
                    'exponent_title_description_zh',
                    { rules: [{ required: true, message: '请输入摇跑指数中文解释!' }] },
                  ]"
                  :disabled="fieldId === 'exponent_title_description_zh' ? disabled : true"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('exponent_title_description_zh', form.getFieldValue('exponent_title_description_zh'))"
                >
                  <a-icon v-if="fieldId === 'exponent_title_description_zh' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="cancel">
                  <a-icon
                    class="icon_style"
                    v-if="fieldId === 'exponent_title_description_zh' && !disabled"
                    type="undo"
                  />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>
          <a-form-item label="达成分子运动时间英文提示">
            <a-row>
              <a-col :md="20" :lg="20">
                <a-input
                  v-decorator="[
                    'exponent_molecular_tips_en',
                    { rules: [{ required: true, message: '请输入达成分子运动时间英文提示!' }] },
                  ]"
                  :disabled="fieldId === 'exponent_molecular_tips_en' ? disabled : true"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('exponent_molecular_tips_en', form.getFieldValue('exponent_molecular_tips_en'))"
                >
                  <a-icon v-if="fieldId === 'exponent_molecular_tips_en' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" v-if="fieldId === 'exponent_molecular_tips_en' && !disabled" type="undo" />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>

          <a-form-item label="达成分子运动时间中文提示">
            <a-row>
              <a-col :md="20" :lg="20">
                <a-input
                  v-decorator="[
                    'exponent_molecular_tips_zh',
                    { rules: [{ required: true, message: '请输入达成分子运动时间中文提示!' }] },
                  ]"
                  :disabled="fieldId === 'exponent_molecular_tips_zh' ? disabled : true"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('exponent_molecular_tips_zh', form.getFieldValue('exponent_molecular_tips_zh'))"
                >
                  <a-icon v-if="fieldId === 'exponent_molecular_tips_zh' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" v-if="fieldId === 'exponent_molecular_tips_zh' && !disabled" type="undo" />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>
          <a-form-item label="达成分母运动距离英文提示">
            <a-row>
              <a-col :md="20" :lg="20">
                <a-input
                  v-decorator="[
                    'exponent_denominator_tips_en',
                    { rules: [{ required: true, message: '请输入达成分母运动距离英文提示!' }] },
                  ]"
                  :disabled="fieldId === 'exponent_denominator_tips_en' ? disabled : true"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('exponent_denominator_tips_en', form.getFieldValue('exponent_denominator_tips_en'))"
                >
                  <a-icon v-if="fieldId === 'exponent_denominator_tips_en' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="cancel">
                  <a-icon
                    class="icon_style"
                    v-if="fieldId === 'exponent_denominator_tips_en' && !disabled"
                    type="undo"
                  />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>

          <a-form-item label="达成分母运动距离中文提示">
            <a-row>
              <a-col :md="20" :lg="20">
                <a-input
                  v-decorator="[
                    'exponent_denominator_tips_zh',
                    { rules: [{ required: true, message: '请输入达成分母运动距离中文提示!' }] },
                  ]"
                  :disabled="fieldId === 'exponent_denominator_tips_zh' ? disabled : true"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('exponent_denominator_tips_zh', form.getFieldValue('exponent_denominator_tips_zh'))"
                >
                  <a-icon v-if="fieldId === 'exponent_denominator_tips_zh' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="cancel">
                  <a-icon
                    class="icon_style"
                    v-if="fieldId === 'exponent_denominator_tips_zh' && !disabled"
                    type="undo"
                  />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>
          <a-form-item label="摇跑指数分子">
            <a-row>
              <a-col :md="7" :lg="7">
                <a-input-number
                  style="width: 100%"
                  v-decorator="['exponent_molecular', { rules: [{ required: true, message: '请输入摇跑指数分子!' }] }]"
                  :min="0"
                  :disabled="fieldId === 'exponent_molecular' ? disabled : true"
                  :formatter="(value) => `${value}s`"
                  :parser="(value) => value.replace('s', '')"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="toggle('exponent_molecular', form.getFieldValue('exponent_molecular'))">
                  <a-icon v-if="fieldId === 'exponent_molecular' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2" v-if="fieldId === 'exponent_molecular' && !disabled">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" type="undo" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="5">
                <span class="tips">指定时间，单位：秒</span>
              </a-col>
            </a-row>
          </a-form-item>
          <a-form-item label="摇跑指数分母">
            <a-row>
              <a-col :md="7" :lg="7">
                <a-input-number
                  style="width: 100%"
                  v-decorator="[
                    'exponent_denominator',
                    { rules: [{ required: true, message: '请输入摇跑指数分母!' }] },
                  ]"
                  :min="0"
                  :disabled="fieldId === 'exponent_denominator' ? disabled : true"
                  :formatter="(value) => `${value}km`"
                  :parser="(value) => value.replace('km', '')"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('exponent_denominator', form.getFieldValue('exponent_denominator'))"
                >
                  <a-icon v-if="fieldId === 'exponent_denominator' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2" v-if="fieldId === 'exponent_denominator' && !disabled">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" type="undo" />
                </a-button>
              </a-col>
              <a-col :md="10" :lg="10">
                <span class="tips">指定距离，单位：千米</span>
              </a-col>
            </a-row>
          </a-form-item>
        </a-form>
      </a-col>
    </a-row>
  </div>
</template>

<script>
import pick from 'lodash.pick'
import { getSystemList, updateSystem } from '@/api/system'
// 表单字段
const fields = [
  'exponent_title_description_en',
  'exponent_title_description_zh',
  'exponent_molecular_tips_en',
  'exponent_molecular_tips_zh',
  'exponent_denominator_tips_en',
  'exponent_denominator_tips_zh',
  'exponent_molecular',
  'exponent_denominator',
  'sys_setting_id'
]
export default {
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
      fileList: [],
      model: {},
      fieldId: '',
      disabled: true
    }
  },
  created() {
    getSystemList(null)
      .then((res) => {
        // 重置表单数据
        console.log(res)
        if (res.code === 1) {
          this.model = res.data
        } else {
          this.$message.info(res.msg)
        }
      })
      .catch((err) => {
        console.log(err)
      })
    // 防止表单未注册
    fields.forEach((v) => this.form.getFieldDecorator(v))
    this.$watch('model', () => {
      this.model && this.form.setFieldsValue(pick(this.model, fields))
    })
  },
  methods: {
    cancel() {
      this.fieldId = ''
    },
    toggle(id, value) {
      if (this.fieldId !== id) {
        this.disabled = true
        console.log('切换==', id)
      } else {
        if (!this.disabled) {
          console.log('改变值', value)
          console.log('保存提交==', id)

          const params = {
            sys_setting_id: this.form.getFieldValue('sys_setting_id'),
            type: id,
            value: value.toString()
          }
          console.log(params)
          updateSystem({ ...params })
            .then((res) => {
              console.log(res)
              if (res.code === 1) {
                this.$message.info(res.msg)
              } else {
                this.$message.info(res.msg)
              }
            })
            .catch((err) => {
              console.log(err)
            })
        } else {
          console.log('编辑==', id)
        }
      }
      this.fieldId = id
      this.disabled = !this.disabled
    },
    onChange(value) {
      console.log('changed', value)
    }
  }
}
</script>

<style scoped>
.system_settings_form .tips {
  display: inline-block;
  /* margin-left: 20px; */
  color: #999;
  font-size: 12px;
}
button i {
  font-size: 18px;
}
</style>

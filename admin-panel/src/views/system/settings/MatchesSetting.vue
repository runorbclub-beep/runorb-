<template>
  <div class="system-settings-info-view">
    <a-row :gutter="16">
      <a-col :md="24" :lg="24">
        <a-form class="system_settings_form" :form="form" v-bind="formLayout">
          <a-form-item v-show="false" label="配置ID">
            <a-input v-decorator="['sys_setting_id', { initialValue: 0 }]" disabled />
          </a-form-item>
          <a-form-item label="完成运动距离时英文提示">
            <a-row>
              <a-col :md="20" :lg="20">
                <a-input
                  v-decorator="[
                    'match_stop_tips_en',
                    { rules: [{ required: true, message: '请输入完成运动距离时英文提示!' }] },
                  ]"
                  :disabled="fieldId === 'match_stop_tips_en' ? disabled : true"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="toggle('match_stop_tips_en', form.getFieldValue('match_stop_tips_en'))">
                  <a-icon v-if="fieldId === 'match_stop_tips_en' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" v-if="fieldId === 'match_stop_tips_en' && !disabled" type="undo" />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>

          <a-form-item label="完成运动距离时中文提示">
            <a-row>
              <a-col :md="20" :lg="20">
                <a-input
                  v-decorator="[
                    'match_stop_tips_zh',
                    { rules: [{ required: true, message: '请输入完成运动距离时中文提示!' }] },
                  ]"
                  :disabled="fieldId === 'match_stop_tips_zh' ? disabled : true"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="toggle('match_stop_tips_zh', form.getFieldValue('match_stop_tips_zh'))">
                  <a-icon v-if="fieldId === 'match_stop_tips_zh' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" v-if="fieldId === 'match_stop_tips_zh' && !disabled" type="undo" />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>
          <a-form-item label="每个参赛队伍成员人数">
            <a-row>
              <a-col :md="7" :lg="7">
                <a-input-number
                  style="width: 100%"
                  v-decorator="[
                    'match_group_user_num',
                    { rules: [{ required: true, message: '请输入每个参赛队伍成员人数!' }] },
                  ]"
                  :min="1"
                  :disabled="fieldId === 'match_group_user_num' ? disabled : true"
                  :formatter="(value) => `${value}人`"
                  :parser="(value) => value.replace('人', '')"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('match_group_user_num', form.getFieldValue('match_group_user_num'))"
                >
                  <a-icon v-if="fieldId === 'match_group_user_num' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2" v-if="fieldId === 'match_group_user_num' && !disabled">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" type="undo" />
                </a-button>
              </a-col>
            </a-row>
          </a-form-item>
          <a-form-item label="每个参赛主体最多同时报名项目数">
            <a-row>
              <a-col :md="7" :lg="7">
                <a-input-number
                  style="width: 100%"
                  v-decorator="[
                    'match_max_sign_count',
                    { rules: [{ required: true, message: '请输入每个参赛主体最多同时报名项目数!' }] },
                  ]"
                  :min="1"
                  :disabled="fieldId === 'match_max_sign_count' ? disabled : true"
                  :formatter="(value) => `${value}个`"
                  :parser="(value) => value.replace('个', '')"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button
                  type="link"
                  @click="toggle('match_max_sign_count', form.getFieldValue('match_max_sign_count'))"
                >
                  <a-icon v-if="fieldId === 'match_max_sign_count' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2" v-if="fieldId === 'match_max_sign_count' && !disabled">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" type="undo" />
                </a-button>
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
const fields = ['match_stop_tips_en', 'match_stop_tips_zh', 'match_group_user_num', 'match_max_sign_count']
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
    // handleSubmit() {
    //   this.form.validateFields((errors, values) => {
    //     if (!errors) {
    //       console.log('values', values)
    //       const requestParameters = {
    //         sys_setting_id: values.sys_setting_id,
    //         pk_group_user: values.pk_group_user,
    //         pk_group_time: values.pk_group_time,
    //         pk_person_time: values.pk_person_time,
    //       }

    //       console.log('编辑')
    //       updateSystem({ ...requestParameters })
    //         .then((res) => {
    //           if (res.code === 1) {
    //             console.log(res)
    //             // 重置表单数据
    //             // this.form.resetFields()
    //             // 刷新表格
    //             this.$message.info('修改成功')
    //           } else {
    //             this.$message.info(res.msg)
    //           }
    //         })
    //         .catch((err) => {
    //           console.log(err)
    //         })
    //     } else {
    //       this.confirmLoading = false
    //     }
    //   })
    // },
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

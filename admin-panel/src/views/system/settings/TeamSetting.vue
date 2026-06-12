<template>
  <div class="system-settings-info-view">
    <a-row :gutter="16">
      <a-col :md="24" :lg="24">
        <a-form class="system_settings_form" :form="form" v-bind="formLayout">
          <a-form-item v-show="false" label="配置ID">
            <a-input v-decorator="['sys_setting_id', { initialValue: 0 }]" disabled />
          </a-form-item>
          <a-form-item label="双人PK时间">
            <a-row>
              <a-col :md="7" :lg="7">
                <a-input-number
                  style="width: 100%"
                  v-decorator="['pk_person_time', { rules: [{ required: true, message: '请输入双人PK时间!' }] }]"
                  :min="1"
                  :disabled="fieldId === 'pk_person_time' ? disabled : true"
                  :formatter="(value) => `${value}s`"
                  :parser="(value) => value.replace('s', '')"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="toggle('pk_person_time', form.getFieldValue('pk_person_time'))">
                  <a-icon v-if="fieldId === 'pk_person_time' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2" v-if="fieldId === 'pk_person_time' && !disabled">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" type="undo" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="5">
                <span class="tips">单位：秒</span>
              </a-col>
            </a-row>
          </a-form-item>
          <a-form-item label="组队PK时间">
            <a-row>
              <a-col :md="7" :lg="7">
                <a-input-number
                  style="width: 100%"
                  v-decorator="['pk_group_time', { rules: [{ required: true, message: '请输入组队PK时间!' }] }]"
                  :min="0"
                  :disabled="fieldId === 'pk_group_time' ? disabled : true"
                  :formatter="(value) => `${value}s`"
                  :parser="(value) => value.replace('s', '')"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="toggle('pk_group_time', form.getFieldValue('pk_group_time'))">
                  <a-icon v-if="fieldId === 'pk_group_time' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2" v-if="fieldId === 'pk_group_time' && !disabled">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" type="undo" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="5">
                <span class="tips">单位：秒</span>
              </a-col>
            </a-row>
          </a-form-item>
          <a-form-item label="组队PK中">
            <a-row>
              <a-col :md="7" :lg="7">
                <a-input-number
                  style="width: 100%"
                  v-decorator="['pk_group_user', { rules: [{ required: true, message: '请输入组队PK时间!' }] }]"
                  :min="0"
                  :max="10"
                  :disabled="fieldId === 'pk_group_user' ? disabled : true"
                  :formatter="(value) => `${value}人`"
                  :parser="(value) => value.replace('人', '')"
                  @change="onChange"
                />
              </a-col>
              <a-col :md="2" :lg="2">
                <a-button type="link" @click="toggle('pk_group_user', form.getFieldValue('pk_group_user'))">
                  <a-icon v-if="fieldId === 'pk_group_user' && !disabled" type="save" />
                  <a-icon v-else type="edit" />
                </a-button>
              </a-col>
              <a-col :md="2" :lg="2" v-if="fieldId === 'pk_group_user' && !disabled">
                <a-button type="link" @click="cancel">
                  <a-icon class="icon_style" type="undo" />
                </a-button>
              </a-col>
              <a-col :md="10" :lg="10">
                <span class="tips">单位：人，每队最多10人</span>
              </a-col>
            </a-row>
          </a-form-item>
          <!-- <a-form-item>
            <a-button type="primary" @click="handleSubmit">更新配置</a-button>
          </a-form-item> -->
        </a-form>
      </a-col>
    </a-row>
  </div>
</template>

<script>
import pick from 'lodash.pick'
import { getSystemList, updateSystem } from '@/api/system'
// 表单字段
const fields = ['pk_person_time', 'pk_group_time', 'pk_group_user', 'sys_setting_id']
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

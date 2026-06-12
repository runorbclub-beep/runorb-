<template>
  <page-header-wrapper>
    <a-card :bordered="false">

      <a-form
        :form="form"
        v-bind="formLayout"
        @submit="handleSubmit"
      >
        <a-form-item label="标题(中文)">
          <a-textarea
            v-decorator="['title_cn', { rules: [{ required: true, message: '请输入中文标题!' }] }]"
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
        <a-form-item label="会员费">
          <a-input-number
            style="width:18%"
            v-decorator="['members_amount', { rules: [{ required: true, message: '请输入会员费!' }] }]"
            :min="0"
            :formatter="value => `${value}元`"
            :parser="value => value.replace('元', '')"
          />
        </a-form-item>
        <a-form-item label="描述(中文)">
          <richText
            :content="content"
            @editorChange="editorChange"
            v-decorator="['members_description_cn', { rules: [{ required: true, message: '请输入中文描述!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item label="描述(英文)">
          <richText
            :content="contentEN"
            @editorChange="editorChange1"
            v-decorator="['members_description_en', { rules: [{ required: true, message: '请输入英文描述!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item v-bind="formItemLayoutWithOutLabel">
          <a-button
            type="primary"
            html-type="submit"
          >
            保存
          </a-button>
        </a-form-item>
      </a-form>

    </a-card>
  </page-header-wrapper>
</template>

<script>
import pick from 'lodash.pick'
import richText from '@/components/RichText/RichText'
import { getMembersList, updateMembersList } from '@/api/system'
// 表单字段
const fields = [
  'title_cn',
  'title_en',
  'members_amount',
  'members_description_en',
  'members_description_cn'
]
export default {
  name: 'MemberRecruit',
  components: { richText },
  data() {
    this.formLayout = {
      labelCol: {
        xs: { span: 24 },
        sm: { span: 3 }
      },
      wrapperCol: {
        xs: { span: 24 },
        sm: { span: 12 }
      }
    }
    this.formItemLayoutWithOutLabel = {
      wrapperCol: {
        xs: { span: 24, offset: 0 },
        sm: { span: 20, offset: 3 }
      }
    }
    return {
      form: this.$form.createForm(this),
      model: {},
      content: '',
      contentEN: ''
    }
  },
  filters: {},
  created() {
    this.getMembersList()
  },
  computed: {},

  methods: {
    editorChange(html) {
      this.content = html
      console.log(this.content)
      this.form.setFieldsValue({
        members_description_cn: html
      })
    },
    editorChange1(html) {
      this.contentEN = html
      console.log(this.contentEN)
      this.form.setFieldsValue({
        members_description_en: html
      })
    },
    getMembersList() {
      getMembersList()
        .then(res => {
          // 重置表单数据
          console.log(res)
          if (res.code === 1) {
            this.content = res.data.members_description_cn
            this.contentEN = res.data.members_description_en
            this.model = res.data
            // 防止表单未注册
            fields.forEach(v => this.form.getFieldDecorator(v))
            this.model && this.form.setFieldsValue(pick(this.model, fields))
          } else {
            this.$message.info(res.msg)
          }
        })
        .catch(err => {
          console.log(err)
        })
    },
    callback(key) {
      this.langType = key
      this.getMembersList()
      console.log(key)
    },
    //   提交
    handleSubmit(e) {
      e.preventDefault() // 这样就好了
      this.form
        .validateFields((errors, values) => {
          if (!errors) {
            console.log('values', values)
            const params = {
              title_cn: values.title_cn,
              title_en: values.title_en,
              members_amount: values.members_amount,
              members_description_cn: this.content,
              members_description_en: this.contentEN
            }
            console.log('params=====', params)

            updateMembersList(params)
              .then(res => {
                console.log(res)
                this.$message.info('修改成功')
              })
              .catch(err => {
                console.log(err)
              })
          } else {
          }
        })
        .catch(err => {
          console.log(err)
        })
    }
  }
}
</script>

<template>
  <div class="main">
    <a-card title="赛事类型" :bordered="false">
      <a-form :form="form" @submit="MatchsTypeAddSubmit">
        <a-form-item :label="$t('matchs.add.form.match_type_title')" v-bind="formItemLayout" style="padding-top: 20px">
          <a-input
            v-decorator="[
              'matchs_type_title',
              {
                rules: [{ required: true, message: $t('matchs.type.title.placeholder') }],
                initialValue: matchs_type_title,
              },
            ]"
            :placeholder="$t('matchs.type.title.placeholder')"
            style="width: 60%; margin-right: 8px"
          >
          </a-input>
        </a-form-item>

        <a-form-item v-bind="formItemLayoutWithOutLabel">
          <a-row type="flex" justify="center" :gutter="16">
            <a-col :md="3">
              <a-button @click="cancel"> 取消 </a-button>
            </a-col>
            <a-col :md="3">
              <a-button type="primary" html-type="submit"> {{ $t('medal.add.form.submit') }}</a-button>
            </a-col>
          </a-row></a-form-item
        >
      </a-form>
    </a-card>
  </div>
</template>

<script>
import { MatchsTypeAdd } from '@/api/matchs'
export default {
  data() {
    return {
      BASE_URL: '',
      button_loading: false,
      matchs_type_id: 0,
      matchs_type_title: '',
      formItemLayout: {
        labelCol: {
          xs: { span: 24 },
          sm: { span: 3 }
        },
        wrapperCol: {
          xs: { span: 24 },
          sm: { span: 18 }
        }
      },
      formItemLayoutWithOutLabel: {
        wrapperCol: {
          xs: { span: 24, offset: 0 },
          sm: { span: 18, offset: 3 }
        }
      }
    }
  },
  created() {
    this.BASE_URL = process.env.VUE_APP_API_BASE_URL
  },
  beforeCreate() {
    this.form = this.$form.createForm(this, { name: 'dynamic_form_item' })
    this.form.getFieldDecorator('keys', { initialValue: [], preserve: true })
  },
  mounted() {
    if (this.$route.query.matchs_type_id !== undefined) {
      this.matchs_type_id = this.$route.query.matchs_type_id
    }
    if (this.$route.query.matchs_type_title !== undefined) {
      this.matchs_type_title = this.$route.query.matchs_type_title
    }
  },
  methods: {
    // 返回上一页
    cancel() {
      this.$router.go(-1)
    },
    MatchsTypeAddSubmit(e) {
      e.preventDefault()
      this.form.validateFields((err, values) => {
        if (!err) {
          this.button_loading = true
          if (this.matchs_type_id !== 0 && this.matchs_type_id !== undefined) {
            values['matchs_type_id'] = this.matchs_type_id
          }
          MatchsTypeAdd(values).then((res) => this.MatchsTypeAddSuccess(res))
        }
      })
    },
    MatchsTypeAddSuccess(res) {
      this.button_loading = false
      if (res.code === 1) {
        this.$message.success(res.msg)
        this.$router.push({
          path: '/matchs/type/list'
        })
      } else {
        this.$message.error(res.msg)
      }
    }
  }
}
</script>

<style lang="less" scoped></style>

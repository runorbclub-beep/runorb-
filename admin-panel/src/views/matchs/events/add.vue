<template>
  <div class="main">
    <a-card title="赛事项目" :bordered="false">
      <a-form :form="form" @submit="MatchsEventTypeAddSubmit" style="background-color: #fff">
        <a-form-item label="比赛项目名称(中文)" v-bind="formItemLayout" style="padding-top: 20px">
          <a-input
            v-decorator="[
              'match_events_type_title',
              { rules: [{ required: true, message: $t('matchs.events.title.placeholder') }] },
            ]"
            placeholder="请输入比赛项目名称(中文)"
            style="width: 60%; margin-right: 8px"
          >
          </a-input>
        </a-form-item>
        <a-form-item label="比赛项目名称(英文)" v-bind="formItemLayout" style="padding-top: 20px">
          <a-input
            v-decorator="[
              'match_events_type_title_en',
              { rules: [{ required: true, message: '请输入比赛项目名称(英文)' }] },
            ]"
            placeholder="请输入比赛项目名称(英文)"
            style="width: 60%; margin-right: 8px"
          >
          </a-input>
        </a-form-item>
        <!-- <a-form-item :label="$t('matchs.add.form.match_events_distance_value')" v-bind="formItemLayout">
        <a-input-number
          :min="0"
          :max="100000"
          v-decorator="[
            'match_events_distance_value',
            { rules: [{ required: true, message: $t('matchs.events.match_events_distance_value.placeholder') }] },
          ]"
          :placeholder="$t('matchs.events.match_events_distance_value.placeholder')"
          style="width: 30%; margin-right: 8px"
        />
      </a-form-item> -->
        <a-form-item label="优先级排序" v-bind="formItemLayout">
          <a-input-number
            v-decorator="['index', { rules: [{ required: true, message: '请输入优先级排序!' }] }]"
            :min="1"
            :formatter="limitNumber"
            :parser="limitNumber"
          />
          <!-- <a-input v-decorator="['index', { rules: [{ required: true, message: '请输入优先级排序!' }] }]" /> -->
        </a-form-item>

        <a-form-item v-bind="formItemLayoutWithOutLabel">
          <a-row type="flex" justify="center" :gutter="16">
            <a-col :md="2">
              <a-button @click="cancel"> 取消 </a-button>
            </a-col>
            <a-col :md="2">
              <a-button type="primary" html-type="submit"> {{ $t('medal.add.form.submit') }}</a-button>
            </a-col>
          </a-row></a-form-item
        >
      </a-form>
    </a-card>
  </div>
</template>

<script>
import { MatchsEventTypeAdd } from '@/api/matchs'
/* 限制数字输入框只能输入整数 */
const limitNumber = (value) => {
  if (typeof value === 'string') {
    return !isNaN(Number(value)) ? value.replace(/^(0+)|[^\d]/g, '') : ''
  } else if (typeof value === 'number') {
    return !isNaN(value) ? String(value).replace(/^(0+)|[^\d]/g, '') : ''
  } else {
    return ''
  }
}
export default {
  data() {
    return {
      BASE_URL: '',
      button_loading: false,
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
      },
      limitNumber
    }
  },
  created() {
    this.BASE_URL = process.env.VUE_APP_API_BASE_URL
  },
  beforeCreate() {
    this.form = this.$form.createForm(this, { name: 'dynamic_form_item' })
    this.form.getFieldDecorator('keys', { initialValue: [], preserve: true })
  },
  methods: {
    // 返回上一页
    cancel() {
      this.$router.go(-1)
    },
    MatchsEventTypeAddSubmit(e) {
      e.preventDefault()
      this.form.validateFields((err, values) => {
        if (!err) {
          this.button_loading = true
          MatchsEventTypeAdd(values).then((res) => this.MatchsEventTypeAddSuccess(res))
        }
      })
    },
    MatchsEventTypeAddSuccess(res) {
      this.button_loading = false
      if (res.code === 1) {
        this.$message.success(res.msg)
        this.$router.push({
          path: '/matchs/events/list'
        })
      } else {
        this.$message.error(res.msg)
      }
    }
  }
}
</script>

<style lang="less" scoped></style>

<template>
  <div class="main">
    <a-form :form="form" @submit="MatchRuleAddSubmit" style="background-color:#fff;padding:20px;">
      <a-form-item :label="$t('matchs.add.form.match_integral_title')" v-bind="formItemLayout">
        <a-input
          v-decorator="[
            'integral_rules_title',
            { rules: [{ required: true, message: $t('matchs.integral_rules_title.placeholder') }], initialValue: obj_match_integral_rule.integral_rules_title },
          ]"
          :placeholder="$t('matchs.integral_rules_title.placeholder')"
          style="width: 60%; margin-right: 8px"
        >
        </a-input>
      </a-form-item>
      <a-form-item :label="$t('matchs.add.form.max_integral')" v-bind="formItemLayout">
        <a-input-number
          :min="0"
          :max="1000"
          v-decorator="['max_integral', { rules: [{ required: true, message: $t('matchs.integral_rules_title.max_integral.placeholder') }], initialValue: obj_match_integral_rule.max_integral },]"
          :placeholder="$t('matchs.integral_rules_title.max_integral.placeholder')"
          style="width: 30%; margin-right: 8px" />
      </a-form-item>
      <a-form-item :label="$t('matchs.add.form.sub_integral')" v-bind="formItemLayout">
        <a-input-number
          :min="0"
          :max="1000"
          v-decorator="['sub_integral', { rules: [{ required: true, message: $t('matchs.integral_rules_title.sub_integral.placeholder') }], initialValue: obj_match_integral_rule.sub_integral },]"
          :placeholder="$t('matchs.integral_rules_title.sub_integral.placeholder')"
          style="width: 30%; margin-right: 8px" />
      </a-form-item>

      <a-form-item :label="$t('matchs.add.form.integral_name')" v-bind="formItemLayout">
        <a-radio-group name="radioGroup" :value="martch_promotion_type_value" @change="radio_change">
          <a-radio v-for="(item,index) in match_promotion_type" :value="item.value" :key="index">
            {{ item.name_cn }}
          </a-radio>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="$t('matchs.add.form.get_integral_value')" v-bind="formItemLayout">
        <div v-if="martch_promotion_type_value == 0">
          <a-input-number :min="0" :max="1000" v-decorator="['get_integral_value', { rules: [{ required: true, message: $t('matchs.rules.get_integral_value.placeholder') }], initialValue: obj_match_integral_rule.get_integral_value },]" :placeholder="$t('matchs.rules.match_promotion_value.placeholder')" style="width: 30%; margin-right: 8px" />
        </div>
        <div v-if="martch_promotion_type_value == 1">
          <a-input-number :min="1" :max="100" v-decorator="['get_integral_value', { rules: [{ required: true, message: $t('matchs.rules.get_integral_value.placeholder') }], initialValue: obj_match_integral_rule.get_integral_value },]" :placeholder="$t('matchs.rules.match_promotion_value.placeholder')" style="width: 30%; margin-right: 8px" />%
        </div>
      </a-form-item>
      <!--  -->
      <a-form-item v-bind="formItemLayoutWithOutLabel" style="padding-bottom:20px;">
        <a-button type="primary" html-type="submit" :loading="button_loading">
          {{ $t('medal.add.form.submit') }}
        </a-button>
      </a-form-item>
    </a-form>
  </div>
</template>

<script>
import { postMatchIntegralRulesAdd, postMatchIntegralRulesInfo } from '@/api/matchs'
export default {
  data() {
    return {
      obj_match_integral_rule: {
        'integral_rules_title': '',
        'max_integral': 0,
        'sub_integral': 0,
        'get_integral_value': 0
      },
      matchs_integral_rule_id: 0,
      martch_promotion_type_value: 0,
      match_promotion_type: [
        {
          'value': 0,
          'name_en': 'Stage Person Count',
          'name_cn': '指定人数'
        },
        {
          'value': 1,
          'name_en': 'Stage Percentage Count',
          'name_cn': '按百分比'
        }
      ],
      BASE_URL: '',
      button_loading: false,
      formItemLayout: {
        labelCol: {
          xs: { span: 24 },
          sm: { span: 4 }
        },
        wrapperCol: {
          xs: { span: 24 },
          sm: { span: 20 }
        }
      },
      formItemLayoutWithOutLabel: {
        wrapperCol: {
          xs: { span: 24, offset: 0 },
          sm: { span: 20, offset: 4 }
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
    if (this.$route.query.matchs_integral_rule_id !== undefined) {
      this.matchs_integral_rule_id = this.$route.query.matchs_integral_rule_id
      this.getMatchsIntegralRulesInfo()
    }
  },
  methods: {
    getMatchsIntegralRulesInfo() {
      var params = {
        matchs_integral_rule_id: this.matchs_integral_rule_id
      }
      postMatchIntegralRulesInfo(params).then((res) => this.postMatchIntegralRulesInfoSuccess(res))
    },
    MatchRuleAddSubmit(e) {
      e.preventDefault()
      this.form.validateFields((err, values) => {
        if (!err) {
          this.button_loading = true
          values['get_integral_type'] = this.martch_promotion_type_value
          if (this.matchs_integral_rule_id !== 0 && this.matchs_integral_rule_id !== undefined) {
            values['matchs_integral_rule_id'] = this.matchs_integral_rule_id
          }
          console.log(values)
          postMatchIntegralRulesAdd(values)
            .then((res) => this.postMatchIntegralRulesAddSuccess(res))
        }
      })
    },
    postMatchIntegralRulesAddSuccess(res) {
      this.button_loading = false
      if (res.code === 1) {
        this.$message.success(res.msg)
        this.$router.push({
            path: '/matchs/integral/list'
        })
      } else {
        this.$message.error(res.msg)
      }
    },
    postMatchIntegralRulesInfoSuccess(res) {
      if (res.code === 1) {
        this.obj_match_integral_rule = res.data
        this.martch_promotion_type_value = res.data.get_integral_type
      } else {
        this.$message.error(res.msg)
      }
    },
    radio_change(e) {
      this.martch_promotion_type_value = e.target.value
    }
  }
}
</script>

<style lang="less" scoped>
</style>

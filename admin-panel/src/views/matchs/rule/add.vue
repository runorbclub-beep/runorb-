<template>
  <div class="main">
    <a-form :form="form" @submit="MatchRuleAddSubmit" style="background-color:#fff;">
      <a-form-item :label="$t('matchs.add.form.matchs_type')" v-bind="formItemLayout" style="padding-top:20px;">
        <a-select v-model="matchs_type_id" style="width: 60%; margin-right: 8px">
          <a-select-option v-for="(item,index) in match_type_list" :key="index" :value="item.matchs_type_id">
            {{ item.matchs_type_title }}
          </a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="$t('matchs.add.form.match_rule_name')" v-bind="formItemLayout">
        <a-input
          v-decorator="[
            'match_rules_title',
            { rules: [{ required: true, message: $t('matchs.rules.title.placeholder') }], initialValue: obj_match.match_rules_title },
          ]"
          :placeholder="$t('matchs.rules.title.placeholder')"
          style="width: 60%; margin-right: 8px"
        >
        </a-input>
      </a-form-item>
      <a-form-item :label="$t('matchs.add.form.match_rule_name')" v-bind="formItemLayout">
        <a-radio-group name="radioGroup" :value="martch_promotion_type_value" @change="radio_change">
          <a-radio v-for="(item,index) in match_promotion_type" :value="item.value" :key="index">
            {{ item.name_cn }}
          </a-radio>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="$t('matchs.add.form.match_promotion_value')" v-bind="formItemLayout">
        <div v-if="martch_promotion_type_value == 0">
          <a-input-number :min="0" :max="1000" v-decorator="['match_promotion_value', { rules: [{ required: true, message: $t('matchs.rules.match_promotion_value.placeholder') }], initialValue: obj_match.match_promotion_value },]" :placeholder="$t('matchs.rules.match_promotion_value.placeholder')" style="width: 30%; margin-right: 8px" />
        </div>
        <div v-if="martch_promotion_type_value == 1">
          <a-input-number :min="1" :max="100" v-decorator="['match_promotion_value', { rules: [{ required: true, message: $t('matchs.rules.match_promotion_value.placeholder') }], initialValue: obj_match.match_promotion_value },]" :placeholder="$t('matchs.rules.match_promotion_value.placeholder')" style="width: 30%; margin-right: 8px" />%
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
import { postMatchStageRulesAdd, postMatchStageRulesInfo, MatchsTypeList } from '@/api/matchs'
export default {
  data() {
    return {
      obj_match: {
        'match_rules_title': '',
        'match_promotion_value': ''
      },
      matchs_stage_rule_id: 0,
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
      },
      match_type_list: [],
      matchs_type_id: ''
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
    this.getMatchsTypeList()
    if (this.$route.query.matchs_stage_rule_id !== undefined) {
      this.matchs_stage_rule_id = this.$route.query.matchs_stage_rule_id
      this.getMatchsInfo()
    }
    if (this.$route.query.matchs_type_id !== undefined) {
      this.matchs_type_id = this.$route.query.matchs_type_id
    }
  },
  methods: {
    getMatchsTypeList() {
      var params = {
        type: 'all'
      }
      MatchsTypeList(params)
      .then((res) => {
        if (res.code === 1) {
          this.match_type_list = res.data.list
          if (this.matchs_type_id === '') {
            this.matchs_type_id = res.data.list[0]['matchs_type_id']
          }
        }
      })
    },
    getMatchsInfo() {
      var params = {
        matchs_stage_rule_id: this.matchs_stage_rule_id
      }
      postMatchStageRulesInfo(params).then((res) => this.postMatchStageRulesInfoSuccess(res))
    },
    MatchRuleAddSubmit(e) {
      e.preventDefault()
      this.form.validateFields((err, values) => {
        if (!err) {
          this.button_loading = true
          values['martch_promotion_type_value'] = this.martch_promotion_type_value
          values['matchs_type_id'] = this.matchs_type_id
          if (this.matchs_stage_rule_id !== 0 && this.matchs_stage_rule_id !== undefined) {
            values['matchs_stage_rule_id'] = this.matchs_stage_rule_id
          }
          postMatchStageRulesAdd(values)
            .then((res) => this.MatchsStageRulesAddSuccess(res))
        }
      })
    },
    MatchsStageRulesAddSuccess(res) {
      this.button_loading = false
      if (res.code === 1) {
        this.$message.success(res.msg)
        this.$router.push({
            path: '/matchs/rule/list'
        })
      } else {
        this.$message.error(res.msg)
      }
    },
    postMatchStageRulesInfoSuccess(res) {
      if (res.code === 1) {
        this.obj_match = {
          'match_rules_title': res.data.match_rules_title,
          'match_promotion_value': res.data.match_promotion_value
        }
        this.martch_promotion_type_value = res.data.match_promotion_type
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

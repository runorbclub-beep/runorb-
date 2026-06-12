<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="main">
        <div class="table-page-search-wrapper">
          <a-form layout="inline">
            <a-row :gutter="48">
              <a-col
                :md="6"
                :sm="24"
              >
                <a-form-item
                  label="赛事"
                  v-bind="formItemLayout"
                >
                  <a-select
                    v-model="sys_match_id"
                    class="center_wrapper_select"
                    @change="sys_match_change"
                  >
                    <a-select-option
                      v-for="(item, index) in match_title_list"
                      :key="index"
                      :value="item.sys_match_id"
                    >
                      {{ item.match_title }}
                    </a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
            </a-row>
          </a-form>
        </div>
        <div class="table-operator">
          <a-button
            type="primary"
            icon="plus"
            @click="add_event"
          >新建</a-button>
        </div>

        <a-descriptions
          :title="obj_match_info.match_title"
          style="background-color: #fff; padding: 20px; clear: both"
        >
          <a-descriptions-item :label="$t('matchs.add.form.matchs_type')">
            {{ obj_match_info.matchs_type_title }}
          </a-descriptions-item>
          <a-descriptions-item :label="$t('matchs.info.time')">
            {{ obj_match_info.start_time }} {{ $t('match.to') }} {{ obj_match_info.stop_time }}
          </a-descriptions-item>
          <a-descriptions-item :label="$t('matchs.add.form.matchs_email')">
            {{ obj_match_info.match_email }}
          </a-descriptions-item>
          <!-- <a-descriptions-item :label="$t('matchs.add.form.user_type_sign_pass')">
        {{ obj_match_info.match_user_type_description }}
      </a-descriptions-item>
      <a-descriptions-item :label="$t('matchs.add.form.user_sex_sign_pass')">
        {{ obj_match_info.match_user_sex_description }}
      </a-descriptions-item> -->
          <a-descriptions-item :label="$t('matchs.add.form.matchs_phone')">
            +{{ obj_match_info.match_phone_prefix }} {{ obj_match_info.match_phone }}
          </a-descriptions-item>
        </a-descriptions>
        <div v-if="showEvent">
          <div
            style="padding: 20px; font-size: 20px; font-weight: 700"
            v-if="event_list.length > 0"
          >比赛项目：</div>
          <a-card
            :title="item['match_events_type_title']"
            v-for="item in event_list"
            :key="item.sys_match_id"
            style="margin-top: 10px"
          >
            <a-card-meta
              title="奖品:"
              :description="item.match_champion_prize_description"
              style="margin-bottom:20px"
            > </a-card-meta>
            <a-button
              slot="extra"
              type="primary"
              icon="plus"
              @click="handleAdd(item)"
              style="margin-right: 10px"
            >新建</a-button>
            <a-popconfirm
              slot="extra"
              :title="$t('match.event.delete')"
              :ok-text="$t('global.yes')"
              :cancel-text="$t('global.no')"
              @confirm="deleteMatch(item.sys_match_id)"
            >
              <a-button
                type="danger"
                icon="delete"
              >{{ $t('match_info_event_stage_delete') }}</a-button>
            </a-popconfirm>
            <!-- 赛段 -->
            <a-row
              type="flex"
              justify="start"
              :gutter="[32, { xs: 8, sm: 16, md: 24, lg: 32 }]"
            >
              <a-col
                :xs="24"
                :sm="24"
                :md="12"
                :lg="12"
                :xl="6"
                v-for="(node, node_index) in item.stage"
                :key="node_index"
              >
                <a-card
                  :title="node['match_stage_title']"
                  :extra="node.matchs_stage_status === 1?'未开始':node.matchs_stage_status === 2?'进行中':'已结束'"
                >
                  <template
                    slot="actions"
                    class="ant-card-actions"
                  >
                    <a-button
                      type="link"
                      icon="solution"
                      @click="handleCheckList(node.matchs_stage_id,node.matchs_stage_id,node.sys_match_id,node.sys_sys_match_id,node.matchs_stage_status)"
                    />
                    <a-button
                      type="link"
                      icon="edit"
                      :disabled="node.matchs_stage_status !== 1"
                      @click="handleEdit(node.matchs_stage_id)"
                    />

                    <a-popconfirm
                      :title="$t('match.stage.delete')"
                      :ok-text="$t('global.yes')"
                      :cancel-text="$t('global.no')"
                      :disabled="node.matchs_stage_status !== 1"
                      @confirm="deleteMatchStage(node)"
                    >
                      <a-button
                        type="link"
                        icon="delete"
                        :disabled="node.matchs_stage_status !== 1"
                      />
                    </a-popconfirm>

                  </template>
                  <a-form
                    :ref="node.matchs_stage_id"
                    class="stage_form"
                    :form="$form.createForm(this, { name: node.matchs_stage_id })"
                    v-bind="formLayout"
                  >
                    <a-form-item v-show="false">
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'matchs_stage_id',
                              {
                                initialValue: node.matchs_stage_id,
                              },
                            ]"></span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item v-show="false">
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'sys_sys_match_id',
                              {
                                initialValue: node.sys_sys_match_id,
                              },
                            ]"></span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item v-show="false">
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'sys_match_id',
                              {
                                initialValue: node.sys_match_id,
                              },
                            ]"></span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item v-show="false">
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'match_stage_title',
                              {
                                initialValue: node.match_stage_title,
                              },
                            ]"></span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item v-show="false">
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'match_stage_title_en',
                              {
                                initialValue: node.match_stage_title_en,
                              },
                            ]"></span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item v-show="false">
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'matchs_stage_rule',
                              {
                                initialValue: node.match_promotion_type,
                              },
                            ]"></span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item
                      :label="$t('match.start')"
                      labelAlign="left"
                    >
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'start_time',
                              {
                                initialValue: node.start_time,
                              },
                            ]">{{ node.start_time }}</span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item
                      :label="$t('match.stop')"
                      labelAlign="left"
                    >
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'stop_time',
                              {
                                initialValue: node.stop_time,
                              },
                            ]">{{ node.stop_time }}</span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item
                      :label="$t('matchs.match_rules_title')"
                      labelAlign="left"
                    >
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'matchs_stage_rule_value',
                              {
                                initialValue: node.match_promotion_value,
                              },
                            ]">{{ node.match_promotion_value }}</span>
                          <span v-if="node.match_promotion_type === 0">{{ $t('matchs.stage_type0') }}</span>
                          <span v-else>{{ $t('matchs.stage_type1') }}</span>
                          {{ $t('matchs.stage_pass') }}
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item
                      label="赛段距离"
                      labelAlign="left"
                    >
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'match_stage_distance',
                              {
                                initialValue: node.match_stage_distance,
                              },
                            ]">{{ node.match_stage_distance }}米</span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <a-form-item
                      label="前端界面"
                      labelAlign="left"
                    >
                      <a-row>
                        <a-col :md="20">
                          <span
                            v-decorator="[
                              'fontEnd_ui',
                              {
                                initialValue: node.view_type,
                              },
                            ]">{{ node.view_type === 1 ? '标准赛事' : '摇跑' }}</span>
                          <span
                            class="switch"
                            @click="showImage(node.view_type)"
                          >
                            <a-icon
                              type="eye"
                              v-show="isShow"
                            />
                            <a-icon
                              type="eye-invisible"
                              v-show="!isShow"
                            />
                          </span>
                        </a-col>
                      </a-row>
                    </a-form-item>
                    <!-- <a-form-item label="报名列表">
                      <a-row>
                        <a-col :md="20">
                          <router-link
                            :to="{
                              name: 'MatchsUsersList',
                              params: {
                                matchId: node.sys_sys_match_id,
                                stageId: node.matchs_stage_id,
                                isGroup: isGroup,
                              },
                            }"
                          >查看</router-link
                          >
                        </a-col>
                      </a-row>
                    </a-form-item> -->
                    <a-modal
                      @cancel="hasCloseImage"
                      :footer="null"
                      :visible="!isShow"
                      :title="node.view_type === 1 ? '标准赛事' : '摇跑'"
                    >
                      <img
                        :src="require(`@/assets/images/${viewType === 1 ? 'biaozhun.png' : 'yaopao.png'}`)"
                        alt=""
                        width="100%"
                      />
                    </a-modal>
                  </a-form>
                </a-card>
              </a-col>
            </a-row>
            </a-card-meta>
          </a-card>
        </div>
        <!-- obj_match_info -->
        <!-- 新增比赛项目 -->
        <a-modal
          :width="640"
          :title="$t('matchs.event_add')"
          :visible="add_match_event_show"
          :confirm-loading="StageConfirmLoading"
          @ok="matchs_event_add"
          @cancel="matchs_event_add_cancel"
        >
          <a-form
            :form="projectForm"
            style="background-color: #fff"
          >
            <a-form-item
              v-show="false"
              label="赛事ID"
            >
              <a-input
                v-decorator="['sys_match_id', { initialValue: '' }]"
                disabled
              />
            </a-form-item>
            <a-form-item
              label="赛事类型"
              v-bind="formItemLayout"
            >
              <span
                v-decorator="[
                  'matchs_type_id',
                  { rules: [{ required: true }], initialValue: obj_match_info.matchs_type_id },
                ]">{{ obj_match_info.matchs_type_title }}</span>
            </a-form-item>
            <a-form-item
              :label="$t('matchs.event_type')"
              v-bind="formItemLayout"
            >
              <a-select
                v-decorator="[
                  'match_event_type_id',
                  { rules: [{ required: true }], initialValue: match_event_type_value },
                ]"
                style="margin-right: 8px"
              >
                <a-select-option
                  v-for="item in match_event_list"
                  :key="item.matchs_event_type_id"
                  :value="item.matchs_event_type_id"
                >
                  {{ item.match_events_type_title }}
                </a-select-option>
              </a-select>
            </a-form-item>

            <a-form-item
              label="奖品(中文)"
              v-bind="formItemLayout"
            >
              <a-input v-decorator="['match_champion_prize_description', { initialValue: '' }]"> </a-input>
            </a-form-item>
            <a-form-item
              label="奖品(英文)"
              v-bind="formItemLayout"
            >
              <a-input v-decorator="['match_champion_prize_description_en', { initialValue: '' }]"> </a-input>
            </a-form-item>
          </a-form>
        </a-modal>

        <stage-form
          ref="stageModal"
          :visible="visibleStage"
          :loading="StageConfirmLoading"
          :model="stageMdl"
          @cancel="handleCancelStage"
          @ok="handleOkStage"
        />
      </div>
    </a-card>
  </page-header-wrapper>
</template>

<script>
import {
  SysMatchInfo,
  MatchtitleList,
  postMatchIntegralRulesList,
  MatchsEventTypeList,
  postMatchStageRulesList,
  postMatchEventAdd,
  postMatchEventDelete,
  postMatchEventStageAdd,
  postMatchEventStageDelete
} from '@/api/matchs'
import StageForm from './modules/StageForm'
import moment from 'moment'

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
const integralRule = [
  {
    name: '指定人数',
    value: 0
  },
  {
    name: '按百分比',
    value: 1
  }
]
export default {
  components: {
    StageForm
  },
  data() {
    return {
      form: this.$form.createForm(this, { name: 'form' }),
      projectForm: this.$form.createForm(this, { name: 'projectForm' }),
      stageForm: this.$form.createForm(this, { name: 'stageForm' }),
      sys_match_id: '',
      obj_match_info: {},
      obj_matchs: {},
      start_time: false,
      stop_time: false,
      match_title_list: [],
      event_list: [],
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
      formLayout: {
        labelCol: {
          xs: { span: 24 },
          sm: { span: 6 }
        },
        wrapperCol: {
          xs: { span: 24 },
          sm: { span: 18 }
        }
      },
      button_loading: false,
      add_match_event_show: false,
      StageConfirmLoading: false,
      match_champion_prize_description: '',
      match_champion_prize_description_en: '',
      match_integral_rule_list: [],
      match_integral_rule_value: '',
      match_event_list: [],
      match_event_type_value: '',
      match_stage_list: [],
      match_stage_value: '',
      visibleStage: false,
      match_stage_title: '',
      obj_matchs_event: {},
      showEvent: false,
      UIType,
      stageRule,
      integralRule,
      isShow: true,
      viewType: null,
      isGroup: null,
      stageMdl: null
      // fieldId: '',
      // disabled: true,
      // inputNumber: null,
    }
  },

  mounted() {
    if (this.$route.query.sys_match_id !== undefined) {
      this.sys_match_id = this.$route.query.sys_match_id
      this.getSysMatchInfo()
    }
    this.getMatchTitleList()

    this.pageInit()
  },
  watch: {
    event_list: function() {
      console.log(this.event_list)
    }
  },
  methods: {
    // 查看报名列表
    handleCheckList(
      matchId,
      stageId,
      sysMatchId,
      sysSysMatchId,
      matchsStageStatus
    ) {
      this.$router.push({
        name: 'MatchsUsersList',
        params: {
          matchId: matchId,
          stageId: stageId,
          isGroup: this.isGroup,
          sysMatchId: sysMatchId,
          sysSysMatchId: sysSysMatchId,
          matchsStageStatus: matchsStageStatus
        }
      })
    },
    handleEdit(stageId) {
      // console.log('stageId===', stageId)
      console.log(this.$refs[stageId][0].form.getFieldsValue())
      const stage = this.$refs[stageId][0].form.getFieldsValue()
      this.visibleStage = true
      this.stageMdl = { ...stage }
    },

    hasCloseImage() {
      this.isShow = true
      console.log('关闭')
    },
    showImage(type) {
      this.viewType = type
      this.isShow = !this.isShow
    },
    moment,

    pageInit() {
      var params = {
        page: 1,
        limit: 100
      }
      // 积分规则
      postMatchIntegralRulesList(params).then(res => {
        this.match_integral_rule_list = res.data.list
        console.log('积分规则=====', this.match_integral_rule_list)
        if (this.match_integral_rule_value === '') {
          this.match_integral_rule_value =
            res.data.list[0]['matchs_integral_rule_id']
        }
      })
      // 项目类型
      MatchsEventTypeList(params).then(res => {
        this.match_event_list = res.data.list
        console.log('项目类型=====', this.match_event_list)
        if (this.match_event_type_value === '') {
          this.match_event_type_value = res.data.list[0]['matchs_event_type_id']
        }
      })
      // 晋级规则
      postMatchStageRulesList(params).then(res => {
        this.match_stage_list = res.data.list
        console.log('晋级规则=====', this.match_stage_list)
        if (this.match_stage_value === '') {
          this.match_stage_value = res.data.list[0]['matchs_stage_rule_id']
        }
      })
    },
    close_match_event() {
      this.add_match_event_show = false
    },
    // 赛事列表
    getSysMatchInfo() {
      var params = {
        sys_match_id: this.sys_match_id
      }
      SysMatchInfo(params).then(res => {
        if (res.code === 1) {
          console.log(res)
          this.obj_match_info = res.data[0]
          this.event_list = res.data[0].event
          this.isGroup = res.data[0].is_group
          this.showEvent = true
          console.log('this.event_list===', this.event_list)
          this.obj_matchs = {
            start_date: moment(res.data[0]['start_time']).format('x'),
            stop_date: moment(res.data[0]['stop_time']).format('x')
          }
        }
      })
    },
    // 获取赛事列表
    getMatchTitleList() {
      var params = {}
      MatchtitleList(params).then(res => {
        if (res.code === 1) {
          console.log(res)
          this.match_title_list = res.data
        }
      })
    },
    sys_match_change(e) {
      console.log(e)

      this.sys_match_id = e
      this.getSysMatchInfo()

      this.getMatchTitleList()

      this.pageInit()
    },
    // 新增比赛项目
    add_event() {
      this.add_match_event_show = true
    },
    // 创建项目提交
    matchs_event_add(e) {
      const form = this.projectForm
      form
        .validateFields((errors, values) => {
          if (!errors) {
            console.log('values', values)
            var params = {
              matchs_type_id: values.matchs_type_id,
              match_champion_prize_description:
                values.match_champion_prize_description,
              match_champion_prize_description_en:
                values.match_champion_prize_description_en,
              sys_match_id: this.sys_match_id,
              matchs_event_type_id: values.match_event_type_id
            }
            console.log(params)
            postMatchEventAdd(params).then(res => {
              console.log(res)
              this.showEvent = false
              this.getSysMatchInfo()
              this.add_match_event_show = false
              // 重置表单数据
              form.resetFields()
            })
          }
        })
        .catch(err => {
          console.log(err)
        })
    },
    // 取消创建项目
    matchs_event_add_cancel(e) {
      this.add_match_event_show = false
    },

    handleAdd(e) {
      // console.log('新增赛段', e)
      this.obj_matchs_event = e
      this.stageMdl = null
      // console.log(this.stageMdl)
      this.visibleStage = true
    },
    // 新增赛段提交
    handleOkStage() {
      const form = this.$refs.stageModal.form
      this.stageConfirmLoading = true

      form.validateFields((errors, values) => {
        console.log('values====', values)
        if (!errors) {
          var params = {
            // 赛事项目ID string
            sys_match_id: this.obj_matchs_event.sys_match_id,
            // 赛段名称 中文
            match_stage_title: values.match_stage_title,
            // 赛段名称 英文
            match_stage_title_en: values.match_stage_title_en,
            // 是否展示摇跑指数成绩
            is_exponent: values.is_exponent,
            // 赛段开始时间 string
            match_stage_start_time: moment(values.start_time).format('X'),
            // 赛段结束时间 string
            match_stage_stop_time: moment(values.stop_time).format('X'),
            // 赛段最多积分 int
            // max_integral: values.matchs_max_integral,
            // // 每降低一名，积分递减数 int
            // sub_integral: values.matchs_sub_integral,
            // 获取积分人数类型，0：指定人数，1：按参赛人数/队伍数比例 int
            // get_integral_type: values.matchs_integral_type,
            // // 能够获得积分的人群值，0：人/队，1：百分比  float
            // get_integral_value: values.matchs_integral_value,
            // 赛段晋级类型 0：指定晋级人数，1：按参与人数晋级 int
            match_promotion_type: values.matchs_stage_rule,
            // 赛段晋级值，人数，百分比  float
            match_promotion_value: values.matchs_stage_rule_value,
            // 赛事ID string
            sys_sys_match_id: this.obj_matchs_event.sys_sys_match_id,
            // 赛段需运动的距离 float
            match_stage_distance: values.match_stage_distance,
            // 前端UI界面 1：标准赛事，2：摇跑  int
            view_type: values.fontEnd_ui
            // 赛段ID，存在即为编辑赛段 string
            // matchs_stage_id:'',
          }
          console.log('请求参数==', params)
          if (values.matchs_stage_id > 0) {
            const obj = {
              matchs_stage_id: values.matchs_stage_id,
              sys_sys_match_id: values.sys_sys_match_id,
              sys_match_id: values.sys_match_id
            }
            console.log(obj)
            console.log('编辑')
            postMatchEventStageAdd({ ...params, ...obj })
              .then(res => {
                if (res.code === 1) {
                  this.visibleStage = false
                  this.stageConfirmLoading = false
                  this.getSysMatchInfo()

                  this.getMatchTitleList()

                  this.pageInit()
                  // 刷新表格
                  this.$message.info(res.msg)
                } else {
                  this.$message.info(res.msg)
                }
              })
              .catch(err => {
                console.log(err)
              })
          } else {
            console.log('新增')
            // 新增
            postMatchEventStageAdd(params).then(res => {
              console.log(res)
              if (res.code === 1) {
                this.visibleStage = false
                this.stageConfirmLoading = false
                this.getSysMatchInfo()

                this.getMatchTitleList()

                this.pageInit()
                // 刷新表格
                this.$message.info(res.msg)
              } else {
                this.$message.info(res.msg)
              }
            })
          }
        } else {
          this.stageConfirmLoading = false
        }
      })
    },
    // 删除项目
    deleteMatch(id) {
      const params = {
        sys_match_id: id
      }
      postMatchEventDelete(params).then(res => {
        console.log(res)
        if (res.code === 1) {
          this.getSysMatchInfo()
          this.$message.success(res.msg)
        } else {
          this.$message.success(res.msg)
        }
      })
    },
    // 新增赛段提交 取消
    handleCancelStage() {
      this.visibleStage = false
    },
    deleteMatchStage(node) {
      var params = {
        sys_match_id: node.sys_match_id,
        sys_sys_match_id: node.sys_sys_match_id,
        matchs_stage_id: node.matchs_stage_id
      }
      postMatchEventStageDelete(params).then(res => {
        this.getSysMatchInfo()
        if (res.code === 1) {
          this.$message.success(res.msg)
        } else {
          this.$message.error(res.msg)
        }
      })
    }
  }
}
</script>
<style scoped>
.center_wrapper {
  margin-bottom: 20px;
}
.center_wrapper_select {
  width: 300px;
}
.center_wrapper_btn {
  text-align: right;
}
.switch {
  display: inline-block;
  margin-left: 10px;
  color: rgb(21, 126, 224);
  cursor: pointer;
}
.switch i {
  display: inline-block;
  width: 10px;
}
.match_stage_distance,
.matchs_stage_rule_value {
  width: 120px;
}
.stage_form >>> .ant-form-item {
  margin-bottom: 0;
}
</style>

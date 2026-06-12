<template>
  <div class="main">
    <a-card
      :title="isEdit?'编辑赛事':'新增赛事'"
      :bordered="false"
    >
      <a-form
        :form="form"
        @submit="MatchsTypeAddSubmit"
        style="background-color: #fff"
      >
        <a-form-item
          label="赛事标题(中文)"
          v-bind="formItemLayout"
        >
          <a-input
            v-decorator="[
              'match_title',
              {
                rules: [{ required: true, message: $t('matchs.title.placeholder') }],
                initialValue: match_title,
              },
            ]"
            :placeholder="$t('matchs.title.placeholder')"
            style="width: 60%; margin-right: 8px"
          >
          </a-input>
        </a-form-item>
        <a-form-item
          label="赛事标题(英文)"
          v-bind="formItemLayout"
        >
          <a-input
            v-decorator="[
              'match_title_en',
              {
                rules: [{ required: true, message: $t('matchs.title.placeholder') }],
                initialValue: match_title_en,
              },
            ]"
            :placeholder="$t('matchs.title.placeholder')"
            style="width: 60%; margin-right: 8px"
          >
          </a-input>
        </a-form-item>
        <a-form-item
          :label="$t('matchs.add.form.matchs_type')"
          v-bind="formItemLayout"
        >
          <a-select
            v-decorator="[
              'matchs_type_id',
              {
                rules: [{ required: true, message: '请选择赛事类型' }],
                initialValue: matchs_type_id,
              },
            ]"
            style="width: 60%; margin-right: 8px"
          >
            <a-select-option
              v-for="(item, index) in match_type_list"
              :key="index"
              :value="item.matchs_type_id"
            >
              {{ item.matchs_type_title }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <!-- 宣传图 -->
        <a-form-item
          :label="$t('matchs.add.form.matchs_img')"
          v-bind="formItemImgLayout"
        >
          <a-upload
            v-decorator="[
              'match_image',
              {
                rules: [{ required: true, message: '请上传宣传图' }],
              },
            ]"
            :action="BASE_URL + '/match/upload'"
            list-type="picture-card"
            :file-list="MatchImgFileList"
            @preview="MatchImgHandlePreview"
            @change="MatchImgHandleChange"
          >
            <div v-if="MatchImgFileList.length < 1">
              <a-icon type="upload" />
              <div class="ant-upload-text">
                {{ $t('matchs.add.form.matchs_img') }}
              </div>
            </div>
          </a-upload>
          <span slot="extra">
            <span style="font-size: 12px">建议尺寸：300*130</span>
            <span
              class="switch"
              @click="showImage(1)"
            >
              <a-icon
                type="eye"
                v-show="isShow"
              />
              <a-icon
                type="eye-invisible"
                v-show="!isShow"
              />
            </span></span>

          <a-modal
            :visible="MatchImgShow"
            :footer="null"
            @cancel="MatchImgHandleCancel"
          >
            <img
              alt="example"
              style="width: 100%"
              :src="MatchImg"
            />
          </a-modal>
        </a-form-item>
        <!-- 赛事列表图 -->
        <a-form-item
          label="赛事列表图"
          v-bind="formItemImgLayout"
        >
          <a-upload
            v-decorator="[
              'match_image_list',
              {
                rules: [{ required: true, message: '请上传赛事列表图' }],
              },
            ]"
            :action="BASE_URL + '/match/upload'"
            list-type="picture-card"
            :file-list="MatchImgListFileList"
            @preview="MatchImgListHandlePreview"
            @change="MatchImgListHandleChange"
          >
            <div v-if="MatchImgListFileList.length < 1">
              <a-icon type="upload" />
              <div class="ant-upload-text">
                {{ $t('matchs.add.form.matchs_img') }}
              </div>
            </div>
          </a-upload>
          <span slot="extra">
            <span style="font-size: 12px">建议尺寸：150*150</span>
            <span
              class="switch"
              @click="showImage(2)"
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
          </span>

          <a-modal
            :visible="MatchImgListShow"
            :footer="null"
            @cancel="MatchImgListHandleCancel"
          >
            <img
              alt="example"
              style="width: 100%"
              :src="MatchImgList"
            />
          </a-modal>
        </a-form-item>
        <a-form-item
          v-show="false"
          :label="$t('matchs.add.form.sys_user_type')"
          v-bind="formItemLayout"
        >
          <a-checkbox-group
            :options="sys_user_type_list"
            v-model="sys_user_type_list_value"
          />
          <span style="color: #b5b5b5">{{ $t('matchs.add.form.sys_user_type_placeholder') }}</span>
        </a-form-item>

        <a-form-item
          v-show="false"
          :label="$t('matchs.add.form.sys_user_sex')"
          v-bind="formItemLayout"
        >
          <a-checkbox-group
            :options="sys_user_sex_list"
            v-model="sys_user_sex_list_value"
          />
          <span style="color: #b5b5b5">{{ $t('matchs.add.form.sys_user_sex_placeholder') }}</span>
        </a-form-item>
        <!-- 参赛主体 -->
        <a-form-item
          :label="$t('matchs.add.form.group')"
          v-bind="formItemLayout"
        >
          <a-radio-group
            button-style="solid"
            v-decorator="[
              'matchs_group',
              {
                rules: [
                  {
                    required: true,
                    message: '请选择参赛主体',
                  },
                ],
                initialValue: is_group?is_group:0,
              },
            ]"
          >
            <a-radio-button :value="0">
              {{ $t('matchs.add.form.group_person') }}
            </a-radio-button>
            <a-radio-button :value="1">
              {{ $t('matchs.add.form.group_team') }}
            </a-radio-button>
          </a-radio-group>
        </a-form-item>
        <!-- 锦标赛展示 -->
        <a-form-item
          label="锦标赛是否展示"
          v-bind="formItemLayout"
        >
          <a-radio-group
            button-style="solid"
            v-decorator="[
              'website_show',
              {
                rules: [
                  {
                    required: true,
                    message: '请选择锦标赛是否展示',

                  },
                ],
                initialValue: this.website_show,

              },
            ]"
          >
            <a-radio :value="0">
              否
            </a-radio>
            <a-radio :value="1">
              是
            </a-radio>
          </a-radio-group>
        </a-form-item>
        <!-- 报名状态 -->
        <a-form-item
          label="报名状态"
          v-bind="formItemLayout"
        >
          <a-radio-group
            button-style="solid"
            v-decorator="[
              'join_status',
              {
                rules: [
                  {
                    required: true,
                    message: '请选择报名状态',
                  },
                ],
                initialValue: this.join_status,

              },
            ]"
          >
            <a-radio :value="0">
              开放报名
            </a-radio>
            <a-radio :value="1">
              关闭报名
            </a-radio>
            <a-radio :value="2">
              允许会员报名
            </a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item
          :label="$t('matchs.add.form.matchs_start_date')"
          v-bind="formItemLayout"
        >
          <a-date-picker
            v-decorator="[
              'matchs_start_date',
              {
                rules: [
                  {
                    required: true,
                    message: $t('matchs.add.form.matchs_start_date_plageholder'),
                  },
                ],
                initialValue: obj_matchs.start_date,
              },
            ]"
            :placeholder="$t('matchs.add.form.matchs_start_date_plageholder')"
            @change="startDatePickerChange"
          />
          <a-time-picker
            @change="startTimeChange"
            v-decorator="['matchs_start_time', { initialValue: obj_matchs.start_time }]"
            :placeholder="$t('matchs.add.form.matchs_start_date_plageholder')"
          >
          </a-time-picker>
        </a-form-item>

        <a-form-item
          :label="$t('matchs.add.form.matchs_stop_date')"
          v-bind="formItemLayout"
        >
          <a-date-picker
            v-decorator="[
              'matchs_stop_date',
              {
                rules: [
                  {
                    required: true,
                    message: $t('matchs.add.form.matchs_stop_date_plageholder'),
                  },
                ],
                initialValue: obj_matchs.stop_date,
              },
            ]"
            :placeholder="$t('matchs.add.form.matchs_stop_date_plageholder')"
            @change="stopDatePickerChange"
          />
          <a-time-picker
            @change="stopTimeChange"
            v-decorator="['matchs_stop_time', { initialValue: obj_matchs.stop_time }]"
            :placeholder="$t('matchs.add.form.matchs_stop_date_plageholder')"
          >
          </a-time-picker>
        </a-form-item>
        <!-- 报名时间 -->
        <!-- <a-form-item :label="$t('matchs.add.form.matchs_apply_date')" v-bind="formItemLayout">
          <a-date-picker
            v-decorator="[
              'matchs_join_date',
              {
                rules: [
                  {
                    required: true,
                    message: $t('matchs.add.form.matchs_apply_date_plageholder')
                  }
                ],
                initialValue: obj_matchs.apply_date
              }
            ]"
            :disabled="!isOpen"
            :disabled-date="disabledApplyDate"
            :placeholder="$t('matchs.add.form.matchs_apply_date_plageholder')"
            @change="applyDatePickerChange"
          />
          <a-time-picker
            :disabled="!isOpen"
            @change="applyTimeChange"
            v-decorator="['matchs_join_time', { initialValue: obj_matchs.apply_time }]"
            :placeholder="$t('matchs.add.form.matchs_apply_date_plageholder')"
          >
          </a-time-picker>
        </a-form-item> -->
        <a-form-item
          :label="$t('matchs.add.form.matchs_phone')"
          v-bind="formItemLayout"
        >
          <a-input
            v-decorator="[
              'matchs_phone',
              {
                rules: [
                  {
                    required: true,
                    message: $t('matchs.add.form.matchs_phone_plageholder'),
                  },
                ],
                initialValue: match_phone,
              },
            ]"
            style="width: 60%; margin-right: 8px"
          >
            <a-select
              slot="addonBefore"
              v-decorator="['prefix', { initialValue: match_phone_prefix }]"
              style="width: 70px"
            >
              <a-select-option value="86"> +86 </a-select-option>
              <a-select-option value="87"> +87 </a-select-option>
              <a-select-option value="1"> +1 </a-select-option>
            </a-select>
          </a-input>
        </a-form-item>

        <a-form-item
          :label="$t('matchs.add.form.matchs_email')"
          v-bind="formItemLayout"
        >
          <a-input
            v-decorator="[
              'matchs_email',
              {
                rules: [{ required: true, message: $t('matchs.email.placeholder') }],
                initialValue: match_email,
              },
            ]"
            :placeholder="$t('matchs.email.placeholder')"
            style="width: 60%; margin-right: 8px"
          >
          </a-input>
        </a-form-item>

        <a-form-item
          label="赛事说明(中文)"
          v-bind="formItemLayout"
        >
          <quill-editor
            ref="myTextEditor"
            class="edit_container"
            v-model="match_description"
            :options="editorOption"
          >
          </quill-editor>
        </a-form-item>
        <a-form-item
          label="赛事说明(英文)"
          v-bind="formItemLayout"
        >
          <quill-editor
            ref="myTextEditorEn"
            class="edit_container"
            v-model="match_description_en"
            :options="editorOptionEn"
          >
          </quill-editor>
        </a-form-item>
        <!-- <a-form-item v-bind="formItemLayoutWithOutLabel" style="padding-bottom: 20px">
          <a-button type="primary" html-type="submit" :loading="button_loading">
            {{ $t('medal.add.form.submit') }}
          </a-button>
        </a-form-item> -->
        <a-form-item v-bind="formItemLayoutWithOutLabel">
          <a-row
            type="flex"
            justify="center"
            :gutter="16"
          >
            <a-col :md="3">
              <a-button @click="cancel"> 取消 </a-button>
            </a-col>
            <a-col :md="3">
              <a-button
                type="primary"
                html-type="submit"
              > {{ $t('medal.add.form.submit') }}</a-button>
            </a-col>
          </a-row>
        </a-form-item>
      </a-form>
    </a-card>
    <a-modal
      @cancel="hasCloseImage"
      :footer="null"
      :visible="!isShow"
      :title="viewType === 1 ? '宣传图片' : '赛事列表图片'"
    >
      <img
        :src="require(`@/assets/images/${viewType === 1 ? 'matchImg.png' : 'matchImgList.png'}`)"
        alt=""
        width="100%"
      />
    </a-modal>
  </div>
</template>

<script>
import { MatchsAdd, MatchsTypeList, SysMatchInfo } from '@/api/matchs'
import { postSysUserTypeList, postSysUserSexList } from '@/api/sysuser'
import 'quill/dist/quill.core.css'
import 'quill/dist/quill.snow.css'
import 'quill/dist/quill.bubble.css'
import { quillEditor } from 'vue-quill-editor'
import moment from 'moment'

export default {
  components: {
    quillEditor
  },
  data() {
    return {
      match_description: '',
      match_description_en: '',
      editorOption: {},
      editorOptionEn: {},
      obj_matchs: {
        start_date: '',
        start_time: '',
        stop_date: '',
        stop_time: ''
        // join_date: '',
        // join_time: '',
      },
      MatchImgFileList: [],
      MatchImgListFileList: [],
      MatchImgShow: false,
      MatchImgListShow: false,
      MatchImg: '',
      MatchImgList: '',
      match_image: '',
      match_image_list: '',
      BASE_URL: '',
      start_time: false,
      stop_time: false,
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
      formItemImgLayout: {
        labelCol: {
          xs: { span: 24 },
          sm: { span: 3 }
        },
        wrapperCol: {
          xs: { span: 24 },
          sm: { span: 6 }
        }
      },
      formItemLayoutWithOutLabel: {
        wrapperCol: {
          xs: { span: 24, offset: 0 },
          sm: { span: 18, offset: 3 }
        },
        labelCol: {
          xs: { span: 24 },
          sm: { span: 24 }
        }
      },
      match_title: '',
      match_title_en: '',
      matchs_type_id: '',
      is_group: null,
      join_status: null,
      website_show: null,
      match_type_list: [],
      sys_user_type_list: [],
      sys_user_type_list_value: [],
      sys_user_sex_list: [],
      sys_user_sex_list_value: [],
      sys_match_id: '',
      match_email: '',
      match_phone: '',
      match_phone_prefix: '86',
      isShow: true,
      viewType: null,
      isEdit: false
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
    if (this.$route.query.sys_match_id !== undefined) {
      this.isEdit = true
      this.sys_match_id = this.$route.query.sys_match_id
      this.getSysMatchInfo()
    }
    this.getMatchTypeList()
    this.getSysUserTypeList()
    this.getSysUserSexList()
  },
  computed: {
    isOpen() {
      const startDate = this.obj_matchs.start_date
      const stopDate = this.obj_matchs.stop_date
      const startTime = this.obj_matchs.start_time
      const stopTime = this.obj_matchs.stop_time
      if (startDate && stopDate && startTime && stopTime) {
        return true
      } else {
        return false
      }
    }
  },
  methods: {
    showImage(type) {
      this.viewType = type
      this.isShow = !this.isShow
    },
    hasCloseImage() {
      this.isShow = true
      console.log('关闭')
    },
    // 返回上一页
    cancel() {
      this.$router.go(-1)
    },
    // 日期区间选择
    disabledApplyDate(currentDate) {
      // console.log('currentDate====', currentDate.format('YYYY-MM-DD'))
      const currentDateFormat = currentDate.format('YYYY-MM-DD')
      const startValue = this.obj_matchs.start_date
      const endValue = this.obj_matchs.stop_date
      console.log(startValue)
      if (!currentDateFormat || !startValue || !endValue) {
        return false
      } else {
        return currentDateFormat <= startValue || currentDateFormat >= endValue
      }
    },
    test_change(e) {
      console.log('sys_user_type_list_value', this.sys_user_type_list_value)
      console.log('sys_user_sex_list_value', this.sys_user_sex_list_value)
    },
    moment,
    // 开始日期选择
    startDatePickerChange(dates, dateStrings) {
      this.obj_matchs.start_date = dateStrings
      console.log(dateStrings)
    },
    // 开始时间选择
    startTimeChange(dates, dateStrings) {
      this.obj_matchs.start_time = dateStrings
      // this.obj_matchs.start_time = open
    },
    // 报名日期选择
    // applyDatePickerChange(dates, dateStrings) {
    //   this.obj_matchs.join_date = dateStrings
    // },
    // // 报名时间选择
    // applyTimeChange(dates, dateStrings) {
    //   this.obj_matchs.join_time = dateStrings
    //   // this.obj_matchs.start_time = open
    // },
    // 结束日期选择
    stopDatePickerChange(dates, dateStrings) {
      this.obj_matchs.stop_date = dateStrings
    },
    // 结束时间选择
    stopTimeChange(dates, dateStrings) {
      this.obj_matchs.stop_time = dateStrings
      // this.obj_matchs.stop_time = open
    },
    // 关闭时间选择框
    timeClose() {
      this.start_time = false
    },
    // 获取赛事类型列表
    getMatchTypeList() {
      var params = {
        type: 'all'
      }
      MatchsTypeList(params).then(res => {
        if (res.code === 1) {
          this.match_type_list = res.data.list
          this.matchs_type_id = res.data.list[0]['matchs_type_id']
        }
      })
    },
    // 获取用户类型列表
    getSysUserTypeList() {
      var params = {}
      postSysUserTypeList(params).then(res => {
        if (res.code === 1) {
          this.sys_user_type_list = res.data.list
          if (this.sys_user_type_list_value.length === 0) {
            this.sys_user_type_list_value = res.data.value
          }
        }
      })
    },
    // 获取用户性别列表
    getSysUserSexList() {
      var params = {}
      postSysUserSexList(params).then(res => {
        if (res.code === 1) {
          this.sys_user_sex_list = res.data.list
          if (this.sys_user_sex_list_value.length === 0) {
            this.sys_user_sex_list_value = res.data.value
          }
        }
      })
    },
    // 查询赛事详细信息
    getSysMatchInfo() {
      var params = {
        sys_match_id: this.sys_match_id
      }
      SysMatchInfo(params).then(res => {
        console.log(res)
        if (res.code === 1 && res.data.length === 1) {
          const startDate = res.data[0]['start_time'].split(' ')[0]
          const startTime = res.data[0]['start_time'].split(' ')[1]
          const stopDate = res.data[0]['stop_time'].split(' ')[0]
          const stopTime = res.data[0]['stop_time'].split(' ')[1]
          this.sys_user_type_list_value = res.data[0]['arrOfUserType']
          this.sys_user_sex_list_value = res.data[0]['arrOfUserSex']
          this.match_title = res.data[0]['match_title']
          this.match_title_en = res.data[0]['match_title_en']
          this.match_email = res.data[0]['match_email']
          this.match_phone = res.data[0]['match_phone']
          this.matchs_type_id = res.data[0]['matchs_type_id']
          this.match_description = res.data[0]['match_description']
          this.match_description_en = res.data[0]['match_description_en']
          this.match_image = res.data[0]['match_image']
          this.match_image_list = res.data[0]['match_image_list']
          this.MatchImgList =
            res.data[0]['server_url'] + '/' + res.data[0]['match_image_list']
          this.MatchImg =
            res.data[0]['server_url'] + '/' + res.data[0]['match_image']
          this.MatchImgListFileList = res.data[0]['image_list_list']
          this.MatchImgFileList = res.data[0]['image_list']
          this.is_group = res.data[0]['is_group']
          this.join_status = res.data[0]['join_status']
          this.website_show = res.data[0]['website_show']
          this.obj_matchs = {
            start_date: moment(startDate, 'YYYY-MM-DD').format('YYYYMMDD'),
            start_time: moment(startTime, 'H:m:s'),
            stop_date: moment(stopDate, 'YYYY-MM-DD').format('YYYYMMDD'),
            stop_time: moment(stopTime, 'H:m:s')
            // join_date: moment(res.data[0]['join_date']),
            // join_time: moment(res.data[0]['join_time'])
          }
        }
      })
    },
    // 保存提交
    MatchsTypeAddSubmit(e) {
      e.preventDefault()
      console.log(this.match_image)
      if (this.match_image !== '') {
        this.form.setFieldsValue({
          match_image: this.match_image
        })
      } else {
        this.form.setFieldsValue({
          match_image: undefined
        })
      }
      if (this.match_image_list !== '') {
        this.form.setFieldsValue({
          match_image_list: this.match_image_list
        })
      } else {
        this.form.setFieldsValue({
          match_image_list: undefined
        })
      }
      this.form.validateFields((err, values) => {
        console.log(values)
        if (!err) {
          this.button_loading = true
          values['matchs_content'] = this.match_description
          values['matchs_content_en'] = this.match_description_en
          values['matchs_start_date'] = moment(
            values['matchs_start_date']
          ).format('YYYY-MM-DD')
          values['matchs_stop_date'] = moment(
            values['matchs_stop_date']
          ).format('YYYY-MM-DD')
          // values['matchs_join_date'] = moment(values['matchs_join_date']).format('YYYY-MM-DD')
          values['matchs_start_time'] = moment(
            values['matchs_start_time']
          ).format('H:m:s')
          values['matchs_stop_time'] = moment(
            values['matchs_stop_time']
          ).format('H:m:s')
          // values['matchs_join_time'] = moment(values['matchs_join_time']).format('H:m:s')
          values['sys_user_type_list_value'] = this.sys_user_type_list_value
          values['sys_user_sex_list_value'] = this.sys_user_sex_list_value
          values['is_group'] = values['matchs_group'] - 0
          values['join_status'] = values['join_status'] - 0
          values['website_show'] = values['website_show'] - 0
          values['match_image'] = this.match_image
          values['match_image_list'] = this.match_image_list
          values['matchs_type_id'] = this.matchs_type_id
          if (this.sys_match_id !== undefined && this.sys_match_id !== '') {
            values['sys_match_id'] = this.sys_match_id
          }
          console.log(values)
          MatchsAdd(values).then(res => this.MatchsAddSuccess(res))
        }
      })
    },
    MatchsAddSuccess(res) {
      this.button_loading = false
      if (res.code === 1) {
        this.$message.success(res.msg)
        this.$router.push({
          path: '/matchs/list'
        })
      } else {
        this.$message.error(res.msg)
      }
    },
    async MatchImgHandlePreview(file) {
      this.MatchImgShow = true
    },
    MatchImgHandleCancel() {
      this.MatchImgShow = false
    },
    MatchImgHandleChange({ file, fileList }) {
      this.MatchImgFileList = fileList

      if (file.status === 'done') {
        this.MatchImg = file.response.data.matchs_img_path
        this.match_image = file.response.data.file_path.file_path
      } else if (file.status === 'removed') {
        this.match_image = ''
        console.log(this.match_image)
      }
    },

    async MatchImgListHandlePreview(file) {
      this.MatchImgListShow = true
    },
    MatchImgListHandleCancel() {
      this.MatchImgListShow = false
    },
    MatchImgListHandleChange({ file, fileList }) {
      // console.log('图片上传改变', file.status)
      // console.log('图片文件', file)
      this.MatchImgListFileList = fileList
      if (file.status === 'done') {
        this.MatchImgList = file.response.data.matchs_img_path
        this.match_image_list = file.response.data.file_path.file_path
        // console.log('图片状态', this.match_image_list)
      } else if (file.status === 'removed') {
        this.match_image_list = ''
      }
    }
  }
}
</script>

<style scoped>
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
</style>

<template>
  <div class="main">
    <a-card :title="$t('matchs.event_add')" :bordered="false">
      <a-form :form="form" @submit="MatchsTypeAddSubmit" style="background-color: #fff">
        <a-form-item :label="$t('matchs.add.form.matchs_title')" v-bind="formItemLayout">
          <a-input
            v-decorator="[
              'matchs_title',
              { rules: [{ required: true, message: $t('matchs.title.placeholder') }], initialValue: match_title },
            ]"
            :placeholder="$t('matchs.title.placeholder')"
            style="width: 60%; margin-right: 8px"
          >
          </a-input>
        </a-form-item>

        <a-form-item :label="$t('matchs.add.form.matchs_type')" v-bind="formItemLayout">
          <a-select v-model="matchs_type_id" style="width: 60%; margin-right: 8px">
            <a-select-option v-for="(item, index) in match_type_list" :key="index" :value="item.matchs_type_id">
              {{ item.matchs_type_title }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item :label="$t('matchs.add.form.matchs_start_date')" v-bind="formItemLayout">
          <a-date-picker
            v-decorator="[
              'matchs_start_date',
              {
                rules: [{ required: true, message: $t('matchs.add.form.matchs_start_date_plageholder') }],
                initialValue: obj_matchs.start_time,
              },
            ]"
            :placeholder="$t('matchs.add.form.matchs_start_date_plageholder')"
            @change="startDatePickerChange"
          />
          <a-time-picker
            :open="start_time"
            @openChange="startTimeChange"
            v-decorator="['matchs_start_time', { initialValue: obj_matchs.start_time }]"
            :placeholder="$t('matchs.add.form.matchs_start_date_plageholder')"
          >
          </a-time-picker>
        </a-form-item>

        <a-form-item :label="$t('matchs.add.form.matchs_stop_date')" v-bind="formItemLayout">
          <a-date-picker
            v-decorator="[
              'matchs_stop_date',
              {
                rules: [{ required: true, message: $t('matchs.add.form.matchs_stop_date_plageholder') }],
                initialValue: obj_matchs.stop_time,
              },
            ]"
            :placeholder="$t('matchs.add.form.matchs_stop_date_plageholder')"
            @change="stopDatePickerChange"
          />
          <a-time-picker
            :open="stop_time"
            @openChange="stopTimeChange"
            v-decorator="['matchs_stop_time', { initialValue: obj_matchs.stop_time }]"
            :placeholder="$t('matchs.add.form.matchs_stop_date_plageholder')"
          >
          </a-time-picker>
        </a-form-item>

        <a-form-item :label="$t('matchs.add.form.matchs_phone')" v-bind="formItemLayout">
          <a-input
            v-decorator="[
              'matchs_phone',
              {
                rules: [{ required: true, message: $t('matchs.add.form.matchs_phone_plageholder') }],
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

        <a-form-item :label="$t('matchs.add.form.matchs_email')" v-bind="formItemLayout">
          <a-input
            v-decorator="[
              'matchs_email',
              { rules: [{ required: true, message: $t('matchs.email.placeholder') }], initialValue: match_email },
            ]"
            :placeholder="$t('matchs.email.placeholder')"
            style="width: 60%; margin-right: 8px"
          >
          </a-input>
        </a-form-item>

        <a-form-item :label="$t('matchs.add.form.matchs_content')" v-bind="formItemLayout">
          <quill-editor
            ref="myTextEditor"
            v-model="match_description"
            :config="editorOption"
            style="width: 60%; margin-right: 8px"
          >
          </quill-editor>
        </a-form-item>

        <a-form-item v-bind="formItemLayoutWithOutLabel" style="padding-bottom: 20px">
          <a-button type="primary" html-type="submit" :loading="button_loading">
            {{ $t('medal.add.form.submit') }}
          </a-button>
        </a-form-item>
      </a-form>
    </a-card>
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
      editorOption: {},
      obj_matchs: {
        start_date: '',
        start_time: '',
        stop_date: '',
        stop_time: ''
      },
      MatchImgFileList: [],
      MatchImgShow: false,
      MatchImg: '',
      match_img: '',
      BASE_URL: '',
      start_time: false,
      stop_time: false,
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
      match_title: '',
      matchs_type_id: '',
      match_type_list: [],
      sys_user_type_list: [],
      sys_user_type_list_value: [],
      sys_user_sex_list: [],
      sys_user_sex_list_value: [],
      sys_match_id: '',
      match_email: '',
      match_phone: '',
      match_phone_prefix: '86'
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
      this.sys_match_id = this.$route.query.sys_match_id
      this.getSysMatchInfo()
    }
    this.getMatchTypeList()
    this.getSysUserTypeList()
    this.getSysUserSexList()
  },
  methods: {
    test_change(e) {
      console.log('sys_user_type_list_value', this.sys_user_type_list_value)
      console.log('sys_user_sex_list_value', this.sys_user_sex_list_value)
    },
    moment,
    // 开始日期选择
    startDatePickerChange(dates, dateStrings) {
      this.obj_matchs.start_date = dateStrings
    },
    // 开始时间选择
    startTimeChange(open) {
      this.start_time = open
      this.obj_matchs.start_time = open
    },
    // 结束日期选择
    stopDatePickerChange(dates, dateStrings) {
      this.obj_matchs.stop_date = dateStrings
    },
    // 结束时间选择
    stopTimeChange(open) {
      this.stop_time = open
      this.obj_matchs.stop_time = open
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
      MatchsTypeList(params).then((res) => {
        if (res.code === 1) {
          this.match_type_list = res.data.list
          this.matchs_type_id = res.data.list[0]['matchs_type_id']
        }
      })
    },
    // 获取用户类型列表
    getSysUserTypeList() {
      var params = {}
      postSysUserTypeList(params).then((res) => {
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
      postSysUserSexList(params).then((res) => {
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
      SysMatchInfo(params).then((res) => {
        if (res.code === 1 && res.data.length === 1) {
          console.log(res.data[0])

          this.sys_user_type_list_value = res.data[0]['arrOfUserType']
          this.sys_user_sex_list_value = res.data[0]['arrOfUserSex']
          this.match_title = res.data[0]['match_title']
          this.match_email = res.data[0]['match_email']
          this.match_phone = res.data[0]['match_phone']
          this.matchs_type_id = res.data[0]['matchs_type_id']
          this.match_description = res.data[0]['match_description']
          this.match_image = res.data[0]['match_image']
          this.MatchImg = res.data[0]['server_url'] + '/' + res.data[0]['match_image']
          this.MatchImgFileList = res.data[0]['image_list']
          this.obj_matchs = {
            start_date: moment(res.data[0]['start_time']),
            start_time: moment(res.data[0]['start_time']),
            stop_date: moment(res.data[0]['stop_time']),
            stop_time: moment(res.data[0]['stop_time'])
          }
        }
      })
    },
    // 保存提交
    MatchsTypeAddSubmit(e) {
      e.preventDefault()
      this.form.validateFields((err, values) => {
        console.log(values)
        if (!err) {
          this.button_loading = true
          values['matchs_content'] = this.match_description
          values['matchs_start_date'] = moment(values['matchs_start_date']).format('YYYY-MM-DD')
          values['matchs_stop_date'] = moment(values['matchs_stop_date']).format('YYYY-MM-DD')
          values['matchs_start_time'] = moment(values['matchs_start_time']).format('H:m:s')
          values['matchs_stop_time'] = moment(values['matchs_stop_time']).format('H:m:s')
          values['sys_user_type_list_value'] = this.sys_user_type_list_value
          values['sys_user_sex_list_value'] = this.sys_user_sex_list_value
          values['match_image'] = this.match_image
          values['matchs_type_id'] = this.matchs_type_id
          if (this.sys_match_id !== undefined && this.sys_match_id !== '') {
            values['sys_match_id'] = this.sys_match_id
          }
          console.log(values)
          MatchsAdd(values).then((res) => this.MatchsAddSuccess(res))
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
        this.match_img = file.response.data.file_path.file_path
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
</style>

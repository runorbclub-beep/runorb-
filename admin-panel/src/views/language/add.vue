<template>
  <div class="main">
    <a-form :form="form" @submit="MedalAddSubmit">

      <a-form-item :label="$t('medal.add.form.medal_name')" v-bind="formItemLayout">
        <a-input
          v-decorator="[
            'medal_name',
            { rules: [{ required: true, message: $t('medal.search.placeholder') }] },
          ]"
          :placeholder="$t('medal.search.placeholder')"
          style="width: 60%; margin-right: 8px"
        >
        </a-input>
      </a-form-item>

      <a-form-item :label="$t('medal.add.form.medal_img')" v-bind="formItemLayout">
        <div style="float:left;">
          <a-upload
            :action="BASE_URL+'/medal/upload'"
            list-type="picture-card"
            :file-list="unHasMedalImgFileList"
            @preview="unHasMedalImgHandlePreview"
            @change="unHasMedalImgHandleChange"
          >
            <div v-if="unHasMedalImgFileList.length < 1 ">
              <a-icon type="upload" />
              <div class="ant-upload-text">
                {{ $t('medal.add.form.disable_medal_img') }}
              </div>
            </div>
          </a-upload>
          <a-modal :visible="unHasMedalImgShow" :footer="null" @cancel="unHasMedalImgHandleCancel">
            <img alt="example" style="width: 100%" :src="unHasMedalImg" />
          </a-modal>
        </div>

        <div style="float:left;">
          <a-upload
            :action="BASE_URL+'/medal/upload'"
            list-type="picture-card"
            :file-list="HasMedalImgFileList"
            @preview="HasMedalImgHandlePreview"
            @change="HasMedalImgHandleChange"
          >
            <div v-if="HasMedalImgFileList.length < 1 ">
              <a-icon type="upload" />
              <div class="ant-upload-text">
                {{ $t('medal.add.form.enable_medal_img') }}
              </div>
            </div>
          </a-upload>
          <a-modal :visible="HasMedalImgShow" :footer="null" @cancel="HasMedalImgHandleCancel">
            <img alt="example" style="width: 100%" :src="HasMedalImg" />
          </a-modal>
        </div>
      </a-form-item>

      <a-form-item
        v-for="(k, index) in form.getFieldValue('keys')"
        :key="k"
        v-bind="formItemLayout"
        :label="$t('medal.level')+'  '+(index+1)"
        :required="true"
      >
        <a-input
          v-decorator="[
            `medal_level[${k}]`,
            {
              validateTrigger: ['change', 'blur'],
              rules: [
                {
                  required: true,
                  whitespace: true,
                  message: $t('medal.add.level.placeholder'),
                },
              ],
            },
          ]"
          :placeholder="$t('medal.add.level.placeholder')"
          style="width: 60%; margin-right: 8px"
        />
        <a-icon
          v-if="form.getFieldValue('keys').length > 1"
          class="dynamic-delete-button"
          type="minus-circle-o"
          :disabled="form.getFieldValue('keys').length === 1"
          @click="() => remove(k)"
        />

        <a-icon
          class="dynamic-delete-button"
          style="margin-left:10px;"
          type="edit"
          :disabled="form.getFieldValue('keys').length === 1"
          @click="() => remove(k)"
        />
      </a-form-item>
      <a-form-item v-bind="formItemLayoutWithOutLabel">
        <a-button type="dashed" style="width: 60%" @click="add">
          <a-icon type="plus" /> {{ $t('medal.add.level') }}
        </a-button>
      </a-form-item>
      <a-form-item v-bind="formItemLayoutWithOutLabel">
        <a-button type="primary" html-type="submit" :loading="button_loading">
          {{ $t('medal.add.form.submit') }}
        </a-button>
      </a-form-item>
    </a-form>
  </div>
</template>

<script>
import { postMedalAdd } from '@/api/medal'
let id = 1
export default {
  data() {
    return {
      BASE_URL: '',
      unHasMedalImgShow: false,
      unHasMedalImg: '',
      unHasMedalImgFileList: [],
      HasMedalImgShow: false,
      HasMedalImg: '',
      HasMedalImgFileList: [],
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
    this.add()
  },
  beforeCreate() {
    this.form = this.$form.createForm(this, { name: 'dynamic_form_item' })
    this.form.getFieldDecorator('keys', { initialValue: [], preserve: true })
  },
  methods: {
    testsss() {
      console.log(this.form.getFieldValue('keys'))
      console.log(this.unHasMedalImg)
    },
    remove(k) {
      const { form } = this
      const keys = form.getFieldValue('keys')
      if (keys.length === 1) {
        return
      }
      form.setFieldsValue({
        keys: keys.filter(key => key !== k)
      })
    },
    add() {
      const { form } = this
      const keys = form.getFieldValue('keys')
      const nextKeys = keys.concat(id++)
      form.setFieldsValue({
        keys: nextKeys
      })
    },
    MedalAddSubmit(e) {
      e.preventDefault()
      this.form.validateFields((err, values) => {
        if (!err) {
          this.button_loading = true
          values['unHasMedalImg'] = this.unHasMedalImg
          values['HasMedalImg'] = this.HasMedalImg
          console.log(values)
          postMedalAdd(values)
            .then((res) => this.MedalAddSuccess(res))
            .finally(() => {
              console.log('错误')
            })
        }
      })
    },
    MedalAddSuccess(res) {
      this.button_loading = false
      console.log(res)
    },
    unHasMedalImgHandleCancel() {
      this.unHasMedalImgShow = false
    },
    async unHasMedalImgHandlePreview(file) {
      this.unHasMedalImgShow = true
    },
    unHasMedalImgHandleChange({ file, fileList }) {
      this.unHasMedalImgFileList = fileList
      if (file.status === 'done') {
        this.unHasMedalImg = file.response.data.medal_img_path
      }
    },
    HasMedalImgHandleCancel() {
      this.HasMedalImgShow = false
    },
    async HasMedalImgHandlePreview(file) {
      this.HasMedalImgShow = true
    },
    HasMedalImgHandleChange({ file, fileList }) {
      this.HasMedalImgFileList = fileList
      if (file.status === 'done') {
        this.HasMedalImg = file.response.data.medal_img_path
      }
    }
  }
}
</script>

<style lang="less" scoped>
</style>

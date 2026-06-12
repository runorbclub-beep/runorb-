<template>
  <a-card :bordered="false">
    <a-spin :spinning="loading">
      <a-form :form="form" v-bind="formLayout" @submit="handleSubmit">
        <!-- 检查是否有 id 并且大于0，大于0是修改。其他是新增，新增不显示商品ID -->
        <a-form-item v-show="false" label="文章ID">
          <a-input v-decorator="['sys_new_id', { initialValue: 0 }]" disabled />
        </a-form-item>
        <a-form-item label="文章标题">
          <a-input v-decorator="['news_title', { rules: [{ required: true, message: '请输入文章标题!' }] }]" />
        </a-form-item>
        <a-form-item label="文章类型" has-feedback>
          <a-select
            v-decorator="['news_type', { rules: [{ required: true, message: '请选择文章类型!' }] }]"
            placeholder="请选择文章类型"
          >
            <a-select-option :key="index" v-for="(item, index) of newsType" :value="item.value">
              {{ item.value }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="创建日期">
          <a-date-picker
            v-decorator="[
              'created_date',
              {
                rules: [
                  {
                    required: true,
                    message: '请输入创建日期'
                  }
                ]
              }
            ]"
            showTime
            placeholder="请输入创建日期"
          />
        </a-form-item>
        <!-- <a-form-item label="文章观看数">
          <a-input v-decorator="['view_num', { rules: [{ required: true, message: '请输入文章观看数!' }] }]" />
        </a-form-item> -->
        <a-form-item label="文章首图" extra="">
          <a-upload
            v-decorator="[
              'news_img',
              {
                getValueFromEvent: normFile,
                rules: [{ required: true, message: '请上传图片!' }]
              }
            ]"
            name="file"
            :action="baseUrl + '/match/upload'"
            list-type="picture"
            :before-upload="beforeUpload"
            :headers="headers"
            accept="image/*"
            :file-list="fileList"
            @preview="handlePreview"
            @change="handleChange"
          >
            <a-button> <a-icon type="upload" /> 点击上传图片 </a-button>
          </a-upload>
          <a-modal :visible="previewVisible" :footer="null" @cancel="handleCancel">
            <img alt="example" style="width: 100%" :src="previewImage" />
          </a-modal>
        </a-form-item>
        <a-form-item label="文章内容">
          <richText
            :content="content"
            @editorChange="editorChange"
            v-decorator="['news_content', { rules: [{ required: true, message: '请输入文章内容!' }] }]"
          >
          </richText>
        </a-form-item>
        <a-form-item v-bind="formItemLayoutWithOutLabel">
          <a-button type="primary" html-type="submit">
            保存
          </a-button>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-card>
</template>

<script>
import pick from 'lodash.pick'
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import richText from './course-rich-text'
import moment from 'moment'
import { addNews } from '@/api/news'
// 表单字段
const fields = ['sys_new_id', 'news_title', 'news_type', 'created_date', 'news_img', 'news_content']
const newsType = [
  {
    value: '新闻'
  },
  {
    value: '资讯'
  }
]
export default {
  props: {
    loading: {
      type: Boolean,
      default: () => false
    }
  },
  components: {
    richText
  },

  data() {
    this.formLayout = {
      labelCol: {
        xs: { span: 24 },
        sm: { span: 3 }
      },
      wrapperCol: {
        xs: { span: 24 },
        sm: { span: 18 }
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
      token: storage.get(ACCESS_TOKEN),
      previewVisible: false,
      previewImage: '',
      fileList: [],
      isEdit: true,
      newsType,
      content: '',
      visible: false,
      model: null,
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      ModifyImg: false
    }
  },
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')

    console.log(this.imgURL)
    this.visible = Boolean(this.$route.query.visible)
    this.model = JSON.parse(this.$route.query.mdl)
    if (this.model !== null) {
      this.model.news_type = this.model.news_type === '1' ? '资讯' : '新闻'
    }

    console.log(this.model)
    // 防止表单未注册
    fields.forEach(v => this.form.getFieldDecorator(v))

    // 当 model 发生改变时，为表单设置值
    if (this.model !== null) {
      this.isEdit = true
      this.content = this.model.news_content
      const newsImg = this.model.news_img
      const newsImgName = this.model.news_img.split('/')[this.model.news_img.split('/').length - 1]
      this.$set(this.fileList, 0, {
        uid: '-1',
        name: newsImgName,
        status: 'done',
        url: this.imgURL + newsImg
      })
      console.log(this.content)

      this.model && this.form.setFieldsValue(pick(this.model, fields))
    } else {
      this.isEdit = false
    }
  },
  mounted() {},
  computed: {
    // 自定义请求头
    headers() {
      return {
        token: this.token
      }
    }
  },
  watch: {
    content: function() {
      console.log(this.content)
    }
  },

  methods: {
    //   提交
    handleSubmit(e) {
      e.preventDefault() // 这样就好了
      //   this.confirmLoading = true

      this.form
        .validateFields((errors, values) => {
          if (!errors) {
            console.log('values', values)

            const requestParameters = {
              news_title: values.news_title,
              news_content: this.content,
              news_img:
                values.sys_new_id > 0
                  ? this.ModifyImg
                    ? values.news_img[0].response.data.file_path.file_path
                    : values.news_img
                  : values.news_img[0].response.data.file_path.file_path,
              news_type: values.news_type === '资讯' ? 1 : 2,
              created_date: moment(values['created_date']).format('YYYY-MM-DD H:m:s')
            }
            console.log('requestParameters=====', requestParameters)
            if (values.sys_new_id > 0) {
              const obj = { sys_new_id: values.sys_new_id }
              console.log('编辑')
              addNews({ ...requestParameters, ...obj })
                .then(res => {
                  console.log(res)
                  this.$message.info('修改成功')
                })
                .catch(err => {
                  console.log(err)
                })
            } else {
              // 新增
              addNews(requestParameters).then(res => {
                console.log(res)

                this.$message.info('新增成功')
              })
            }
          } else {
            //   this.confirmLoading = false
          }
        })
        .catch(err => {
          console.log(err)
        })
    },
    editorChange: function(html) {
      this.content = html
      //   this.form.setFieldsValue(['news_content'], html)
      this.form.setFieldsValue({
        news_content: html
      })
    },
    // 将上传的文件转成base64格式
    getBase64(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.readAsDataURL(file)
        reader.onload = () => resolve(reader.result)
        reader.onerror = error => reject(error)
      })
    },

    // 图片预览
    async handlePreview(file) {
      if (!file.url && !file.preview) {
        file.preview = await this.getBase64(file.originFileObj)
      }
      this.previewImage = file.url || file.preview
      this.previewVisible = true
    },
    // 关闭预览图片遮罩层
    handleCancel() {
      this.previewVisible = false
    },

    beforeUpload(file) {
      console.log('上传图片状态', file)

      const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png'
      if (!isJpgOrPng) {
        this.$message.error('You can only upload JPG file!')
      }
      const isLt2M = file.size / 1024 / 1024 < 2
      if (!isLt2M) {
        this.$message.error('Image must smaller than 2MB!')
      }
      return isJpgOrPng && isLt2M
    },
    handleChange(info) {
      let fileList = [...info.fileList]

      // 1. Limit the number of uploaded files
      //    Only to show two recent uploaded files, and old ones will be replaced by the new
      fileList = fileList.slice(-1)
      console.log(fileList)
      this.ModifyImg = true
      this.fileList = fileList
    },
    // 可以把 onChange 的参数转化为控件的值
    normFile(e) {
      console.log(e)
      if (Array.isArray(e)) {
        return e
      }
      return e && e.fileList
    }
  }
}
</script>
<style scoped>
/* tile uploaded pictures */
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
</style>

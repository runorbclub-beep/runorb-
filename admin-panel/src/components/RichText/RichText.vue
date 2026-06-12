<template>
  <div>
    <quill-editor
      :content="content"
      :options="editorOption"
      @change="editorChange"
      ref="newEditor"
    > </quill-editor>
  </div>
</template>

<script>
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import 'quill/dist/quill.core.css'
import 'quill/dist/quill.snow.css'
import 'quill/dist/quill.bubble.css'
import { quillEditor, Quill } from 'vue-quill-editor'
import { container, ImageExtend, QuillWatch } from 'quill-image-extend-module'
Quill.register('modules/ImageExtend', ImageExtend)
export default {
  name: 'CourseRichText',
  props: ['content'],
  components: { quillEditor },
  data() {
    return {
      token: storage.get(ACCESS_TOKEN),
      editorOption: {
        modules: {
          ImageExtend: {
            loading: true,
            name: 'file',
            size: 1,
            action: process.env.VUE_APP_API_BASE_URL + '/match/upload',
            response: res => {
              console.log(res)
              return res.data.matchs_img_path
            },
            headers: (xhr, formData) => {
              xhr.setRequestHeader('token', this.token)
            }, // 可选参数 设置请求头部
            start: e => {
              console.log('开始上传', e)
            }, // 可选参数 自定义开始上传触发事件
            end: () => {}, // 可选参数 自定义上传结束触发的事件，无论成功或者失败
            error: () => {
              console.log('上传失败')
            }, // 可选参数 上传失败触发的事件
            success: e => {
              console.log('上传成功', e)
            }, // 可选参数  上传成功触发的事件
            sizeError: () => {
              return this.$message.error('图片超过1M')
            } // 图片超过大小的回调
          },
          toolbar: {
            container: [
              ['bold', 'italic', 'underline', 'strike'], // 加粗，斜体，下划线，删除线
              ['blockquote', 'code-block'], // 引用，代码块
              [{ header: 1 }, { header: 2 }], // 几级标题
              [{ list: 'ordered' }, { list: 'bullet' }], // 有序列表，无序列表
              [{ script: 'sub' }, { script: 'super' }], // 下角标，上角标
              [{ indent: '-1' }, { indent: '+1' }], // 缩进
              [{ direction: 'rtl' }], // 文字输入方向
              [{ size: ['small', false, 'large', 'huge'] }], // 字体大小
              [{ header: [1, 2, 3, 4, 5, 6, false] }], // 标题
              [{ color: [] }, { background: [] }], // 颜色选择
              [{ font: [] }], // 字体
              [{ align: [] }], // 居中
              ['link', 'image'],
              ['clean']
            ],
            handlers: {
              image() {
                console.log(this.quill.id)
                QuillWatch.emit(this.quill.id) // 劫持原来的图片点击按钮事件
              }
            }
          }
        }
      }
    }
  },
  methods: {
    editorChange({ editor, html, text }) {
      this.$emit('editorChange', html)
    }
  },
  watch: {
    // 监控富文本内的变化
    text(val) {
      this.editorChange = val
    }
  },
  mounted() {
    console.log(this.content)
  }
}
</script>

<style scoped></style>

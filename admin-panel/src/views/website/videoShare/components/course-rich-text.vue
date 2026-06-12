<template>
  <div>
    <quill-editor :content="content" :options="newOption" @change="editorChange" ref="newEditor"> </quill-editor>
  </div>
</template>

<script>
import 'quill/dist/quill.core.css'
import 'quill/dist/quill.snow.css'
import 'quill/dist/quill.bubble.css'
import { quillEditor } from 'vue-quill-editor'
export default {
  name: 'CourseRichText',
  props: ['content'],
  components: { quillEditor },
  data() {
    return {
      newOption: {
        placeholder: '请填写内容',
        history: {
          delay: 100,
          maxStack: 100,
          userOnly: false
        },
        modules: {
          toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ header: 1 }, { header: 2 }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ script: 'sub' }, { script: 'super' }],
            [{ indent: '-1' }, { indent: '+1' }],
            [{ direction: 'rtl' }],
            [{ size: ['small', false, 'large', 'huge'] }],
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ font: [] }],
            [{ color: [] }, { background: [] }],
            [{ align: [] }],
            ['clean'],
            ['link', 'image', 'video']
          ]
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
    text: function(val) {
      this.editorChange = val
    }
  },
  mounted() {
    console.log(this.content)
  }
}
</script>

<style scoped></style>

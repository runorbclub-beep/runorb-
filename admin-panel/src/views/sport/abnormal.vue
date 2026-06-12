<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="table-operator">
        <a-button type="primary" icon="plus" @click="handleAdd">新建</a-button>
      </div>
      <a-list :grid="{ gutter: 16, xs: 1, sm: 2, md: 4, lg: 4, xl: 4 }" :data-source="data">
        <a-list-item slot="renderItem" slot-scope="item, index">
          <a-card>
            <template slot="actions" class="ant-card-actions">
              <a-button type="link" icon="edit" @click="handleEdit(item, item.id)" />
              <a-popconfirm
                :title="$t('match.stage.delete')"
                :ok-text="$t('global.yes')"
                :cancel-text="$t('global.no')"
                @confirm="onDelete(item.id)"
              >
                <a-button type="link" icon="delete" />
              </a-popconfirm>
            </template>
            <a-form :ref="item.id" :form="$form.createForm(this, { name: item.id })" v-bind="formLayout">
              <a-form-item label="id" labelAlign="left" v-show="false">
                <a-row>
                  <a-col :md="20">
                    <span
                      v-decorator="[
                        'id',
                        {
                          initialValue: item.id,
                        },
                      ]"
                    >{{ item.id }}</span
                    >
                  </a-col>
                </a-row>
              </a-form-item>
              <a-form-item label="秒数" labelAlign="left">
                <a-row>
                  <a-col :md="20">
                    <span
                      v-decorator="[
                        'time',
                        {
                          initialValue: item.time,
                        },
                      ]"
                    >{{ item.time }}秒</span
                    >
                  </a-col>
                </a-row>
              </a-form-item>
              <a-form-item label="最高转速" labelAlign="left">
                <a-row>
                  <a-col :md="20">
                    <span
                      v-decorator="[
                        'max_speed',
                        {
                          initialValue: item.max_speed,
                        },
                      ]"
                    >{{ item.max_speed }}</span
                    >
                  </a-col>
                </a-row>
              </a-form-item>
            </a-form>
          </a-card>
        </a-list-item>
      </a-list>
      <create-form
        ref="createModal"
        :visible="visible"
        :loading="confirmLoading"
        :model="mdl"
        @cancel="handleCancel"
        @ok="handleOk"
      />
    </a-card>
  </page-header-wrapper>
</template>
<script>
import CreateForm from './modules/CreateForm'
import { getAbnormalList, updateAbnormalList } from '@/api/sport'

export default {
  name: 'Abnormal',
  components: {
    CreateForm
  },
  data() {
    return {
      visible: false,
      confirmLoading: false,
      mdl: null,
      data: [],
      formLayout: {
        labelCol: {
          xs: { span: 24 },
          sm: { span: 6 }
        },
        wrapperCol: {
          xs: { span: 24 },
          sm: { span: 8 }
        }
      }
    }
  },
  created() {
    this.initData()
  },
  methods: {
    initData() {
      getAbnormalList().then((res) => {
        console.log(res)
        this.data = res.data
      })
    },
    // 团队新增
    handleAdd() {
      this.mdl = null
      this.visible = true
    },
    // 团队编辑
    handleEdit(item, id) {
      console.log(item)
      this.visible = true
      console.log(this.$refs[id])
      const record = this.$refs[id].form.getFieldsValue()
      console.log(record)
      this.mdl = { ...record }
    },

    handleOk(isEdit) {
      const form = this.$refs.createModal.form
      const data = this.data
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values====', values)
          console.log('是否编辑===', isEdit)

          if (isEdit) {
            console.log('编辑')
            for (let i = 0; i < data.length; i++) {
              if (data[i].id === values.id) {
                data[i].time = values.time
                data[i].max_speed = values.max_speed
                break
              }
            }
            console.log(data)
            const params = {
              play_abnormal: data
            }
            updateAbnormalList(params)
              .then((res) => {
                this.visible = false
                this.confirmLoading = false
                // 重置表单数据
                form.resetFields()
                // 刷新表格
                this.$message.info('修改成功')
              })
              .catch((err) => {
                console.log(err)
              })
          } else {
            // 新增
            console.log('新增')
            data.push(values)
            const params = {
              play_abnormal: data
            }
            updateAbnormalList(params).then((res) => {
              console.log(res)
              this.visible = false
              this.confirmLoading = false
              // 重置表单数据
              form.resetFields()
              // 刷新表格
              this.$message.info('新增成功')
            })
          }
        } else {
          this.confirmLoading = false
        }
      })
    },

    onDelete(id) {
      console.log(id)
      const data = this.data
      for (let i = 0; i < data.length; i++) {
        if (data[i].id === id) {
          data.splice(i, 1)
          break
        }
      }
      console.log(data)
      const params = {
        play_abnormal: data
      }
      updateAbnormalList(params).then((res) => {
        console.log(res)

        // 刷新表格
        this.initData()
        this.$message.info(res.msg)
      })
    },
    handleCancel() {
      this.visible = false

      const form = this.$refs.createModal.form
      form.resetFields() // 清理表单数据（可不做）
    }
  }
}
</script>
<style></style>

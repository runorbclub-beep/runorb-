<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="table-operator">

      </div>

      <s-table
        ref="table"
        size="default"
        rowKey="sys_ranking_type_id"
        :columns="columns"
        :data="loadData"
        :alert="false"
        :pagination="pagination"
      >
        <span
          slot="operation"
          slot-scope="text, record"
        >
          <template>
            <a @click="handleEdit(record)">编辑</a>

          </template>
        </span>

      </s-table>
    </a-card>
    <leaderboard-form
      ref="createModal"
      :visible="visible"
      :loading="confirmLoading"
      :model="mdl"
      :data="data"
      @cancel="handleCancel"
      @ok="handleOk"
    />
  </page-header-wrapper>
</template>

<script>
import { STable, Ellipsis } from '@/components'
import LeaderboardForm from './modules/LeaderboardForm'
import { getRankingList, updateRankingList } from '@/api/system'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '榜单序号',
    dataIndex: 'ranking_index'
  },

  {
    title: '中文名称',
    dataIndex: 'ranking_title_zh'
  },
  {
    title: '英文名称',
    dataIndex: 'ranking_title_en'
  },
  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'Leaderboard',
  components: {
    STable,
    LeaderboardForm,
    Ellipsis
  },
  data() {
    this.columns = columns
    return {
      pagination,
      data: [],
      // create model
      visible: false,
      confirmLoading: false,
      mdl: null,
      // 高级搜索 展开/关闭
      advanced: false,
      // 查询参数
      queryParam: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: parameter => {
        const requestParameters = Object.assign({}, parameter, this.queryParam)
        // console.log('loadData request parameters:', requestParameters)
        return getRankingList(requestParameters).then(res => {
          console.log(res)
          this.data = res.data.list
          return Object.assign(res.data, parameter)
        })
      }
    }
  },
  filters: {},
  created() {},
  computed: {},

  methods: {
    handleEdit(record) {
      this.visible = true
      this.mdl = { ...record }
    },
    handleCancel() {
      this.visible = false

      const form = this.$refs.createModal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    handleOk() {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          // console.log(this.data)
          for (let i = 0; i < this.data.length; i++) {
            if (
              this.data[i].sys_ranking_type_id !== values.sys_ranking_type_id &&
              this.data[i].ranking_index === values.ranking_index
            ) {
              form.setFields({
                ranking_index: {
                  value: undefined,
                  errors: [Error('序号已存在')]
                }
              })
              this.confirmLoading = false
              return
            }
          }

          const params = {
            sys_ranking_type_id: values.sys_ranking_type_id,
            ranking_index: values.ranking_index,
            ranking_title_en: values.ranking_title_en,
            ranking_title_zh: values.ranking_title_zh,
            ranking_rule_zh: values.ranking_rule_zh,
            ranking_rule_en: values.ranking_rule_en
          }
          updateRankingList(params)
            .then(res => {
              if (res.code === 1) {
                this.visible = false
                this.confirmLoading = false
                // 重置表单数据
                form.resetFields()
                // 刷新表格
                this.$refs.table.refresh()

                this.$message.success(res.msg)
              } else {
                this.$message.error(res.msg)
              }
            })
            .catch(err => {
              console.log(err)
            })
        } else {
          this.confirmLoading = false
        }
      })
    }
  }
}
</script>

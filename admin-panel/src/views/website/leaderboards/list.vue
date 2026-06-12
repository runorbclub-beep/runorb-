<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="table-page-search-wrapper">
        <a-form layout="inline">
          <a-row :gutter="48">
            <a-col
              :md="8"
              :sm="24"
            >
              <a-form-item label="榜单类型">
                <a-select
                  v-model="queryParam.rank_type"
                  allowClear
                  @change="handleChange"
                >
                  <a-select-option
                    :value="item.ranking_type"
                    v-for=" item of leaderboardType"
                    :key="item.sys_ranking_type_id"
                  >
                    {{ item.ranking_title_zh }}
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
          @click="handleAdd"
        >新建</a-button>

      </div>

      <s-table
        ref="table"
        size="default"
        rowKey="web_match_ranking_id"
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
            <a-divider type="vertical" />
            <a-popconfirm
              v-if="loadData.length"
              title="确认删除?"
              @confirm="() => onDelete(record.web_match_ranking_id)"
            >
              <a href="javascript:;">删除</a>
            </a-popconfirm>
            <a-divider type="vertical" />
            <a @click="handleTeam(record)">用户列表</a>
            <a-divider type="vertical" />
            <a @click="handleMember(record)">新增用户</a>

            <!-- <a-dropdown>
              <a
                class="ant-dropdown-link"
                @click="(e) => e.preventDefault()"
              > 用户编辑
                <a-icon type="down" />
              </a>
              <a-menu slot="overlay">
                <a-menu-item>
                  <a @click="handleTeam(record)">查看用户</a>
                </a-menu-item>
                <a-menu-item>
                  <a @click="handleMember(record)">添加用户</a>
                </a-menu-item>
              </a-menu>
            </a-dropdown> -->
          </template>
        </span>
      </s-table>

      <create-form
        ref="createModal"
        :visible="visible"
        :loading="confirmLoading"
        :model="mdl"
        :leaderboardType="leaderboardType"
        @cancel="handleCancel"
        @ok="handleOk"
      />
      <member-form
        ref="memberModal"
        :visible="visibleMember"
        :loading="memberConfirmLoading"
        :model="membermdl"
        @cancel="handleCancelMember"
        @ok="handleOkMember"
      />
      <team-form
        ref="teamModal"
        :visible="visibleTeam"
        :loading="teamConfirmLoading"
        :model="teammdl"
        :leaderboardType="leaderboardType"
        @cancel="handleCancelTeam"
        @ok="handleOkTeam"
      />
    </a-card>
  </page-header-wrapper>
</template>

<script>
import moment from 'moment'
import { STable, Ellipsis } from '@/components'
import {
  getLeaderboardsList,
  addLeaderboards,
  deleteLeaderboards,
  addleaderboardsListUser
} from '@/api/website'
import { getRankingList } from '@/api/system'

import CreateForm from './modules/CreateForm'
import MemberForm from './modules/MemberForm'
import TeamForm from './modules/TeamForm'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '榜单ID',
    dataIndex: 'web_match_ranking_id'
  },
  {
    title: '榜单标题',
    dataIndex: 'match_ranking_title'
  },
  {
    title: '榜单类型',
    dataIndex: 'ranking_title.title_zh'
  },
  {
    title: '榜单周期',
    dataIndex: 'ranking_time_title'
  },

  {
    title: '用户数(人)',
    dataIndex: 'user_count'
  },
  {
    title: '开始时间',
    dataIndex: 'start_date'
  },
  {
    title: '结束时间',
    dataIndex: 'stop_date'
  },
  // {
  //   title: '榜单类型',
  //   dataIndex: 'ranking_type',
  //   scopedSlots: { customRender: 'group_property' }
  // },
  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'Leaderboards',
  components: {
    STable,
    Ellipsis,
    CreateForm,
    MemberForm,
    TeamForm
  },
  data() {
    this.columns = columns
    return {
      pagination,
      // create model 团队编辑/新增
      visible: false,
      confirmLoading: false,
      mdl: null,
      // 添加成员表单
      visibleMember: false,
      memberConfirmLoading: false,
      membermdl: null,
      // 团队表单
      visibleTeam: false,
      teamConfirmLoading: false,
      teammdl: null,
      // 高级搜索 展开/关闭
      advanced: false,
      // 查询参数
      queryParam: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: parameter => {
        console.log(parameter)
        console.log(Object.assign)
        const requestParameters = Object.assign({}, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        return getLeaderboardsList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      selectedRowKeys: [],
      selectedRows: [],
      brandDict: [],
      categoryDict: [],
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      rankingId: '',
      rankingType: '',
      value: '',
      joinTime: '',
      leaderboardType: []
    }
  },
  filters: {},
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
    getRankingList().then(res => {
      console.log(res)
      this.leaderboardType = res.data.list
      console.log(this.leaderboardType)
    })
  },
  computed: {
    rowSelection() {
      return {
        selectedRowKeys: this.selectedRowKeys,
        onChange: this.onSelectChange
      }
    }
  },

  methods: {
    handleChange(value) {
      console.log(`selected ${value}`)
      this.$refs.table.refresh(true)
    },
    // 团队新增
    handleAdd() {
      this.mdl = null
      this.visible = true
    },
    // 团队编辑
    handleEdit(record) {
      this.visible = true
      this.mdl = { ...record }
    },
    // 团队添加成员
    handleMember(record) {
      console.log(record)
      this.visibleMember = true
      console.log(this.visibleMember)
      this.rankingId = record.web_match_ranking_id
      this.membermdl = { ...record }
    },
    // 查看团队成员及删除团队成员
    handleTeam(record) {
      console.log(record)
      this.visibleTeam = true
      console.log(this.visibleTeam)
      this.rankingId = record.web_match_ranking_id
      this.rankingType = record.ranking_type
      this.value = record.web_match_ranking_id
      this.joinTime = record.web_match_ranking_id
      this.teammdl = { ...record }
    },
    handleOk() {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const requestParameters = {
            ranking_title: values.match_ranking_title,
            ranking_title_en: values.ranking_title_en,
            ranking_type: values.ranking_type,
            ranking_time_type: values.ranking_time_type,
            start_time: moment(values['start_time']).format('YYYY-MM-DD'),
            stop_time: moment(values['stop_time']).format('YYYY-MM-DD')
          }
          console.log('requestParameters=====', requestParameters)
          if (values.web_match_ranking_id > 0) {
            const obj = { web_match_ranking_id: values.web_match_ranking_id }
            console.log('编辑')
            addLeaderboards({ ...requestParameters, ...obj })
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
            // 新增
            addLeaderboards(requestParameters).then(res => {
              if (res.code === 1) {
                console.log(res)
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
          }
        } else {
          this.confirmLoading = false
        }
      })
    },
    handleOkMember(id) {
      console.log('用户ID', id)
      const form = this.$refs.memberModal.form
      this.memberConfirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const requestParameters = {
            user_id: id,
            web_match_ranking_id: this.rankingId,
            ranking_type: this.rankingType,
            value: this.value,
            join_time: this.joinTime
          }
          console.log('requestParameters=====', requestParameters)

          // 新增
          addleaderboardsListUser(requestParameters).then(res => {
            console.log(res)
            this.visibleMember = false
            this.memberConfirmLoading = false
            // 重置表单数据
            form.resetFields()
            // 刷新表格
            this.$refs.table.refresh()
            this.$message.info('添加成功')
          })
        } else {
          this.memberConfirmLoading = false
        }
      })
    },
    handleOkTeam(id) {
      console.log('用户ID', id)
      const form = this.$refs.teamModal.form
      this.teamConfirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          // const requestParameters = {
          //   user_id: id,
          //   web_match_ranking_id: this.rankingId,
          // }
          // console.log('requestParameters=====', requestParameters)

          // // 新增
          // addleaderboardsListUser(requestParameters).then((res) => {
          //   console.log(res)
          //   this.visibleMember = false
          //   this.memberConfirmLoading = false
          //   // 重置表单数据
          //   form.resetFields()
          //   // 刷新表格
          //   this.$refs.table.refresh()
          //   this.$message.info('添加成功')
          // })
        } else {
          this.teamConfirmLoading = false
        }
      })
    },
    onDelete(key) {
      console.log(key)
      const requestParameters = {
        web_match_ranking_id: key
      }
      deleteLeaderboards(requestParameters).then(res => {
        console.log(res)

        // 刷新表格
        this.$refs.table.refresh()

        this.$message.info(res.msg)
      })
    },
    handleCancel() {
      this.visible = false

      const form = this.$refs.createModal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    handleCancelMember() {
      this.visibleMember = false

      const form = this.$refs.memberModal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    handleCancelTeam() {
      this.visibleTeam = false

      const form = this.$refs.teamModal.form
      form.resetFields() // 清理表单数据（可不做）
    },

    onSelectChange(selectedRowKeys, selectedRows) {
      this.selectedRowKeys = selectedRowKeys
      this.selectedRows = selectedRows
    },
    toggleAdvanced() {
      this.advanced = !this.advanced
    },
    resetSearchForm() {
      this.queryParam = {
        date: moment(new Date())
      }
    }
  }
}
</script>

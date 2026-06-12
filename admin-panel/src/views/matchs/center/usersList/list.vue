<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <!-- <div class="table-page-search-wrapper">
        <a-form layout="inline">
          <a-row :gutter="48">
            <a-col :md="8" :sm="24">
              <a-form-item label="关键字">
                <a-input v-model="queryParam.search" placeholder="团队标题/团队编号" />
              </a-form-item>
            </a-col>
            <a-col :md="(!advanced && 8) || 24" :sm="24">
              <span
                class="table-page-search-submitButtons"
                :style="(advanced && { float: 'right', overflow: 'hidden' }) || {}"
              >
                <a-button type="primary" @click="$refs.table.refresh(true)">查询</a-button>
                <a-button style="margin-left: 8px" @click="() => (this.queryParam = {})">重置</a-button>
              </span>
            </a-col>
          </a-row>
        </a-form>
      </div> -->
      <div class="table-operator">
        <a-button
          :disabled="matchsStageStatus === 3 || teamTag.length === 0"
          type="primary"
          icon="plus"
          @click="handleAdd"
        >新建</a-button>
        <a-button
          :disabled="matchsStageStatus === 3"
          type="primary"
          icon="plus"
          @click="handleAddGroup"
        >新建组队</a-button>
      </div>
      <s-table
        ref="table"
        size="default"
        rowKey="matchs_user_id"
        :columns="columns"
        :data="loadData"
        :alert="false"
        showPagination="auto"
      >
        <span
          slot="operation"
          slot-scope="text, record"
        >
          <template>
            <a @click="handleData(record)">运动数据</a>
          </template>
        </span>
        <span
          slot="group_property"
          slot-scope="text"
        >
          {{ text === 0 ? '个人' : '团队' }}
        </span>
        <span
          slot="img"
          slot-scope="text, record"
        >
          <a-avatar
            :size="44"
            :src=" record.img"
          />
        </span>
      </s-table>

      <creat-form
        ref="modal"
        :visible="visibleTeam"
        :loading="teamConfirmLoading"
        :model="teammdl"
        @cancel="handleCancelTeam"
        @ok="handleOkTeam"
      />
      <user-form
        ref="userModal"
        :visible="visible"
        :loading="ConfirmLoading"
        :model="mdl"
        :teamTag="teamTag"
        @cancel="handleCancel"
        @ok="handleOk"
      />
      <group-form
        ref="groupModal"
        :visible="visibleGroup"
        :loading="ConfirmLoadingGroup"
        :model="mdlGroup"
        @cancel="handleCancelGroup"
        @ok="handleOkGroup"
      />
    </a-card>
  </page-header-wrapper>
</template>

<script>
import { STable, Ellipsis } from '@/components'
import {
  matchsStageUsersList,
  addMatchUserSign,
  teamTag,
  addTeamTag
} from '@/api/matchs'

import CreatForm from './modules/CreatForm'
import UserForm from './modules/UserForm'
import GroupForm from './modules/GroupForm'
const userColumns = [
  {
    title: '用户报名ID',
    dataIndex: 'matchs_user_id'
  },
  {
    title: '用户ID',
    dataIndex: 'user_id'
  },
  {
    title: '赛段ID',
    dataIndex: 'matchs_stage_id'
  },
  {
    title: '用户名',
    dataIndex: 'name'
  },

  {
    title: 'logo',
    dataIndex: 'img',
    scopedSlots: { customRender: 'img' }
  },
  {
    title: '当前用时',
    dataIndex: 'match_grade'
  },
  {
    title: '当前排名',
    dataIndex: 'match_ranking'
  },

  {
    title: '参赛主体',
    dataIndex: 'is_group',
    scopedSlots: { customRender: 'group_property' }
  },
  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]
const teamColumns = [
  {
    title: '用户ID',
    dataIndex: 'matchs_user_id'
  },
  {
    title: '组队名',
    dataIndex: 'team_tag'
  },
  // {
  //   title: '团队ID',
  //   dataIndex: 'user_group_id'
  // },
  // {
  //   title: '赛段ID',
  //   dataIndex: 'matchs_stage_id'
  // },
  {
    title: '用户昵称',
    dataIndex: 'name'
  },

  {
    title: 'logo',
    dataIndex: 'img',
    scopedSlots: { customRender: 'img' }
  },
  // {
  //   title: '当前用时',
  //   dataIndex: 'match_grade'
  // },
  // {
  //   title: '当前排名',
  //   dataIndex: 'match_ranking'
  // },

  // {
  //   title: '参赛主体',
  //   dataIndex: 'is_group',
  //   scopedSlots: { customRender: 'group_property' }
  // },
  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]
export default {
  name: 'MatchsUsersList',
  components: {
    STable,
    Ellipsis,
    CreatForm,
    UserForm,
    GroupForm
  },
  data() {
    const that = this
    this.columns = that.isGroup === 0 ? userColumns : teamColumns
    return {
      // create model 团队编辑/新增
      visible: false,
      ConfirmLoading: false,
      mdl: null,
      // 添加成员表单
      visibleGroup: false,
      ConfirmLoadingGroup: false,
      mdlGroup: null,
      // 团队表单
      visibleTeam: false,
      teamConfirmLoading: false,
      teammdl: null,
      // 高级搜索 展开/关闭
      advanced: false,
      // 查询参数
      queryParam: {},
      matchId: '',
      stageId: '',
      isGroup: null,
      // 加载数据方法 必须为 Promise 对象
      loadData: parameter => {
        console.log(parameter)
        const requestParameters = Object.assign(
          {
            is_group: this.isGroup,
            sys_match_id: this.matchId,
            matchs_stage_id: this.stageId
          },
          parameter,
          this.queryParam
        )
        console.log('loadData request parameters:', requestParameters)
        return matchsStageUsersList(requestParameters).then(res => {
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
      userGroupId: '',
      sysMatchId: '',
      sysSysMatchId: '',
      teamTag: [],
      matchsStageStatus: ''
    }
  },
  filters: {},
  created() {
    this.matchsStageStatus = this.$route.params.matchsStageStatus
    this.sysMatchId = this.$route.params.sysMatchId
    this.sysSysMatchId = this.$route.params.sysSysMatchId
    this.matchId = this.$route.params.matchId
    this.stageId = this.$route.params.stageId
    this.isGroup = this.$route.params.isGroup
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
    this.getTeamTag(this.sysSysMatchId)
  },
  computed: {},
  methods: {
    getTeamTag(id) {
      const requestParameters = {
        sys_sys_match_id: id
      }
      teamTag(requestParameters).then(res => {
        console.log(res)
        if (res.code === 1) {
          this.teamTag = res.data
        }
      })
    },
    // 用户新增
    handleAdd() {
      this.mdl = null
      this.visible = true
    },
    handleCancel() {
      this.visible = false
      const form = this.$refs.userModal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    handleCancelTeam() {
      this.visibleTeam = false
      const form = this.$refs.modal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    // 查看团队成员及删除团队成员
    handleData(record) {
      console.log(record)
      this.visibleTeam = true
      console.log(this.visibleTeam)
      const params = {
        isGroup: this.isGroup
      }
      this.teammdl = { ...record, ...params }
    },
    handleOk() {
      const form = this.$refs.userModal.form
      this.ConfirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const requestParameters = {
            sys_match_id: this.sysMatchId,
            sys_sys_match_id: this.sysSysMatchId,
            is_quartets: '1',
            team_tag: values.team_tag,
            phone: values.phone
          }
          console.log('requestParameters=====', requestParameters)

          // 新增
          addMatchUserSign(requestParameters).then(res => {
            console.log(res)
            if (res.code === 1) {
              this.visible = false

              // 重置表单数据
              form.resetFields()
              // 刷新表格
              this.$refs.table.refresh()
              this.$message.info('添加成功')
            } else {
              this.$message.warn(res.msg)
            }
            this.ConfirmLoading = false
          })
        } else {
          this.ConfirmLoading = false
        }
      })
    },
    handleOkTeam(id) {
      console.log('用户ID', id)
      const form = this.$refs.modal.form
      this.teamConfirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          // const requestParameters = {
          //   user_id: id,
          //   matchs_user_id: this.userGroupId,
          // }
          // console.log('requestParameters=====', requestParameters)

          // // 新增
          // addGroupUser(requestParameters).then((res) => {
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
    // 组队新增
    handleAddGroup() {
      this.mdlGroup = null
      this.visibleGroup = true
    },
    handleOkGroup(id) {
      console.log('用户ID', id)
      const form = this.$refs.groupModal.form
      this.ConfirmLoadingGroup = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const requestParameters = {
            sys_sys_match_id: this.sysSysMatchId,
            team_tag: values.team_tag
          }
          console.log('requestParameters=====', requestParameters)

          // 新增
          addTeamTag(requestParameters).then(res => {
            console.log(res)
            this.visibleGroup = false
            this.ConfirmLoadingGroup = false
            // 重置表单数据
            form.resetFields()
            this.getTeamTag(this.sysSysMatchId)
            this.$message.info('添加成功')
          })
        } else {
          this.ConfirmLoadingGroup = false
        }
      })
    },

    handleCancelGroup() {
      this.visibleGroup = false

      const form = this.$refs.groupModal.form
      form.resetFields() // 清理表单数据（可不做）
    },

    onSelectChange(selectedRowKeys, selectedRows) {
      this.selectedRowKeys = selectedRowKeys
      this.selectedRows = selectedRows
    }
  }
}
</script>

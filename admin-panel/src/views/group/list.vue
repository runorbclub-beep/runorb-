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
              <a-form-item label="关键字">
                <a-input
                  v-model="queryParam.search"
                  placeholder="团队标题/团队编号"
                />
              </a-form-item>
            </a-col>
            <a-col
              :md="(!advanced && 8) || 24"
              :sm="24"
            >
              <span
                class="table-page-search-submitButtons"
                :style="(advanced && { float: 'right', overflow: 'hidden' }) || {}"
              >
                <a-button
                  type="primary"
                  @click="$refs.table.refresh(true)"
                >查询</a-button>
                <a-button
                  style="margin-left: 8px"
                  @click="resetTable"
                >重置</a-button>
              </span>
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
        <a-dropdown
          v-action:edit
          v-if="selectedRowKeys.length > 0"
        >
          <a-menu slot="overlay">
            <a-menu-item key="1">
              <a-icon type="delete" />删除
            </a-menu-item>
          </a-menu>
          <a-button style="margin-left: 8px"> 批量操作
            <a-icon type="down" />
          </a-button>
        </a-dropdown>
      </div>

      <s-table
        ref="table"
        size="default"
        rowKey="user_group_id"
        :columns="columns"
        :data="loadData"
        :alert="false"
        :pagination="pagination"
      >
        <!-- <span slot="serial" slot-scope="text, record, index">
          {{ index + 1 }}
        </span>
        <span slot="status" slot-scope="text">
          <a-badge :status="text | statusTypeFilter" :text="text | statusFilter" />
        </span>
        <span slot="description" slot-scope="text">
          <ellipsis :length="4" tooltip>{{ text }}</ellipsis>
        </span> -->

        <span
          slot="operation"
          slot-scope="text, record"
        >
          <template>
            <a @click="handleEdit(record)">编辑团队</a>
            <a-divider type="vertical" />
            <!-- <a-popconfirm v-if="loadData.length" title="确认删除?" @confirm="() => onDelete(record.user_group_id)">
              <a href="javascript:;">删除</a>
            </a-popconfirm>
            <a-divider type="vertical" /> -->
            <a @click="handleTeam(record)">查看成员</a>
            <a-divider type="vertical" />
            <a @click="handleMember(record)">添加成员</a>
            <!-- <a-dropdown>
              <a
                class="ant-dropdown-link"
                @click="(e) => e.preventDefault()"
              > 成员编辑
                <a-icon type="down" />
              </a>
              <a-menu slot="overlay">
                <a-menu-item>
                  <a @click="handleTeam(record)">查看成员</a>
                </a-menu-item>
                <a-menu-item>
                  <a @click="handleMember(record)">添加成员</a>
                </a-menu-item>
              </a-menu>
            </a-dropdown> -->
          </template>
        </span>
        <span
          slot="group_property"
          slot-scope="text"
        >
          {{ text === '1' ? '官方' : '非官方' }}
        </span>

        <span
          slot="group_logo"
          slot-scope="text, record"
        >
          <a-avatar
            :size="44"
            icon="user"
            :src=" record.group_logo"
          />
        </span>
      </s-table>

      <create-form
        ref="createModal"
        :visible="visible"
        :loading="confirmLoading"
        :model="mdl"
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
        @cancel="handleCancelTeam"
        @ok="handleOkTeam"
      />
    </a-card>
  </page-header-wrapper>
</template>

<script>
import moment from 'moment'
import { STable, Ellipsis } from '@/components'
import { getGroupList, addGroup, addGroupUser } from '@/api/group'

import CreateForm from './modules/CreateForm'
import MemberForm from './modules/MemberForm'
import TeamForm from './modules/TeamForm'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '用户团队ID',
    dataIndex: 'user_group_id'
  },
  {
    title: '团队编号',
    dataIndex: 'group_num'
  },
  {
    title: '团队名称',
    dataIndex: 'group_title'
  },

  {
    title: '团队logo',
    dataIndex: 'group_logo',
    scopedSlots: { customRender: 'group_logo' }
  },
  // {
  //   title: '团队描述',
  //   dataIndex: 'official_description'
  // },
  {
    title: '团队用户数',
    dataIndex: 'group_user_num'
  },

  {
    title: '团队性质',
    dataIndex: 'is_official',
    scopedSlots: { customRender: 'group_property' }
  },
  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'ProductList',
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
        const requestParameters = Object.assign({}, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        return getGroupList(requestParameters).then(res => {
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
      userGroupId: ''
    }
  },
  filters: {},
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
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
    resetTable() {
      this.queryParam = {}
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
      this.userGroupId = record.user_group_id
      this.membermdl = { ...record }
    },
    // 查看团队成员及删除团队成员
    handleTeam(record) {
      console.log(record)
      this.visibleTeam = true
      console.log(this.visibleTeam)
      this.userGroupId = record.user_group_id
      this.teammdl = { ...record }
    },
    handleOk(ModifyImg) {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          console.log('ModifyImg', ModifyImg)
          const requestParameters = {
            group_title: values.group_title,
            group_num: values.group_num,
            group_logo:
              values.user_group_id > 0
                ? ModifyImg
                  ? values.group_logo[0].response.data.file_path.file_path
                  : values.group_logo
                : values.group_logo[0].response.data.file_path.file_path,
            official_description: values.official_description,
            is_official: values.is_official === '官方' ? 1 : 0
          }
          console.log('requestParameters=====', requestParameters)
          if (values.user_group_id > 0) {
            const obj = { user_group_id: values.user_group_id }
            console.log('编辑')
            addGroup({ ...requestParameters, ...obj })
              .then(res => {
                this.visible = false
                this.confirmLoading = false
                // 重置表单数据
                form.resetFields()
                // 刷新表格
                this.$refs.table.refresh()

                this.$message.info('修改成功')
              })
              .catch(err => {
                console.log(err)
              })
          } else {
            // 新增
            addGroup(requestParameters).then(res => {
              console.log(res)
              this.visible = false
              this.confirmLoading = false
              // 重置表单数据
              form.resetFields()
              // 刷新表格
              this.$refs.table.refresh()

              this.$message.info('新增成功')
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
            user_group_id: this.userGroupId
          }
          console.log('requestParameters=====', requestParameters)

          // 新增
          addGroupUser(requestParameters).then(res => {
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
          //   user_group_id: this.userGroupId,
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
    onDelete(key) {
      console.log(key)
      const requestParameters = {
        user_group_id: key
      }
      deleteGoods(requestParameters).then(res => {
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

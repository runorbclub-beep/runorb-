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
                  placeholder="用户名/手机号"
                />
              </a-form-item>
            </a-col>
            <a-col
              :md="8"
              :sm="24"
            >
              <span class="table-page-search-submitButtons">
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
          <a-row :gutter="48">
            <a-col
              :md="8"
              :sm="24"
            >
              <a-form-item label="用户类型">
                <a-radio-group
                  default-value=""
                  button-style="solid"
                  v-model="queryParam.sys_user_type_id"
                  @change="onRadioChange"
                >
                  <a-radio-button
                    :value="item.value"
                    v-for="item of UserTypeList"
                    :key="item.value"
                  >
                    {{ item.label }}
                  </a-radio-button>
                </a-radio-group>
              </a-form-item>
            </a-col>
            <a-col
              :md="2"
              :sm="24"
            >
              <span
                class="table-page-search-submitButtons"
                :style="(advanced && { float: 'right', overflow: 'hidden' }) || {}"
              >
                <a-button
                  type="primary"
                  @click="resetTable"
                >重置</a-button>
              </span>
            </a-col>
          </a-row>
          <a-row :gutter="48">
            <a-col
              :md="8"
              :sm="24"
            >
              <a-form-item label="会员状态">
                <a-radio-group
                  :default-value="membersStatusDefaultValue"
                  button-style="solid"
                  v-model="queryParam.members_status"
                  @change="onRadioChange"
                >
                  <a-radio-button
                    :value="item.value"
                    v-for="item of membersStatus"
                    :key="item.value"
                  >
                    {{ item.name }}
                  </a-radio-button>
                </a-radio-group>
              </a-form-item>
            </a-col>
            <a-col
              :md="2"
              :sm="24"
            >
              <span
                class="table-page-search-submitButtons"
                :style="(advanced && { float: 'right', overflow: 'hidden' }) || {}"
              >
                <a-button
                  type="primary"
                  @click="resetTable"
                >重置</a-button>
              </span>
            </a-col>
          </a-row>
        </a-form>
      </div>

      <div class="table-operator">
        <!-- <a-button type="primary" icon="plus" @click="handleAdd">新建</a-button> -->
      </div>

      <s-table
        ref="table"
        size="default"
        rowKey="user_id"
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
            <span>
              <a @click="handleInfo(record.user_id)">详情</a>

            </span>
            <span v-if="record.members_status !== 1 && record.members_status !== 0">
              <a-divider type="vertical" />
              <a @click="handleMembersStatus(1,record.user_id)">设为会员</a>
            </span>
            <span v-if="record.members_status === 1">
              <a-divider type="vertical" />
              <a-popconfirm
                title="是否取消会员?"
                ok-text="确定"
                cancel-text="取消"
                @confirm="handleMembersStatus(3,record.user_id)"
              ><a href="#">取消会员</a>
              </a-popconfirm>
            </span>
            <span v-if="record.members_status === 0">
              <a-divider type="vertical" />
              <a @click="handleMembersStatus(1,record.user_id)">通过</a>
            </span>
            <span v-if="record.members_status === 0">
              <a-divider type="vertical" />
              <a-popconfirm
                title="是否驳回?"
                ok-text="确定"
                cancel-text="取消"
                @confirm="handleMembersStatus(2,record.user_id)"
              >
                <a href="#">驳回</a>
              </a-popconfirm>
            </span>

          </template>
        </span>
        <span
          slot="sex_name"
          slot-scope="text"
        >
          {{ text }}
        </span>
        <span
          slot="user_type_name"
          slot-scope="text"
        >
          {{ text }}
        </span>
        <span
          slot="is_members"
          slot-scope="text"
        >
          {{ text === 0?'非会员':'会员' }}
        </span>
        <span
          slot="members_status"
          slot-scope="text"
        >
          {{ text === 0?'待审核':text === 1 ?'已审核': text === 2?'已驳回':text === 3?'已过期':'未提交' }}
        </span>
        <span
          slot="user_img"
          slot-scope="text, record"
        >
          <a-avatar
            :size="44"
            icon="user"
            :src="record.user_img"
          />
        </span>
        <span
          slot="integral"
          slot-scope="text, record"
        >
          <div class="integral_wrap">
            <a-input-number
              class="integral_input"
              :min="0"
              :disabled="projectId === record.user_id ? disabled : true"
              :default-value="text"
              :precision="0.1"
              @change="onInputChange"
            />
            <div class="integral_btn">
              <a-button
                type="link"
                @click="toggle(record.user_id)"
              >
                <!-- {{ projectId === record.user_id && !disabled ? '保存' : '编辑' }} -->
                <a-icon
                  v-if="projectId === record.user_id && !disabled"
                  type="save"
                />
                <a-icon
                  v-else
                  type="edit"
                />
              </a-button>
            </div>
          </div>
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
import {
  getUserList,
  getUserTypeList,
  updateMembers,
  integral
} from '@/api/user'
import CreateForm from './modules/CreateForm'
import TeamForm from './modules/TeamForm'
/* 限制数字输入框只能输入整数 */
const limitNumber = value => {
  if (typeof value === 'string') {
    return !isNaN(Number(value)) ? value.replace(/^(0+)|[^\d]/g, '') : ''
  } else if (typeof value === 'number') {
    return !isNaN(value) ? String(value).replace(/^(0+)|[^\d]/g, '') : ''
  } else {
    return ''
  }
}
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const membersStatus = [
  {
    name: '待审核',
    value: 0
  },
  {
    name: '已审核',
    value: 1
  },
  {
    name: '已驳回',
    value: 2
  }
]
const columns = [
  {
    title: '用户ID',
    dataIndex: 'user_id'
  },
  {
    title: '用户名',
    dataIndex: 'user_name'
  },
  {
    title: '用户头像',
    dataIndex: 'user_img',
    align: 'center',
    scopedSlots: { customRender: 'user_img' }
  },
  {
    title: '性别',
    dataIndex: 'sex_name',
    scopedSlots: { customRender: 'sex_name' }
  },
  {
    title: '手机号码',
    dataIndex: 'phone'
  },

  // {
  //   title: '自我介绍',
  //   dataIndex: 'self_description'
  // },
  {
    title: '用户地址',
    dataIndex: 'address'
  },

  {
    title: '用户类型',
    dataIndex: 'user_type_name',
    scopedSlots: { customRender: 'user_type_name' }
  },
  {
    title: '会员类型',
    dataIndex: 'is_members',
    scopedSlots: { customRender: 'is_members' }
  },
  {
    title: '会员状态',
    dataIndex: 'members_status',
    scopedSlots: { customRender: 'members_status' }
  },
  {
    title: '渠道',
    dataIndex: 'channel'
  },
  {
    title: '设备信息',
    dataIndex: 'device_model'
  },
  {
    title: '版本号',
    dataIndex: 'version'
  },
  {
    title: '用户积分',
    dataIndex: 'integral',
    scopedSlots: { customRender: 'integral' }
  },
  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'UsersList',
  components: {
    STable,
    Ellipsis,
    CreateForm,
    TeamForm
  },
  data() {
    this.columns = columns
    return {
      name: 'UsersList',
      pagination,
      // create model
      visible: false,
      confirmLoading: false,
      mdl: null,
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
        const obj = {}
        if (this.membersStatusDefaultValue !== '') {
          obj.members_status = 0
        }
        const requestParameters = Object.assign(obj, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        return getUserList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      selectedRowKeys: [],
      selectedRows: [],
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: '',
      UserTypeList: [],
      userId: '',
      membersStatus,
      membersStatusDefaultValue: '',
      disabled: true,
      projectId: '',
      limitNumber,
      inputNumber: null
    }
  },
  filters: {},
  created() {
    console.log(this.$route.query.id)
    if (this.$route.query.id !== undefined) {
      this.membersStatusDefaultValue = 0
    }
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')
    getUserTypeList({}).then(res => {
      console.log('用户类型===', res)
      if (res.code === 1) {
        this.UserTypeList = res.data.list
      }
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
    onInputChange(value) {
      // console.log('输入框值', value)
      this.inputNumber = value
    },

    toggle(id) {
      // console.log(id)
      // console.log(this.projectId)
      if (this.projectId !== id) {
        this.disabled = true
        console.log('切换==', id)
      } else {
        if (!this.disabled) {
          console.log('改变值', this.inputNumber)
          console.log('保存提交==', id)

          const params = {
            user_id: id,
            integral: this.inputNumber
          }
          integral({ ...params })
            .then(res => {
              console.log(res)
              if (res.code === 1) {
                this.$message.info('修改成功')
                this.$refs.table.refresh(true)
              } else {
                this.$message.info(res.msg)
              }
            })
            .catch(err => {
              console.log(err)
            })
        } else {
          console.log('编辑==', id)
        }
      }
      this.projectId = id
      this.disabled = !this.disabled
    },
    handleMembersStatus(key, userID) {
      console.log(`Click on item ${key}====${userID}`)
      const form = this.$refs.createModal.form
      const params = {
        user_id: userID,
        member_status: key
      }
      updateMembers(params)
        .then(res => {
          if (res.code === 1) {
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
    },
    resetTable() {
      this.queryParam = {}
      this.$refs.table.refresh(true)
    },
    onRadioChange(event) {
      console.log(event)
      this.$refs.table.refresh(true)
    },
    handleAdd() {
      this.mdl = null
      this.visible = true
    },
    handleEdit(record) {
      this.visible = true
      this.mdl = { ...record }
    },
    // 查看我的团队
    handleTeam(record) {
      console.log(record)
      this.visibleTeam = true
      console.log(this.visibleTeam)
      this.userId = record.user_id
      this.teammdl = { ...record }
    },
    // 查看用户详情
    handleInfo(userID) {
      console.log('用户ID', userID)
      this.$router.push({
        path: '/users/details',
        query: {
          id: userID
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
    handleOk(ModifyImg) {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
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

      //   this.dataSource = dataSource.filter(item => item.key !== key)
    },
    handleCancel() {
      this.visible = false

      const form = this.$refs.createModal.form
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
<style scoped>
.integral_wrap {
  display: flex;
}
</style>

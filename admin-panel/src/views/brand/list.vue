<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <!-- <div class="table-page-search-wrapper">
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
      </div> -->

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
        rowKey="id"
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
              <a @click="handleEdit(record)">编辑</a>
              <a-divider type="vertical" />
              <a @click="handleInfo(record.id)">详情</a>
              <a-divider type="vertical" />
              <a-popconfirm
                v-if="loadData.length"
                title="确认删除?"
                @confirm="() => onDelete(record.id)"
              >
                <a href="javascript:;">删除</a>
              </a-popconfirm>
            </span>
          </template>
        </span>

        <span
          slot="logo"
          slot-scope="text, record"
        >
          <a-avatar
            :size="44"
            icon="user"
            :src="record.logo"
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
    </a-card>
  </page-header-wrapper>
</template>

<script>
import moment from 'moment'
import { STable, Ellipsis } from '@/components'
import { brandList, addBrand, updateBrand, deleteBrand } from '@/api/brand'
import CreateForm from './modules/CreateForm'
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
    title: 'ID',
    dataIndex: 'id'
  },
  {
    title: '品牌名称',
    dataIndex: 'brand_name'
  },
  {
    title: '品牌logo',
    dataIndex: 'logo',
    align: 'center',
    scopedSlots: { customRender: 'logo' }
  },
  {
    title: '联系人',
    dataIndex: 'contact_person'
  },
  {
    title: '联系电话',
    dataIndex: 'phone'
  },

  {
    title: '分店数量',
    dataIndex: 'brand_shop_count'
  },
  {
    title: '总积分',
    dataIndex: 'shop_integral_sum'
  },
  {
    title: '创建时间',
    dataIndex: 'created_at'
  },

  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'BrandList',
  components: {
    STable,
    Ellipsis,
    CreateForm
  },
  data() {
    this.columns = columns
    return {
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
        return brandList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      selectedRowKeys: [],
      selectedRows: [],
      baseUrl: process.env.VUE_APP_API_BASE_URL,

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
  created() {},
  computed: {},
  methods: {
    moment,

    resetTable() {
      this.queryParam = {}
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

    // 查看用户详情
    handleInfo(id) {
      this.$router.push({
        path: '/brand/details',
        query: {
          id: id
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
            brand_name: values.brand_name,
            contact_person: values.contact_person,
            phone: values.phone,
            logo:
              values.id > 0
                ? ModifyImg
                  ? values.logo[0].response.data.file_path.file_path
                  : values.logo
                : values.logo[0].response.data.file_path.file_path
          }
          console.log('requestParameters=====', requestParameters)
          if (values.id > 0) {
            const obj = { id: values.id }
            console.log('编辑')
            updateBrand({ ...requestParameters, ...obj })
              .then(res => {
                this.confirmLoading = false
                if (res.code === 1) {
                  this.visible = false

                  // 重置表单数据
                  form.resetFields()
                  // 刷新表格
                  this.$refs.table.refresh()
                  this.$message.success(res.msg)
                } else {
                  this.$message.warn(res.msg)
                }
              })
              .catch(err => {
                console.log(err)
              })
          } else {
            // 新增
            addBrand(requestParameters).then(res => {
              console.log(res)
              this.confirmLoading = false
              if (res.code === 1) {
                this.visible = false
                // 重置表单数据
                form.resetFields()
                // 刷新表格
                this.$refs.table.refresh()
                this.$message.success(res.msg)
              } else {
                this.$message.warn(res.msg)
              }
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
        id: key
      }
      deleteBrand(requestParameters).then(res => {
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
    }
  }
}
</script>

<template>
  <page-header-wrapper>
    <template
      v-slot:content
      v-if="Object.keys(brandDetail).length > 0"
    >
      <div class="page-header-content">
        <div
          class="avatar"
          style="text-align: center"
        >
          <a-avatar
            icon="user"
            :size="72"
            :src="`${brandDetail.logo}`"
          />
          <div>{{ brandDetail.brand_name }}</div>
        </div>
        <div class="content">
          <div class="content-title">
            <span class="welcome-text">
              <span>{{ brandDetail.contact_person }}</span>
              <span class="content_members_status">创建时间:
                <span>{{ brandDetail.created_at }}</span>
              </span>
            </span>
          </div>

          <div class="content-user-info">
            <span v-if="brandDetail.phone">
              <a-icon type="phone" /><span class="content-user-info-detail">{{ brandDetail.phone }}</span>
            </span>
          </div>
        </div>
      </div>
    </template>
    <template v-slot:extraContent>
      <div class="extra-content">
        <div class="stat-item">
          <a-statistic
            title="分店总数"
            :value="shopList.brand_shop_count"
          />
        </div>
        <div class="stat-item">
          <a-statistic
            title="品牌总积分"
            :value="shopList.shop_integral_sum"
          />
        </div>

      </div>
    </template>
    <div>
      <a-card
        class="project-list"
        style="margin-bottom: 24px"
        :bordered="false"
        title="分店列表"
      >
        <div class="table-operator">
          <a-button
            type="primary"
            icon="plus"
            @click="handleAdd"
          >新建</a-button>
        </div>
        <a-table
          ref="table"
          size="default"
          rowKey="id"
          :columns="columns"
          :data-source="shopList"
          :pagination="pagination"
        >
          <span
            slot="operation"
            slot-scope="text, record"
          >
            <template>
              <span>
                <a @click="handleEmployees(record)">店员列表</a>
                <a-divider type="vertical" />
                <a @click="handleBill(record)">账单</a>
                <a-divider type="vertical" />
                <a @click="handleEdit(record)">编辑</a>
                <a-divider type="vertical" />
                <a-popconfirm
                  title="确认删除?"
                  @confirm="() => onDelete(record.id)"
                >
                  <a href="javascript:;">删除</a>
                </a-popconfirm>
              </span>
            </template>
          </span>
        </a-table>
      </a-card>

    </div>
    <create-shop-form
      ref="createModal"
      :visible="visible"
      :loading="confirmLoading"
      :model="mdl"
      @cancel="handleCancel"
      @ok="handleOk"
    />
    <employees-form
      ref="employeesModal"
      :visible="visibleEmployees"
      :loading="confirmLoadingEmployees"
      :model="employeesList"
      @cancel="handleCancelEmployees"
      @ok="handleOkEmployees"
    />
    <bill-form
      ref="billModal"
      :visible="visibleBill"
      :loading="confirmLoadingBill"
      :model="mdlBill"
      @cancel="handleCancelBill"
      @ok="handleOkBill"
    />
  </page-header-wrapper>
</template>

<script>
import { PageHeaderWrapper } from '@ant-design-vue/pro-layout'
import CreateShopForm from './modules/CreateShopForm'
import EmployeesForm from './modules/EmployeesForm'
import BillForm from './modules/BillForm'
import {
  brandInfo,
  employeesList,
  addShop,
  updateShop,
  deleteShop
} from '@/api/brand'
const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: 'ID',
    dataIndex: 'id'
  },
  {
    title: '分店名称',
    dataIndex: 'shop_name'
  },
  {
    title: '分店地址',
    dataIndex: 'shop_address'
  },
  {
    title: '分店积分',
    dataIndex: 'shop_integral'
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
  name: 'BrandDetails',
  components: {
    PageHeaderWrapper,
    CreateShopForm,
    EmployeesForm,
    BillForm
  },
  data() {
    return {
      pagination,
      // create model
      visible: false,
      confirmLoading: false,
      mdl: null,
      visibleEmployees: false,
      confirmLoadingEmployees: false,
      employeesList: null,
      visibleBill: false,
      confirmLoadingBill: false,
      mdlBill: null,
      brandId: '',
      brandDetail: {},
      shopList: [],
      userPlayId: '',
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      avatar: '',
      user: {},
      columns,
      miniLineOption: {},
      miniLineHistoryOption: {},
      miniBar2Option: {}
    }
  },
  computed: {},
  created() {
    this.brandId = this.$route.query.id
    this.getBrandInfo()
  },
  mounted() {},
  methods: {
    getBrandInfo() {
      brandInfo({ id: this.brandId })
        .then(res => {
          if (res.code === 1) {
            this.brandDetail = res.data.brand_detail
            this.shopList = res.data.brand_shop_list
          } else {
            this.$message.error(res.msg)
          }
        })
        .catch(err => {
          console.log(err)
        })
    },
    handleAdd() {
      this.mdl = null
      this.visible = true
    },
    handleEdit(record) {
      this.visible = true
      this.mdl = { ...record }
    },
    handleOk() {
      const form = this.$refs.createModal.form
      this.confirmLoading = true
      form.validateFields((errors, values) => {
        if (!errors) {
          console.log('values', values)
          const requestParameters = {
            brand_id: parseInt(this.brandId),
            shop_name: values.shop_name,
            shop_address: values.shop_address
          }
          console.log('requestParameters=====', requestParameters)
          if (values.id > 0) {
            const obj = { id: values.id }
            console.log('编辑')
            updateShop({ ...requestParameters, ...obj })
              .then(res => {
                if (res.code === 1) {
                  this.visible = false
                  this.confirmLoading = false
                  // 重置表单数据
                  form.resetFields()
                  // 刷新表格
                  this.getBrandInfo()
                  this.$message.info(res.msg)
                } else {
                  this.confirmLoading = false
                  this.$message.warn(res.msg)
                }
              })
              .catch(err => {
                console.log(err)
              })
          } else {
            // 新增
            addShop(requestParameters).then(res => {
              console.log(res)
              this.visible = false
              this.confirmLoading = false
              // 重置表单数据
              form.resetFields()
              // 刷新表格
              this.getBrandInfo()
              this.$message.info('新增成功')
            })
          }
        } else {
          this.confirmLoading = false
        }
      })
    },
    handleCancel() {
      this.visible = false
      const form = this.$refs.createModal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    onDelete(key) {
      console.log(key)
      const requestParameters = {
        id: key
      }
      deleteShop(requestParameters).then(res => {
        console.log(res)
        // 刷新表格
        this.getBrandInfo()
        this.$message.info(res.msg)
      })
    },
    // 账单列表
    handleBill(record) {
      this.visibleBill = true
      this.mdlBill = { ...record }
    },
    handleOkBill() {},
    handleCancelBill() {
      this.visibleBill = false
      const form = this.$refs.billModal.form
      form.resetFields() // 清理表单数据（可不做）
    },
    // 获取店员列表
    handleEmployees(record) {
      this.visibleEmployees = true
      this.employeesList = { ...record }
    },
    handleOkEmployees() {},
    handleCancelEmployees() {
      this.visibleEmployees = false
      const form = this.$refs.employeesModal.form
      form.resetFields() // 清理表单数据（可不做）
    }
  }
}
</script>

<style lang="less" scoped>
@import './less/Details.less';
</style>

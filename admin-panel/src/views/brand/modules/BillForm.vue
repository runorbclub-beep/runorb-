<template>
  <a-drawer
    title="账单列表"
    width="90%"
    :visible="visible"
    :confirmLoading="loading"
    @close="
      onClose
    "
    :afterVisibleChange="afterVisibleChange"
  >
    <div class="table-page-search-wrapper">
      <a-form layout="inline">

        <a-row :gutter="48">
          <a-col
            :md="8"
            :sm="24"
          >
            <a-form-item label="账单类型">
              <a-radio-group
                :default-value="1"
                button-style="solid"
                v-model="type"
                @change="onRadioChange"
              >
                <a-radio-button :value="1">
                  进账
                </a-radio-button>
                <a-radio-button :value="2">
                  出账
                </a-radio-button>
              </a-radio-group>
            </a-form-item>
          </a-col>

        </a-row>

      </a-form>
    </div>
    <div class="table-operator">
      <!-- <a-button
        type="primary"
        icon="plus"
        @click="handleAdd"
      >新建</a-button> -->
    </div>
    <a-table
      ref="table"
      size="default"
      rowKey="id"
      :columns="columns"
      :data-source="billData"
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

  </a-drawer>
</template>

<script>
import storage from 'store'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import { billList } from '@/api/brand'
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
    title: '积分',
    dataIndex: 'integral'
  },
  {
    title: '备注',
    dataIndex: 'remark'
  },
  {
    title: '操作时间',
    dataIndex: 'created_at'
  }

  // {
  //   title: '操作',
  //   dataIndex: 'operation',
  //   scopedSlots: { customRender: 'operation' }
  // }
]

export default {
  props: {
    visible: {
      type: Boolean,
      required: true
    },
    loading: {
      type: Boolean,
      default: () => false
    },
    model: {
      type: Object,
      default: () => null
    }
  },
  components: {},

  data() {
    const that = this
    console.log(that)
    this.formLayout = {
      labelCol: {
        xs: { span: 24 },
        sm: { span: 4 }
      },
      wrapperCol: {
        xs: { span: 24 },
        sm: { span: 20 }
      }
    }
    return {
      form: this.$form.createForm(this),
      token: storage.get(ACCESS_TOKEN),
      columns,
      pagination,
      billData: [],
      type: 1
    }
  },
  created() {
    console.log('custom modal created')

    // 当 model 发生改变时，为表单设置值
    this.$watch('model', () => {
      // 当 model 发生改变时，为表单设置值
      if (this.model !== null) {
        this.getBillList()
      } else {
      }
    })
  },
  computed: {},
  methods: {
    getBillList() {
      billList({
        id: this.model.id,
        brand_id: this.model.brand_id,
        type: this.type
      })
        .then(res => {
          if (res.code === 1) {
            this.billData = res.data
          } else {
            this.$message.error(res.msg)
          }
        })
        .catch(err => {
          console.log(err)
        })
    },
    onRadioChange(e) {
      console.log(e.target.value)
      this.type = e.target.value
      this.getBillList()
    },
    afterVisibleChange(visible) {
      console.log('切换抽屉时动画', visible)
      if (!visible) {
        this.type = 1
      }
    },

    onClose() {
      this.$emit('cancel')
    }
  }
}
</script>

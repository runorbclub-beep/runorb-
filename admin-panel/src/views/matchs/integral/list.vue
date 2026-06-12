<template>
  <div class="main">
    <a-form layout="inline" :form="form" style="float:left;">
      <a-form-item >
        <a-input
          v-model="search"
          :placeholder="$t('matchs.integral_search.placeholder')"
        >
          <a-icon slot="prefix" type="search" style="color:rgba(0,0,0,.25)" />
        </a-input>
      </a-form-item>
      <a-form-item>
        <a-button type="primary" @click="searchClick">
          {{ $t('global.search') }}
        </a-button>
      </a-form-item>
    </a-form>

    <a-form layout="inline" :form="form" style="float:right;">
      <a-form-item>
        <a-button type="primary" @click="add_match_integral">
          {{ $t('matchs.integral.add') }}
        </a-button>
      </a-form-item>
    </a-form>

    <a-table
      style="clear:both;background-color:#fff;"
      :pagination="pagination"
      :columns="columns"
      :data-source="list"
      :row-key="record => record.matchs_integral_rule_id"
      :loading="listLoading">
      <span slot="get_integral_type" slot-scope="obj">
        <span v-if="obj.get_integral_type === 0">{{ $t('matchs.integral.rule.type.person') }}</span>
        <span v-if="obj.get_integral_type === 1">{{ $t('matchs.integral.rule.type.percentage') }}</span>
      </span>

      <span slot="get_integral_value" slot-scope="obj">
        <span v-if="obj.get_integral_type === 0">{{ obj.get_integral_value }}</span>
        <span v-if="obj.get_integral_type === 1">{{ obj.get_integral_value }} %</span>
      </span>

      <span slot="operation" slot-scope="obj">
        <a-button icon="edit" @click="editMatchIntegralRule(obj)">{{ $t('global.edit') }}</a-button>
        <a-popconfirm :title="$t('match.integral.rule.delete')" :ok-text="$t('global.yes')" :cancel-text="$t('global.no')" @confirm="deleteMatchIntegralRule(obj)">
          <a-button type="danger" icon="delete">{{ $t('global.delete') }}</a-button>
        </a-popconfirm>
      </span>
    </a-table>
  </div>
</template>

<script>
import { postMatchIntegralRulesList, postMatchIntegralRulesDelete } from '@/api/matchs'

const columns = [
  {
    title: 'UUID',
    dataIndex: 'matchs_integral_rule_id',
    key: 'matchs_integral_rule_id'
  },
  {
    title: '规则名称',
    dataIndex: 'integral_rules_title',
    key: 'integral_rules_title'
  },
  {
    title: '发放条件',
    key: 'get_integral_type',
    scopedSlots: { customRender: 'get_integral_type' }
  },
  {
    title: '发放人数/占比',
    key: 'get_integral_value',
    scopedSlots: { customRender: 'get_integral_value' },
    align: 'right'
  },
  {
    title: '最高积分',
    dataIndex: 'max_integral',
    key: 'max_integral',
    align: 'right'
  },
  {
    title: '递减积分',
    dataIndex: 'sub_integral',
    key: 'sub_integral',
    align: 'right'
  },
  {
    title: '操作',
    key: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  data() {
    return {
        columns,
        form: this.$form.createForm(this, { name: 'medal_search' }),
        search: '',
        page: 1,
        listLoading: false,
        pagination: {
          pageSize: 10,
          showSizeChanger: true,
          pageSizeOptions: ['10', '20', '30', '40'],
          total: 0,
          showTotal: total => ` ${total} 条`,
          onChange: (page, pageSize) => this.changePage(page, pageSize),
          onShowSizeChange: (page, pageSize) =>
            this.pageSizeChange(page, pageSize)
        },
        list: []
    }
  },
  mounted() {
    this.getList()
  },
  methods: {
    // 获取赛段规则列表
    getList() {
      this.listLoading = true
      var params = {
        page: this.page,
        limit: this.pagination.pageSize,
        search: this.search
      }
      postMatchIntegralRulesList(params).then((res) => this.postMatchIntegralRulesListSuccess(res))
    },
    postMatchIntegralRulesListSuccess(res) {
      this.listLoading = false
      this.list = res.data.list
      this.pagination.total = res.data.count
    },
    // 搜索按钮点击事件
    searchClick() {
      this.page = 1
      this.getList()
    },
    // 分页点击事件
    changePage(page, pageSize) {
      this.page = page
      this.pagination.pageSize = pageSize
      this.getList()
    },
    // 切换每页显示条数
    pageSizeChange(page, pageSize) {
      this.page = page
      this.pagination.pageSize = pageSize

      this.getList()
    },
    //   新增积分规则
    add_match_integral() {
      this.$router.push({
          path: '/matchs/integral/add'
      })
    },
    // 编辑积分规则
    editMatchIntegralRule(obj) {
      this.$router.push({
          path: '/matchs/integral/add',
          query: { matchs_integral_rule_id: obj.matchs_integral_rule_id }
      })
    },
    // 删除积分规则
    deleteMatchIntegralRule(obj) {
      var params = {
        matchs_integral_rule_id: obj.matchs_integral_rule_id
      }
      postMatchIntegralRulesDelete(params).then((res) => this.deleteMatchIntegralRuleSuccess(res))
    },
    deleteMatchIntegralRuleSuccess(res) {
      if (res.code === 1) {
        this.$message.success(res.msg)
        this.getList()
      } else {
        this.$message.error(res.msg)
      }
    }
  }
}
</script>

<style lang="less" scoped>
</style>

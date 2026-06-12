<template>
  <div class="main">
    <a-form layout="inline" :form="form" style="float:left;">
      <a-form-item >
        <a-input
          v-model="search"
          :placeholder="$t('matchs.search.placeholder')"
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
        <a-button type="primary" @click="add_match_rule">
          {{ $t('matchs.rule.add') }}
        </a-button>
      </a-form-item>
    </a-form>

    <a-table
      style="clear:both;background-color:#fff;"
      :pagination="pagination"
      :columns="columns"
      :data-source="list"
      :row-key="record => record.matchs_stage_rule_id"
      :loading="listLoading">
      <span slot="match_promotion_type" slot-scope="obj">
        <span v-if="obj.match_promotion_type === 0">{{ $t('matchs.stage.rule.type.person') }}</span>
        <span v-if="obj.match_promotion_type === 1">{{ $t('matchs.stage.rule.type.percentage') }}</span>
      </span>

      <span slot="match_promotion_value" slot-scope="obj">
        <span v-if="obj.match_promotion_type === 0">{{ obj.match_promotion_value }}</span>
        <span v-if="obj.match_promotion_type === 1">{{ obj.match_promotion_value }} %</span>
      </span>

      <span slot="operation" slot-scope="obj">
        <a-button icon="edit" @click="editMatchStageRule(obj)">{{ $t('global.edit') }}</a-button>
        <a-popconfirm :title="$t('match.stage.rule.delete')" :ok-text="$t('global.yes')" :cancel-text="$t('global.no')" @confirm="deleteMatchStageRule(obj)">
          <a-button type="danger" icon="delete">{{ $t('global.delete') }}</a-button>
        </a-popconfirm>
      </span>
    </a-table>
  </div>
</template>

<script>
import { postMatchStageRulesList, postMatchStageRulesDelete } from '@/api/matchs'

const columns = [
  {
    title: 'UUID',
    dataIndex: 'matchs_stage_rule_id',
    key: 'matchs_stage_rule_id'
  },
  {
    title: '规则名称',
    dataIndex: 'match_rules_title',
    key: 'match_rules_title'
  },
  {
    title: '赛事类型',
    dataIndex: 'matchs_type_title',
    key: 'matchs_type_title'
  },
  {
    title: '晋级条件',
    key: 'match_promotion_type',
    scopedSlots: { customRender: 'match_promotion_type' }
  },
  {
    title: '晋级人数/占比',
    key: 'match_promotion_value',
    scopedSlots: { customRender: 'match_promotion_value' },
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
      // postMatchStageRulesList().then()
      var params = {
        page: this.page,
        limit: this.pagination.pageSize,
        search: this.search
      }
      postMatchStageRulesList(params).then((res) => this.MatchsStageRulesAddSuccess(res))
    },
    MatchsStageRulesAddSuccess(res) {
      this.listLoading = false
      this.list = res.data.list
      this.pagination.total = res.data.count
      console.log(res.data.list)
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
    //   新增赛事规则
    add_match_rule() {
      this.$router.push({
          path: '/matchs/rule/add'
      })
    },
    // 编辑赛段规则
    editMatchStageRule(obj) {
      this.$router.push({
          path: '/matchs/rule/add',
          query: { matchs_stage_rule_id: obj.matchs_stage_rule_id, matchs_type_id: obj.matchs_type_id }
      })
    },
    // 删除赛段规则
    deleteMatchStageRule(obj) {
      console.log(obj)
      var params = {
        matchs_stage_rule_id: obj.matchs_stage_rule_id
      }
      postMatchStageRulesDelete(params).then((res) => this.deleteMatchStageRuleSuccess(res))
    },
    deleteMatchStageRuleSuccess(res) {
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

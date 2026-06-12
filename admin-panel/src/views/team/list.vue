<template>
  <page-header-wrapper>
    <a-card :bordered="false">

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
              <a @click="handleApproved(record.id)">通过</a>
              <!-- <a-divider type="vertical" /> -->
              <!-- <a @click="handleInfo(record.id)">详情</a>
              <a-divider type="vertical" /> -->
              <!-- <a-popconfirm
                v-if="loadData.length"
                title="确认删除?"
                @confirm="() => onDelete(record.id)"
              >
                <a href="javascript:;">删除</a>
              </a-popconfirm> -->
            </span>
          </template>
        </span>

        <span
          slot="clan_avatar"
          slot-scope="text, record"
        >
          <a-avatar
            shape="square"
            :size="44"
            icon="user"
            :src="record.clan_avatar"
          />
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
          slot="introduction"
          slot-scope="text"
        >
          <ellipsis
            :length="50"
            tooltip
          >
            {{ text }}
          </ellipsis>
        </span>
        <span
          slot="remark"
          slot-scope="text"
        >
          <ellipsis
            :length="50"
            tooltip
          >
            {{ text }}
          </ellipsis>
        </span>
        <span
          slot="sys_sex_id"
          slot-scope="text"
        >
          {{ text === '1791224340025344'?'男':'女' }}
        </span>
      </s-table>

    </a-card>
  </page-header-wrapper>
</template>

<script>
import moment from 'moment'
import { STable, Ellipsis } from '@/components'
import { teamList, approved } from '@/api/team'

const pagination = {
  showQuickJumper: true,
  showTotal: (total, range) => `第 ${range[0]}-${range[1]} 条/总共 ${total} 条`
}
const columns = [
  {
    title: '战队ID',
    dataIndex: 'id'
  },
  {
    title: '队长ID',
    dataIndex: 'user_id'
  },
  {
    title: '战队名称',
    dataIndex: 'title'
  },
  {
    title: '战队图片',
    dataIndex: 'clan_avatar',
    scopedSlots: { customRender: 'clan_avatar' }
  },

  {
    title: '所在区域',
    dataIndex: 'address'
  },

  {
    title: '队长名称',
    dataIndex: 'user_name'
  },
  {
    title: '队长头像',
    dataIndex: 'user_img',
    scopedSlots: { customRender: 'user_img' }
  },
  {
    title: '性别',
    dataIndex: 'sys_sex_id',
    scopedSlots: { customRender: 'sys_sex_id' }
  },
  {
    title: '战队联系方式',
    dataIndex: 'telephone'
  },
  {
    title: '战队简介',
    dataIndex: 'introduction',
    scopedSlots: { customRender: 'introduction' }
  },
  {
    title: '备注',
    dataIndex: 'remark',
    scopedSlots: { customRender: 'remark' }
  },
  {
    title: '申请时间',
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
    Ellipsis
  },
  data() {
    this.columns = columns
    return {
      pagination,
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
        console.log(parameter)
        const obj = {}

        const requestParameters = Object.assign(obj, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        return teamList(requestParameters).then(res => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      selectedRowKeys: [],
      selectedRows: [],
      baseUrl: process.env.VUE_APP_API_BASE_URL
    }
  },

  methods: {
    moment,
    handleApproved(id) {
      const params = {
        user_clan_id: id,
        status: 1
      }
      approved(params)
        .then(res => {
          if (res.code === 1) {
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
    },
    resetTable() {
      this.queryParam = {}
      this.$refs.table.refresh(true)
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
    }
  }
}
</script>

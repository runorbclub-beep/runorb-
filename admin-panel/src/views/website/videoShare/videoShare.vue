<template>
  <page-header-wrapper>
    <a-card :bordered="false">
      <div class="table-page-search-wrapper">
        <a-form layout="inline">
          <a-row :gutter="48">
            <a-col :md="8" :sm="24">
              <a-form-item label="关键字">
                <a-input v-model="queryParam.search" placeholder="文章标题" />
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
      </div>

      <div class="table-operator">
        <a-button type="primary" icon="plus" @click="handleAdd">新建</a-button>
        <a-dropdown v-action:edit v-if="selectedRowKeys.length > 0">
          <a-menu slot="overlay">
            <a-menu-item key="1"><a-icon type="delete" />删除</a-menu-item>
          </a-menu>
          <a-button style="margin-left: 8px"> 批量操作 <a-icon type="down" /> </a-button>
        </a-dropdown>
      </div>

      <s-table
        ref="table"
        size="default"
        rowKey="sys_new_id"
        :columns="columns"
        :data="loadData"
        :alert="false"
        showPagination="auto"
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

        <span slot="operation" slot-scope="text, record">
          <template>
            <a @click="handleEdit(record)">编辑</a>
            <a-divider type="vertical" />
            <a-popconfirm v-if="loadData.length" title="确认删除?" @confirm="() => onDelete(record.sys_new_id)">
              <a href="javascript:;">删除</a>
            </a-popconfirm>
          </template>
        </span>
        <span slot="news_type" slot-scope="text">
          {{ text === 1 ? '资讯' : '新闻' }}
        </span>
        <span slot="news_content" slot-scope="text">
          <div v-html="text"></div>
        </span>

        <span slot="image" slot-scope="text, record">
          <img style="width: 50px; heigth: 50px" :src="imgURL + '/' + record.news_img" />
        </span>
      </s-table>
    </a-card>
  </page-header-wrapper>
</template>

<script>
import { STable, Ellipsis } from '@/components'
import { getNewsList, deleteNews } from '@/api/news'

const columns = [
  {
    title: '文章ID',
    dataIndex: 'sys_new_id'
  },
  {
    title: '文章标题',
    dataIndex: 'news_title'
  },
  {
    title: '文章类型',
    dataIndex: 'news_type',
    scopedSlots: { customRender: 'news_type' }
  },

  {
    title: '文章首图',
    dataIndex: 'news_img',
    scopedSlots: { customRender: 'image' }
  },
  // {
  //   title: '文章内容',
  //   dataIndex: 'news_content',
  //   ellipsis: true,
  //   scopedSlots: { customRender: 'news_content' },
  // },

  {
    title: '操作',
    dataIndex: 'operation',
    scopedSlots: { customRender: 'operation' }
  }
]

export default {
  name: 'Say',
  components: {
    STable,
    Ellipsis
  },
  data() {
    this.columns = columns
    return {
      // create model
      visible: false,
      confirmLoading: false,
      mdl: null,
      // 高级搜索 展开/关闭
      advanced: false,
      // 查询参数
      queryParam: {},
      // 加载数据方法 必须为 Promise 对象
      loadData: (parameter) => {
        console.log(parameter)
        const requestParameters = Object.assign({}, parameter, this.queryParam)
        console.log('loadData request parameters:', requestParameters)
        return getNewsList(requestParameters).then((res) => {
          console.log(res)
          return Object.assign(res.data, parameter)
        })
      },
      selectedRowKeys: [],
      selectedRows: [],
      brandDict: [],
      categoryDict: [],
      baseUrl: process.env.VUE_APP_API_BASE_URL,
      imgURL: ''
    }
  },
  filters: {},
  created() {
    this.imgURL = this.baseUrl.replace(new RegExp('(.*/)[^/]+$'), '$1')

    console.log(this.imgURL)
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
    handleAdd() {
      this.$router.push({
        path: '/website/index/edit',
        query: {
          visible: true,
          mdl: null
        }
      })
    },
    handleEdit(record) {
      this.$router.push({
        path: '/website/index/edit',
        query: {
          visible: true,
          mdl: JSON.stringify({ ...record })
        }
      })
    },

    onDelete(key) {
      console.log(key)
      const requestParameters = {
        sys_new_id: key
      }
      deleteNews(requestParameters).then((res) => {
        console.log(res)

        // 刷新表格
        this.$refs.table.refresh()

        this.$message.info(res.msg)
      })
    },

    onSelectChange(selectedRowKeys, selectedRows) {
      this.selectedRowKeys = selectedRowKeys
      this.selectedRows = selectedRows
    }
  }
}
</script>

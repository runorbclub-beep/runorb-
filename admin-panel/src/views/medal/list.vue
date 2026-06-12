<template>
  <div class="main">
    <a-form layout="inline" :form="form" style="float:left;">
      <a-form-item >
        <a-input
          v-decorator="[
            'medal_name',
            { rules: [{ required: true, message: 'Please input medal name!' }] },
          ]"
          :placeholder="$t('medal.search.placeholder')"
        >
          <a-icon slot="prefix" type="search" style="color:rgba(0,0,0,.25)" />
        </a-input>
      </a-form-item>
      <a-form-item>
        <a-button type="primary" >
          {{ $t('global.search') }}
        </a-button>
      </a-form-item>
    </a-form>

    <a-form layout="inline" :form="form" style="float:right;">
      <a-form-item>
        <a-button type="primary" @click="add_medal">
          {{ $t('medal.add') }}
        </a-button>
      </a-form-item>
    </a-form>

    <a-table :columns="columns" :data-source="data" style="clear:both;">
      <a slot="name" slot-scope="text">{{ text }}</a>
    </a-table>
  </div>
</template>

<script>

const columns = [
  {
    title: 'Name',
    dataIndex: 'name',
    key: 'name',
    scopedSlots: { customRender: 'name' }
  },
  {
    title: 'Age',
    dataIndex: 'age',
    key: 'age',
    width: 80
  },
  {
    title: 'Address',
    dataIndex: 'address',
    key: 'address 1',
    ellipsis: true
  },
  {
    title: 'Long Column Long Column Long Column',
    dataIndex: 'address',
    key: 'address 2',
    ellipsis: true
  },
  {
    title: 'Long Column Long Column',
    dataIndex: 'address',
    key: 'address 3',
    ellipsis: true
  },
  {
    title: 'Long Column',
    dataIndex: 'address',
    key: 'address 4',
    ellipsis: true
  }
]

const data = [
  {
    key: '1',
    name: 'John Brown',
    age: 32,
    address: 'New York No. 1 Lake Park, New York No. 1 Lake Park',
    tags: ['nice', 'developer']
  },
  {
    key: '2',
    name: 'Jim Green',
    age: 42,
    address: 'London No. 2 Lake Park, London No. 2 Lake Park',
    tags: ['loser']
  },
  {
    key: '3',
    name: 'Joe Black',
    age: 32,
    address: 'Sidney No. 1 Lake Park, Sidney No. 1 Lake Park',
    tags: ['cool', 'teacher']
  }
]

export default {
  data() {
    return {
        columns,
        data,
        form: this.$form.createForm(this, { name: 'medal_search' })
    }
  },
  created() {

  },
  methods: {
    //   新增勋章
      add_medal() {
        this.$router.push({
            path: '/medal/add'
        })
      }
  }
}
</script>

<style lang="less" scoped>
</style>

import request from '@/utils/request'

const api = {
  enterpriseList: '/qiyeShake/list',
  editEnterprise: '/qiyeShake/add',
  enterpriseInfo: '/qiyeShake/info',
  deleteEnterprise: '/qiyeShake/delete'
}

// 企业列表
export function enterpriseList(parameter) {
  return request({
    url: api.enterpriseList,
    method: 'post',
    data: parameter
  })
}
// 编辑企业
export function editEnterprise(parameter) {
  return request({
    url: api.editEnterprise,
    method: 'post',
    data: parameter
  })
}
// 企业详情
export function enterpriseInfo(parameter) {
  return request({
    url: api.enterpriseInfo,
    method: 'post',
    data: parameter
  })
}
// 删除企业
export function deleteEnterprise(parameter) {
  return request({
    url: api.deleteEnterprise,
    method: 'post',
    data: parameter
  })
}

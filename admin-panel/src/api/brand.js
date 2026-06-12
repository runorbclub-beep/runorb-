import request from '@/utils/request'

const api = {
  brandList: '/redeem/brand/list',
  addBrand: '/redeem/brand/add',
  updateBrand: '/redeem/brand/edit',
  deleteBrand: '/redeem/brand/del',
  brandInfo: '/redeem/brand/getBrandDetail',
  billList: '/redeem/brand/getBrandRedeemLog',
  employeesList: '/redeem/brand/getBrandUser',
  shopList: '/redeem/brand/list',
  addShop: '/redeem/brand/addBrandShop',
  updateShop: '/redeem/brand/editBrandShop',
  deleteShop: '/redeem/brand/delBrandShop',
  updateEmployees: '/redeem/brand/editBrandUser',
  associatedRegistered: '/redeem/brand/postBrandUserPhone',
  deleteEmployees: '/redeem/brand/delBrandUser'
}

// export default api

// 品牌列表
export function brandList(parameter) {
  return request({
    url: api.brandList,
    method: 'post',
    data: parameter
  })
}

// 新增品牌
export function addBrand(parameter) {
  return request({
    url: api.addBrand,
    method: 'post',
    data: parameter
  })
}
// 编辑品牌
export function updateBrand(parameter) {
  return request({
    url: api.updateBrand,
    method: 'post',
    data: parameter
  })
}
// 删除品牌
export function deleteBrand(parameter) {
  return request({
    url: api.deleteBrand,
    method: 'post',
    data: parameter
  })
}
// 品牌详情
export function brandInfo(parameter) {
  return request({
    url: api.brandInfo,
    method: 'post',
    data: parameter
  })
}
// 品牌账单列表
export function billList(parameter) {
  return request({
    url: api.billList,
    method: 'post',
    data: parameter
  })
}
// 店员列表
export function employeesList(parameter) {
  return request({
    url: api.employeesList,
    method: 'post',
    data: parameter
  })
}
// 店员编辑
export function updateEmployees(parameter) {
  return request({
    url: api.updateEmployees,
    method: 'post',
    data: parameter
  })
}
// 店员删除
export function deleteEmployees(parameter) {
  return request({
    url: api.deleteEmployees,
    method: 'post',
    data: parameter
  })
}
// 新增分店
export function addShop(parameter) {
  return request({
    url: api.addShop,
    method: 'post',
    data: parameter
  })
}
// 编辑分店
export function updateShop(parameter) {
  return request({
    url: api.updateShop,
    method: 'post',
    data: parameter
  })
}
// 删除分店
export function deleteShop(parameter) {
  return request({
    url: api.deleteShop,
    method: 'post',
    data: parameter
  })
}
// 手机号关联注册
export function associatedRegistered(parameter) {
  return request({
    url: api.associatedRegistered,
    method: 'post',
    data: parameter
  })
}

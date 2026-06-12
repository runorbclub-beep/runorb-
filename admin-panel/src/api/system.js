import request from '@/utils/request'

const api = {
  system: '/system',
  updateSystem: '/system/update',
  appBannerList: '/match/banner/list',
  addAppBannerList: '/match/banner/add',
  deleteAppBannerList: '/match/banner/delete',
  matchs_title_list: '/match/banner/title/list',
  membersList: '/members/description/info',
  updateMembersList: '/members/description/add',
  rankingList: '/system/ranking/list',
  updateRankingList: '/system/ranking/update',
  advertisingList: '/sys/app/advertising/list',
  addAdvertising: '/sys/app/advertising/add',
  updateAdvertising: '/sys/app/advertising/update'
}

// export default api
// 查询系统设置
export function getSystemList(parameter) {
  return request({
    url: api.system,
    method: 'post',
    data: parameter
  })
}
// 编辑系统设置
export function updateSystem(parameter) {
  return request({
    url: api.updateSystem,
    method: 'post',
    data: parameter
  })
}
// 获取APP宣传图列表
export function getAppBannerList(parameter) {
  return request({
    url: api.appBannerList,
    method: 'post',
    data: parameter
  })
}
// 新增/修改APP宣传图
export function addAppBannerList(parameter) {
  return request({
    url: api.addAppBannerList,
    method: 'post',
    data: parameter
  })
}
// 删除APP宣传图
export function deleteAppBannerList(parameter) {
  return request({
    url: api.deleteAppBannerList,
    method: 'post',
    data: parameter
  })
}

// 赛事标题列表
export function MatchtitleList(parameter) {
  return request({
    url: api.matchs_title_list,
    method: 'post',
    data: parameter
  })
}

// 会员招募列表
export function getMembersList(parameter) {
  return request({
    url: api.membersList,
    method: 'post',
    data: parameter
  })
}
// 更新会员招募列表
export function updateMembersList(parameter) {
  return request({
    url: api.updateMembersList,
    method: 'post',
    data: parameter
  })
}

// 查询榜单列表
export function getRankingList(parameter) {
  return request({
    url: api.rankingList,
    method: 'post',
    data: parameter
  })
}

// 更新榜单列表
export function updateRankingList(parameter) {
  return request({
    url: api.updateRankingList,
    method: 'post',
    data: parameter
  })
}
// 获取广告列表
export function getAdvertisingList(parameter) {
  return request({
    url: api.advertisingList,
    method: 'post',
    data: parameter
  })
}
// 新增广告
export function addAdvertising(parameter) {
  return request({
    url: api.addAdvertising,
    method: 'post',
    data: parameter
  })
}
// 更新广告
export function updateAdvertising(parameter) {
  return request({
    url: api.updateAdvertising,
    method: 'post',
    data: parameter
  })
}

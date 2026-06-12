import request from '@/utils/request'

const api = {
  userList: '/user/list',
  rankingUserList: '/website/match/ranking/choose/user/list',
  userTypeList: '/sys/user/type/list',
  userInfo: '/user/detail',
  userPlayList: '/user/play/list',
  userPlayInfo: '/user/play/info',
  updateMembers: '/user/join/members/update',
  integral: '/user/edit'
}

// export default api
// 获取全部用户列表
export function getUserList(parameter) {
  return request({
    url: api.userList,
    method: 'post',
    data: parameter
  })
}
// 获取榜单用户列表
export function getRankingUserList(parameter) {
  return request({
    url: api.rankingUserList,
    method: 'post',
    data: parameter
  })
}
// 获取用户类型 游客 注册
export function getUserTypeList(parameter) {
  return request({
    url: api.userTypeList,
    method: 'post',
    data: parameter
  })
}
// 获取用户详情
export function getUserInfo(parameter) {
  return request({
    url: api.userInfo,
    method: 'post',
    data: parameter
  })
}
// 获取用户运动数据列表
export function getUserPlayList(parameter) {
  return request({
    url: api.userPlayList,
    method: 'post',
    data: parameter
  })
}
// 用户单次运动数据明细
export function getUserPlayInfo(parameter) {
  return request({
    url: api.userPlayInfo,
    method: 'post',
    data: parameter
  })
}
// 用户会员状态变更
export function updateMembers(parameter) {
  return request({
    url: api.updateMembers,
    method: 'post',
    data: parameter
  })
}
// 用户积分更新
export function integral(parameter) {
  return request({
    url: api.integral,
    method: 'post',
    data: parameter
  })
}

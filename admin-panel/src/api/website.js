import request from '@/utils/request'

const api = {
  // 首页
  getHomeList: '/home/list',
  addHome: '/home/add',
  updateHomeIndex: '/home/index/update',
  deleteHome: '/home/delete',
  // app
  addVersion: '/app/version/add',
  versionList: '/app/version/list',
  deleteVersion: '/app/version/delete',
  // 关于我们
  aboutMeList: '/aboutme/list',
  addAboutMe: '/aboutme/add',
  deleteAboutMe: '/aboutme/delete',
  // 赛事榜单
  addLeaderboards: '/website/match/ranking/add',
  leaderboardsList: '/website/match/ranking/list',
  leaderboardsListUsersList: '/website/match/ranking/user/list',
  leaderboardsListAddUser: '/website/match/ranking/user/add',
  updateLeaderboardsListUsers: '/website/match/ranking/user/update',
  leaderboardsListDeleteUser: '/website/match/ranking/user/delete',
  deleteLeaderboards: '/website/match/ranking/delete',
  // 更多活动
  activityList: '/activity/list',
  activityInfo: '/activity/info',
  deleteActivity: '/activity/delete',
  addActivity: '/activity/add'

}

// export default api
// 首页列表查询
export function getHomeList(parameter) {
  return request({
    url: api.getHomeList,
    method: 'post',
    data: parameter
  })
}
// 新增、编辑首页内容
export function addHome(parameter) {
  return request({
    url: api.addHome,
    method: 'post',
    data: parameter
  })
}
// 变更内容排序
export function updateHomeIndex(parameter) {
  return request({
    url: api.updateHomeIndex,
    method: 'post',
    data: parameter
  })
}
// 删除首页内容
export function deleteHome(parameter) {
  return request({
    url: api.deleteHome,
    method: 'post',
    data: parameter
  })
}
// 新增、编辑APP版本
export function addVersion(parameter) {
  return request({
    url: api.addVersion,
    method: 'post',
    data: parameter
  })
}
// 历史版本列表
export function getVersionList(parameter) {
  return request({
    url: api.versionList,
    method: 'post',
    data: parameter
  })
}
// 删除版本
export function deleteVersion(parameter) {
  return request({
    url: api.deleteVersion,
    method: 'post',
    data: parameter
  })
}

// 关于我们列表
export function getAboutMeList(parameter) {
  return request({
    url: api.aboutMeList,
    method: 'post',
    data: parameter
  })
}
// 新增、编辑关于我们
export function addAboutMe(parameter) {
  return request({
    url: api.addAboutMe,
    method: 'post',
    data: parameter
  })
}
// 删除关于我们
export function deleteAboutMe(parameter) {
  return request({
    url: api.deleteAboutMe,
    method: 'post',
    data: parameter
  })
}

// 赛事榜单

// 查询赛事榜单列表
export function getLeaderboardsList(parameter) {
  return request({
    url: api.leaderboardsList,
    method: 'post',
    data: parameter
  })
}

// 新增、编辑榜单
export function addLeaderboards(parameter) {
  return request({
    url: api.addLeaderboards,
    method: 'post',
    data: parameter
  })
}
// 删除榜单
export function deleteLeaderboards(parameter) {
  return request({
    url: api.deleteLeaderboards,
    method: 'post',
    data: parameter
  })
}

// 查询赛事榜单列表下的用户列表
export function getLeaderboardsUsersList(parameter) {
  return request({
    url: api.leaderboardsListUsersList,
    method: 'post',
    data: parameter
  })
}
// 榜单新增用户
export function addleaderboardsListUser(parameter) {
  return request({
    url: api.leaderboardsListAddUser,
    method: 'post',
    data: parameter
  })
}
// 榜单下用户数据更新
export function updateLeaderboardsListUser(parameter) {
  return request({
    url: api.updateLeaderboardsListUsers,
    method: 'post',
    data: parameter
  })
}
// 榜单删除用户
export function deleteLeaderboardsListUser(parameter) {
  return request({
    url: api.leaderboardsListDeleteUser,
    method: 'post',
    data: parameter
  })
}
// 获取活动列表
export function getActivityList(parameter) {
  return request({
    url: api.activityList,
    method: 'post',
    data: parameter
  })
}
// 活动详情
export function getActivityInfo(parameter) {
  return request({
    url: api.activityInfo,
    method: 'post',
    data: parameter
  })
}
// 新增/编辑活动
export function addActivity(parameter) {
  return request({
    url: api.addActivity,
    method: 'post',
    data: parameter
  })
}
// 删除活动
export function deleteActivity(parameter) {
  return request({
    url: api.deleteActivity,
    method: 'post',
    data: parameter
  })
}

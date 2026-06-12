import request from '@/utils/request'

const api = {
  addGroup: '/group/add',
  getGroupList: '/group/list',
  addGroupUser: '/group/join/user',
  deleteGroupUser: '/group/delete/user',
  getGroupUserList: '/group/user/list'
}

// export default api
// 新增，编辑团队
export function addGroup(parameter) {
  return request({
    url: api.addGroup,
    method: 'post',
    data: parameter
  })
}
// 查询团队列表
export function getGroupList(parameter) {
  return request({
    url: api.getGroupList,
    method: 'post',
    data: parameter
  })
}
// 团队新增用户
export function addGroupUser(parameter) {
  return request({
    url: api.addGroupUser,
    method: 'post',
    data: parameter
  })
}
// 团队移除用户
export function deleteGroupUser(parameter) {
  return request({
    url: api.deleteGroupUser,
    method: 'post',
    data: parameter
  })
}
// 团队下的用户列表
export function getGroupUserList(parameter) {
  return request({
    url: api.getGroupUserList,
    method: 'post',
    data: parameter
  })
}

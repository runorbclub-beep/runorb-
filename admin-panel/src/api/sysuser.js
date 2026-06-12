import request from '@/utils/request'

const api = {
  sys_user_type_list: '/sys/user/type/list',
  sys_user_sex_list: '/sys/user/sex/list'
}

// export default api

// 查询用户类型列表
export function postSysUserTypeList(parameter) {
  return request({
    url: api.sys_user_type_list,
    method: 'post',
    data: parameter
  })
}

// 查询用户性别列表
export function postSysUserSexList(parameter) {
  return request({
    url: api.sys_user_sex_list,
    method: 'post',
    data: parameter
  })
}

import request from '@/utils/request'

const api = {
  playList: '/play/list',
  abnormalList: '/play/abnormal/list',
  updateAbnormal: '/play/abnormal/update',
  deleteUserPlay: '/play/list/delete',
  starList: '/play/star/list'
}

// 获取运动列表
export function getSportList(parameter) {
  return request({
    url: api.playList,
    method: 'post',
    data: parameter
  })
}
// 获取运动判定异常列表
export function getAbnormalList(parameter) {
  return request({
    url: api.abnormalList,
    method: 'post',
    data: parameter
  })
}
// 更新运动异常判定列表
export function updateAbnormalList(parameter) {
  return request({
    url: api.updateAbnormal,
    method: 'post',
    data: parameter
  })
}
// 运动列表删除用户数据
export function deleteUserPlay(parameter) {
  return request({
    url: api.deleteUserPlay,
    method: 'post',
    data: parameter
  })
}
// 每日之星列表
export function getStarList(parameter) {
  return request({
    url: api.starList,
    method: 'post',
    data: parameter
  })
}

import request from '@/utils/request'

const api = {
  analysis: '/home'
}

// 新增，编辑团队
export function getAnalysis(parameter) {
  return request({
    url: api.analysis,
    method: 'post',
    data: parameter
  })
}

import request from '@/utils/request'

const api = {
  addNews: '/news/add',
  getNewsInfo: '/news/get',
  getNewsList: '/news/list',
  deleteNews: '/news/delete'
}

// export default api
// 新增/修改 文章
export function addNews(parameter) {
  return request({
    url: api.addNews,
    method: 'post',
    data: parameter
  })
}
// 查询文章信息
export function getNewsInfo(parameter) {
  return request({
    url: api.getNewsInfo,
    method: 'post',
    data: parameter
  })
}
// 查询文章列表
export function getNewsList(parameter) {
  return request({
    url: api.getNewsList,
    method: 'post',
    data: parameter
  })
}
// 删除文章
export function deleteNews(parameter) {
  return request({
    url: api.deleteNews,
    method: 'post',
    data: parameter
  })
}

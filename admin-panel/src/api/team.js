import request from '@/utils/request'

const api = {
  teamList: '/clans/getPendingReviewList',
  approved: '/clans/postPendingReview'

}

// 战队列表
export function teamList(parameter) {
  return request({
    url: api.teamList,
    method: 'post',
    data: parameter
  })
}

// 审核通过
export function approved(parameter) {
  return request({
    url: api.approved,
    method: 'post',
    data: parameter
  })
}

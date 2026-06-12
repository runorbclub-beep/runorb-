import request from '@/utils/request'

const api = {
  medal_add: '/medal/add'
}

// export default api

export function postMedalAdd(parameter) {
  return request({
    url: api.medal_add,
    method: 'post',
    data: parameter
  })
}

import request from '@/utils/request'

const api = {
  matchs_stage_rules: '/match/stage/rules/add',
  matchs_stage_list: '/match/stage/rules/list',
  matchs_stage_rules_info: '/match/stage/rules/info',
  matchs_stage_rules_delete: '/match/stage/rules/delete',
  matchs_event_type_add: '/match/event/type/add',
  matchs_event_type_list: '/match/event/type/list',
  matchs_event_type_delete: '/match/event/type/delete',
  matchs_type_add: '/match/type/add',
  matchs_type_list: '/match/type/list',
  matchs_type_delete: '/match/type/delete',
  matchs_add: '/match/add',
  matchs_list: '/match/list',
  matchs_info: '/match/info',
  matchs_release: '/match/release',
  matchs_unrelease: '/match/unrelease',
  matchs_delete: '/match/delete',
  matchs_title_list: '/match/title/list',
  matchs_integral_rule_add: '/match/integral/rule/add',
  matchs_integral_rule_list: '/match/integral/rule/list',
  matchs_integral_rule_info: '/match/integral/rule/info',
  matchs_integral_rule_delete: '/match/integral/rule/delete',
  matchs_event_add: '/match/event/add',
  matchs_event_delete: '/match/event/delete',
  matchs_event_stage_add: '/match/events/stages/add',
  matchs_event_stage_delete: '/match/events/stages/delete',
  updateProjectIndex: '/match/event/type/index/update',
  matchs_stage_users_list: '/matchs/stage/user/list',
  matchs_stage_movement_data: '/matchs/stage/user/play/list',
  add_match_user_sign: '/match/add-match-user-sign',
  get_team_tag: '/match/get-team-tag',
  addTeamTag: '/match/add-team-tag'
}

// export default api

// 查询赛段晋级规则列表
export function postMatchStageRulesList(parameter) {
  return request({
    url: api.matchs_stage_list,
    method: 'post',
    data: parameter
  })
}

// 新增、编辑赛段晋级规则
export function postMatchStageRulesAdd(parameter) {
  return request({
    url: api.matchs_stage_rules,
    method: 'post',
    data: parameter
  })
}

// 查询赛段晋级规则详情
export function postMatchStageRulesInfo(parameter) {
  return request({
    url: api.matchs_stage_rules_info,
    method: 'post',
    data: parameter
  })
}

// 删除当前赛段规则
export function postMatchStageRulesDelete(parameter) {
  return request({
    url: api.matchs_stage_rules_delete,
    method: 'post',
    data: parameter
  })
}

// 新增比赛项目
export function MatchsEventTypeAdd(parameter) {
  return request({
    url: api.matchs_event_type_add,
    method: 'post',
    data: parameter
  })
}

// 查询比赛项目列表
export function MatchsEventTypeList(parameter) {
  return request({
    url: api.matchs_event_type_list,
    method: 'post',
    data: parameter
  })
}
// 删除比赛项目
export function MatchsEventTypeDelete(parameter) {
  return request({
    url: api.matchs_event_type_delete,
    method: 'post',
    data: parameter
  })
}

// 查询比赛类型列表
export function MatchsTypeList(parameter) {
  return request({
    url: api.matchs_type_list,
    method: 'post',
    data: parameter
  })
}

// 删除比赛类型
export function MatchsTypeDelete(parameter) {
  return request({
    url: api.matchs_type_delete,
    method: 'post',
    data: parameter
  })
}

// 新增比赛类型
export function MatchsTypeAdd(parameter) {
  return request({
    url: api.matchs_type_add,
    method: 'post',
    data: parameter
  })
}

// 新增赛事
export function MatchsAdd(parameter) {
  return request({
    url: api.matchs_add,
    method: 'post',
    data: parameter
  })
}

// 赛事列表
export function SysMatchList(parameter) {
  return request({
    url: api.matchs_list,
    method: 'post',
    data: parameter
  })
}

// 赛事详情
export function SysMatchInfo(parameter) {
  return request({
    url: api.matchs_info,
    method: 'post',
    data: parameter
  })
}

// 发布赛事
export function MatchRelease(parameter) {
  return request({
    url: api.matchs_release,
    method: 'post',
    data: parameter
  })
}

// 取消发布赛事
export function MatchUnRelease(parameter) {
  return request({
    url: api.matchs_unrelease,
    method: 'post',
    data: parameter
  })
}

// 删除赛事
export function MatchDelete(parameter) {
  return request({
    url: api.matchs_delete,
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
// 创建赛事积分规则
export function postMatchIntegralRulesAdd(parameter) {
  return request({
    url: api.matchs_integral_rule_add,
    method: 'post',
    data: parameter
  })
}
// 查询赛事积分规则信息
export function postMatchIntegralRulesInfo(parameter) {
  return request({
    url: api.matchs_integral_rule_info,
    method: 'post',
    data: parameter
  })
}

// 查询赛事积分规则列表
export function postMatchIntegralRulesList(parameter) {
  return request({
    url: api.matchs_integral_rule_list,
    method: 'post',
    data: parameter
  })
}

// 删除赛事积分规则
export function postMatchIntegralRulesDelete(parameter) {
  return request({
    url: api.matchs_integral_rule_delete,
    method: 'post',
    data: parameter
  })
}

// 新增赛事项目
export function postMatchEventAdd(parameter) {
  return request({
    url: api.matchs_event_add,
    method: 'post',
    data: parameter
  })
}
// 删除赛事项目
export function postMatchEventDelete(parameter) {
  return request({
    url: api.matchs_event_delete,
    method: 'post',
    data: parameter
  })
}
// 新增赛段
export function postMatchEventStageAdd(parameter) {
  return request({
    url: api.matchs_event_stage_add,
    method: 'post',
    data: parameter
  })
}

// 删除赛段
export function postMatchEventStageDelete(parameter) {
  return request({
    url: api.matchs_event_stage_delete,
    method: 'post',
    data: parameter
  })
}
// 更新比赛项目序号
export function updateProjectIndex(parameter) {
  return request({
    url: api.updateProjectIndex,
    method: 'post',
    data: parameter
  })
}
// 赛段用户列表
export function matchsStageUsersList(parameter) {
  return request({
    url: api.matchs_stage_users_list,
    method: 'post',
    data: parameter
  })
}
// 赛段用户运动数据
export function matchsStageMovementData(parameter) {
  return request({
    url: api.matchs_stage_movement_data,
    method: 'post',
    data: parameter
  })
}
// 新增用户
export function addMatchUserSign(parameter) {
  return request({
    url: api.add_match_user_sign,
    method: 'post',
    data: parameter
  })
}
// 获取当前赛事下组队列表
export function teamTag(parameter) {
  return request({
    url: api.get_team_tag,
    method: 'post',
    data: parameter
  })
}
// 新增组队
export function addTeamTag(parameter) {
  return request({
    url: api.addTeamTag,
    method: 'post',
    data: parameter
  })
}

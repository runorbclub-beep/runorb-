// eslint-disable-next-line
import { UserLayout, BasicLayout, BlankLayout } from '@/layouts'
import { bxAnaalyse } from '@/core/icons'

const RouteView = {
  name: 'RouteView',
  render: (h) => h('router-view')
}

export const asyncRouterMap = [
  {
    path: '/',
    name: 'index',
    component: BasicLayout,
    meta: { title: 'menu.home' },
    redirect: '/dashboard/analysis',
    children: [
      // dashboard
      {
        path: '/dashboard',
        name: 'dashboard',
        redirect: '/dashboard/analysis',
        component: RouteView,
        meta: { title: 'menu.dashboard', keepAlive: true, icon: bxAnaalyse, permission: ['supper_admin'] },
        children: [
          {
            path: '/dashboard/analysis/:pageNo([1-9]\\d*)?',
            name: 'Analysis',
            component: () => import('@/views/dashboard/Analysis'),
            meta: { title: 'menu.dashboard.analysis', keepAlive: false, permission: ['supper_admin'] }
          }
        ]
      },
      // 运动管理
      {
        path: '/sport',
        redirect: '/sport/list',
        component: RouteView,
        meta: { title: 'menu.sport', icon: 'project', permission: ['supper_admin'] },
        children: [
          {
            path: '/sport/list',
            name: 'SportList',
            component: () => import('@/views/sport/list'),
            meta: { title: 'menu.sport.list', keepAlive: true, permission: ['supper_admin'] }
          },
          {
            path: '/sport/abnormal',
            name: 'Abnormal',
            component: () => import('@/views/sport/abnormal'),
            meta: { title: 'menu.sport.abnormal', keepAlive: true, permission: ['supper_admin'] }
          },
          {
            path: '/sport/dailyStar',
            name: 'DailyStar',
            component: () => import('@/views/sport/dailyStar'),
            meta: { title: '每日之星', keepAlive: true, permission: ['supper_admin'] }
          }
        ]
      },
      // matchs
      {
        path: '/matchs',
        redirect: '/matchs/list',
        component: RouteView,
        meta: { title: '赛事管理', icon: 'trophy', permission: ['supper_admin'] },
        children: [
          // 赛事列表
          {
            path: '/matchs/list',
            name: 'matchsList',
            component: () => import('@/views/matchs/list'),
            meta: { title: 'menu.matchs.list', keepAlive: true, permission: ['supper_admin'] }
          },
          // 新增赛事
          {
            path: '/matchs/add',
            name: 'matchsAdd',
            hidden: true,
            component: () => import('@/views/matchs/add'),
            meta: { title: 'menu.matchs.add', keepAlive: true, permission: ['supper_admin'] }
          },
          // 赛事中心
          {
            path: '/matchs/center',
            name: 'matchsCenter',
            hidden: true,
            component: () => import('@/views/matchs/match_center'),
            meta: { title: 'menu.matchs.center', keepAlive: true, permission: ['supper_admin'] }
          },
          {
            path: '/matchs/center/usersList/:matchId/:stageId/:isGroup/:sysMatchId/:sysSysMatchId/:matchsStageStatus',
            name: 'MatchsUsersList',
            hidden: true,
            component: () => import('@/views/matchs/center/usersList/list'),
            meta: { title: '用户列表', keepAlive: true, permission: ['supper_admin'] }
          },
          // 新增赛事项目
          // {
          //   path: '/matchs/event/add',
          //   name: 'matchsEventAdd',
          //   hidden: true,
          //   component: () => import('@/views/matchs/match_event_add'),
          //   meta: { title: 'menu.matchs.node.add', keepAlive: true, permission: ['supper_admin'] }
          // },
          // 赛事类型
          {
            path: '/matchs/type/list',
            name: 'matchsTypesList',
            component: () => import('@/views/matchs/type/list'),
            meta: { title: 'menu.matchs.type.list', keepAlive: true, permission: ['supper_admin'] }
          },
          // 新增赛事类型
          {
            path: '/matchs/type/add',
            name: 'matchsTypeAdd',
            hidden: true,
            component: () => import('@/views/matchs/type/add'),
            meta: { title: 'menu.matchs.type.add', keepAlive: true, permission: ['supper_admin'] }
          },
          // 赛段规则列表
          {
            path: '/matchs/rule/list',
            name: 'matchsRuleList',
            hidden: true,
            component: () => import('@/views/matchs/rule/list'),
            meta: { title: 'menu.matchs.rule.list', keepAlive: true, permission: ['supper_admin'] }
          },
          // 新增、编辑赛段规则
          {
            path: '/matchs/rule/add',
            name: 'matchsRuleAdd',
            hidden: true,
            component: () => import('@/views/matchs/rule/add'),
            meta: { title: 'menu.matchs.rule.add', keepAlive: true, permission: ['supper_admin'] }
          },
          // 比赛项目列表
          {
            path: '/matchs/events/list',
            name: 'matchsEventsList',
            component: () => import('@/views/matchs/events/list'),
            meta: { title: 'menu.matchs.events.list', keepAlive: true, permission: ['supper_admin'] }
          },
          // 新增比赛项目
          {
            path: '/matchs/events/add',
            name: 'matchsEventsAdd',
            hidden: true,
            component: () => import('@/views/matchs/events/add'),
            meta: { title: 'menu.matchs.events.add', keepAlive: true, permission: ['supper_admin'] }
          },
          // 积分规则列表
          {
            path: '/matchs/integral/list',
            name: 'matchsIntegralList',
            hidden: true,
            component: () => import('@/views/matchs/integral/list'),
            meta: { title: 'menu.matchs.integral.list', keepAlive: true, permission: ['supper_admin'] }
          },
          // 新增积分规则
          {
            path: '/matchs/integral/add',
            name: 'matchsIntegralAdd',
            hidden: true,
            component: () => import('@/views/matchs/integral/add'),
            meta: { title: 'menu.matchs.integral.add', keepAlive: true, permission: ['supper_admin'] }
          }
        ]
      },

      // 用户管理
      {
        path: '/users',
        redirect: '/users/list',
        component: RouteView,
        meta: { title: 'menu.users', icon: 'user', permission: ['supper_admin'] },
        children: [
          {
            path: '/users/list',
            name: 'UsersList',
            component: () => import('@/views/users/list'),
            meta: { title: 'menu.users.list', keepAlive: true, permission: ['supper_admin'] }
          },
          {
            path: '/users/details',
            name: 'UsersDetails',
            hidden: true,
            component: () => import('@/views/users/details'),
            meta: { title: 'menu.users.details', keepAlive: true, permission: ['supper_admin'] }
          }
        ]
      },
      // 品牌管理
      {
        path: '/brand',
        redirect: '/brand/list',
        component: RouteView,
        meta: { title: 'menu.brand', icon: 'codepen-circle', permission: ['supper_admin'] },
        children: [
          {
            path: '/brand/list',
            name: 'BrandList',
            component: () => import('@/views/brand/list'),
            meta: { title: 'menu.brand.list', keepAlive: true, permission: ['supper_admin'] }
          },
          {
            path: '/brand/details',
            name: 'BrandDetails',
            hidden: true,
            component: () => import('@/views/brand/details'),
            meta: { title: 'menu.brand.details', keepAlive: true, permission: ['supper_admin'] }
          }
        ]
      },
      // 团队管理
      {
        path: '/group',
        redirect: '/group/list',
        component: RouteView,
        meta: { title: 'menu.group', icon: 'team', permission: ['supper_admin'] },
        children: [
          {
            path: '/group/list',
            name: 'GroupList',
            component: () => import('@/views/group/list'),
            meta: { title: 'menu.group.list', keepAlive: true, permission: ['supper_admin'] }
          }
        ]
      },

      // 战队管理
      {
        path: '/team',
        redirect: '/team/list',
        component: RouteView,
        meta: { title: '战队管理', icon: 'gitlab', permission: ['supper_admin'] },
        children: [
          {
            path: '/team/list',
            name: 'TeamList',
            component: () => import('@/views/team/list'),
            meta: { title: '战队列表', keepAlive: true, permission: ['supper_admin'] }
          }
        ]
      },
      // 企业管理
      {
        path: '/enterprise',
        redirect: '/enterprise/list',
        component: RouteView,
        meta: { title: '企业管理', icon: 'team', permission: ['supper_admin'] },
        children: [
          {
            path: '/enterprise/list',
            name: 'EnterpriseList',
            component: () => import('@/views/enterprise'),
            meta: { title: '企业列表', keepAlive: true, permission: ['supper_admin'] }
          }
        ]
      },
      // 官网
      {
        path: '/website',
        redirect: '/website/index',
        component: RouteView,
        meta: { title: 'menu.website', icon: 'ie', permission: ['supper_admin'] },
        children: [
          {
            path: '/website/index',
            redirect: '/website/index/say',
            component: RouteView,
            meta: { title: 'menu.website.index', keepAlive: true, permission: ['supper_admin'] },
            children: [
              {
                path: '/website/index/say',
                name: 'Say',
                component: () => import('@/views/website/index/say'),
                meta: { title: 'menu.website.index.say', keepAlive: true, permission: ['supper_admin'] }
              }

            ]
          },
          // 新闻列表
          {
            path: '/news/list',
            name: 'NewsList',
            component: () => import('@/views/news/newsList'),
            meta: {
              title: 'menu.news.list',
              keepAlive: true,
              permission: ['supper_admin']
            }
          },
          // 新闻列表编辑
          // {
          //   path: '/news/list/edit',
          //   name: 'NewsListEdit',
          //   hidden: true,
          //   component: () => import('@/views/news/components/newsEdit'),
          //   meta: {
          //     title: '编辑',
          //     keepAlive: true,
          //     permission: ['supper_admin']
          //   }
          // },
          // 活动列表
          {
            path: '/website/activitiesList',
            name: 'ActivitiesList',
            component: () => import('@/views/website/moreActivities/activitiesList'),
            meta: {
              title: '活动列表',
              keepAlive: true,
              permission: ['supper_admin']
            }
          },
          // APP下载
          {
            path: '/website/app',
            name: 'app',
            component: () => import('@/views/website/app/app'),
            meta: { title: 'menu.app', permission: ['supper_admin'] }
          },
          // 关于我们
          {
            path: '/website/aboutUs',
            name: 'AboutUs',
            component: () => import('@/views/website/aboutUs/aboutUs'),
            meta: { title: 'menu.aboutUs', permission: ['supper_admin'] }
          },

          // 赛事榜单
          {
            path: '/website/leaderboards',
            name: 'Leaderboards',
            component: () => import('@/views/website/leaderboards/list'),
            meta: { title: 'menu.leaderboards', permission: ['supper_admin'] }
          }
        ]
      },

      // 系统设置
      {
        path: '/system',
        component: RouteView,
        redirect: '/system/settings',
        name: 'system',
        meta: { title: 'menu.system', icon: 'setting', keepAlive: true, permission: ['supper_admin'] },
        children: [
          // {
          //   path: '/system/center',
          //   name: 'center',
          //   component: () => import('@/views/system/center'),
          //   meta: { title: 'menu.system.center', keepAlive: true, permission: ['supper_admin'] },
          // },
          {
            path: '/system/settings',
            name: 'settings',
            component: () => import('@/views/system/settings/Index'),
            meta: { title: 'menu.system.settings', hideHeader: true, permission: ['supper_admin'] },
            redirect: '/system/settings/team',
            hideChildrenInMenu: true,
            children: [
              {
                path: '/system/settings/team',
                name: 'TeamSetting',
                component: () => import('@/views/system/settings/TeamSetting'),
                meta: { title: 'system.settings.menuMap.team', hidden: true, permission: ['supper_admin'] }
              },
              {
                path: '/system/settings/RunBall',
                name: 'RunBallSetting',
                component: () => import('@/views/system/settings/RunBallSetting'),
                meta: { title: 'system.settings.menuMap.Runball', hidden: true, permission: ['supper_admin'] }
              },
              {
                path: '/system/settings/matches',
                name: 'MatchesSetting',
                component: () => import('@/views/system/settings/MatchesSetting'),
                meta: { title: 'system.settings.menuMap.matches', hidden: true, permission: ['supper_admin'] }
              }

              // {
              //   path: '/system/settings/custom',
              //   name: 'CustomSettings',
              //   component: () => import('@/views/system/settings/Custom'),
              //   meta: {
              //     title: 'system.settings.menuMap.custom',
              //     hidden: true,
              //     keepAlive: true,
              //     permission: ['supper_admin'],
              //   },
              // },
              // {
              //   path: '/system/settings/binding',
              //   name: 'BindingSettings',
              //   component: () => import('@/views/system/settings/Binding'),
              //   meta: {
              //     title: 'system.settings.menuMap.binding',
              //     hidden: true,
              //     keepAlive: true,
              //     permission: ['supper_admin'],
              //   },
              // },
              // {
              //   path: '/system/settings/notification',
              //   name: 'NotificationSettings',
              //   component: () => import('@/views/system/settings/Notification'),
              //   meta: {
              //     title: 'system.settings.menuMap.notification',
              //     hidden: true,
              //     keepAlive: true,
              //     permission: ['supper_admin'],
              //   },
              // },
            ]
          },
          // APP宣传图设置
          {
            path: '/system/settings/AppBanner',
            name: 'AppBanner',
            component: () => import('@/views/system/settings/AppBanner'),
            meta: {
              title: 'system.settings.menuMap.AppBanner',
              keepAlive: true,
              permission: ['supper_admin']
            }
          },
          // APP启动页设置
          {
            path: '/system/settings/AppStartPage',
            name: 'AppStartPage',
            component: () => import('@/views/system/settings/AppStartPage'),
            meta: {
              title: 'system.settings.menuMap.AppStartPage',
              keepAlive: true,
              permission: ['supper_admin']
            }
          },
          // 会员招募设置
          {
            path: '/system/settings/memberRecruit',
            name: 'MemberRecruit',
            component: () => import('@/views/system/settings/MemberRecruit'),
            meta: {
              title: 'system.settings.menuMap.memberRecruit',
              keepAlive: true,
              permission: ['supper_admin']
            }
          },
          // 榜单设置
          {
            path: '/system/settings/leaderboard',
            name: 'Leaderboard',
            component: () => import('@/views/system/settings/leaderboard'),
            meta: {
              title: 'system.settings.menuMap.leaderboard',
              keepAlive: true,
              permission: ['supper_admin']
            }
          }
        ]
      }
    ]
  },
  {
    path: '*',
    redirect: '/404',
    hidden: true
  }
]

/**
 * 基础路由
 * @type { *[] }
 */
export const constantRouterMap = [
  {
    path: '/user',
    component: UserLayout,
    redirect: '/user/login',
    hidden: true,
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import(/* webpackChunkName: "user" */ '@/views/user/Login')
      },
      {
        path: 'register',
        name: 'register',
        component: () => import(/* webpackChunkName: "user" */ '@/views/user/Register')
      },
      {
        path: 'register-result',
        name: 'registerResult',
        component: () => import(/* webpackChunkName: "user" */ '@/views/user/RegisterResult')
      },
      {
        path: 'recover',
        name: 'recover',
        component: undefined
      }
    ]
  },

  {
    path: '/404',
    component: () => import(/* webpackChunkName: "fail" */ '@/views/exception/404')
  }
]

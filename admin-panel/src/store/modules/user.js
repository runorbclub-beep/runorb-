import storage from 'store'
// import { login, getInfo, logout } from '@/api/login'
import { login } from '@/api/login'
import { ACCESS_TOKEN } from '@/store/mutation-types'
import { welcome } from '@/utils/util'
import notification from 'ant-design-vue/es/notification'

const user = {
  state: {
    token: '',
    name: '',
    welcome: '',
    avatar: '',
    roles: [],
    info: {}
  },

  mutations: {
    SET_TOKEN: (state, token) => {
      state.token = token
    },
    SET_NAME: (state, { name, welcome }) => {
      state.name = name
      state.welcome = welcome
    },
    SET_AVATAR: (state, avatar) => {
      state.avatar = avatar
    },
    SET_ROLES: (state, roles) => {
      state.roles = roles
    },
    SET_INFO: (state, info) => {
      state.info = info
    }
  },

  actions: {
    // 登录
    // Login({ commit }, userInfo) {
    //   return new Promise((resolve, reject) => {
    //     login(userInfo)
    //       .then((response) => {
    //         console.log(response)
    //         const result = response.data
    //         storage.set(ACCESS_TOKEN, result.token, 7 * 24 * 60 * 60 * 1000)
    //         storage.set('nick_name', result.nick_name)
    //         commit('SET_TOKEN', result.token)
    //         resolve()
    //       })
    //       .catch((error) => {
    //         reject(error)
    //       })
    //   })
    // },

    // 登录
    Login({ commit }, userInfo) {
      return new Promise((resolve, reject) => {
        login(userInfo)
          .then((response) => {
            const result = response
            console.log(response)
            if (result.code !== 0) {
              storage.set(ACCESS_TOKEN, result.data.token, 7 * 24 * 60 * 60 * 1000)
              storage.set('role_name', result.data.role_name)
              storage.set('role_code', result.data.role_code)
              storage.set('user_name', result.data.user_name)
              storage.set('nick_name', result.data.nick_name)
              storage.set('user_info', JSON.stringify(result.data))
              commit('SET_TOKEN', result.data.token)
              resolve()
            } else {
              notification.error({
                message: '错误',
                description: result.msg
              })
              reject(result)
            }
          })
          .catch((error) => {
            reject(error)
          })
      })
    },

    // 获取用户信息
    // GetInfo({ commit }) {
    //   return new Promise((resolve, reject) => {
    //     getInfo()
    //       .then((response) => {
    //         const result = response.data
    //         commit('SET_ROLES', result.role_code)
    //         commit('SET_INFO', result)
    //         commit('SET_NAME', { name: result.nick_name, welcome: welcome() })
    //         commit('SET_AVATAR', result.user_img)

    //         resolve(response)
    //       })
    //       .catch((error) => {
    //         reject(error)
    //       })
    //   })
    // },

    // 获取用户权限信息
    GetInfo({ commit }) {
      return new Promise((resolve, reject) => {
        const userInfo = JSON.parse(storage.get('user_info'))
        console.log(userInfo)
        const role = {}
        role.permissions = [userInfo]
        role.permissionList = [userInfo.role_code]
        // role.permissions = [
        //   {
        //     role_id: 1,
        //     role_name: '超级管理员',
        //     role_code: 'supper_admin'
        //   },
        //   {
        //     role_id: 2,
        //     role_name: '管理员',
        //     role_code: 'admin'
        //   },
        //   {
        //     role_id: 3,
        //     role_name: '采购',
        //     role_code: 'purchase'
        //   },
        //   {
        //     role_id: 4,
        //     role_name: '印刷',
        //     role_code: 'printing'
        //   }
        // ]
        // role.permissionList = ['supper_admin', 'admin', 'purchase', 'printing']
        console.log(userInfo.user_name)
        commit('SET_ROLES', role)
        commit('SET_INFO', role)
        commit('SET_NAME', { name: userInfo.user_name, welcome: welcome() })
        commit('SET_AVATAR', userInfo.avatar)
        resolve(role)
        // getInfo()
        //   .then(response => {
        //     const result = response.data
        //     console.log(result)
        //     if (result && result.list.length > 0) {
        //       const role = result
        //       role.permissions = result.list
        //       // role.permissions.map(per => {
        //       //   if (per.actionEntitySet != null && per.actionEntitySet.length > 0) {
        //       //     const action = per.actionEntitySet.map(action => {
        //       //       return action.action
        //       //     })
        //       //     per.actionList = action
        //       //   }
        //       // })
        //       role.permissionList = role.permissions.map(permission => {
        //         return permission.role_code
        //       })
        //       console.log('role=====', role)
        //       commit('SET_ROLES', role)
        //       commit('SET_INFO', role)
        //     } else {
        //       reject(new Error('getInfo: roles must be a non-null array !'))
        //     }
        //     // commit('SET_NAME', { name: result.name, welcome: welcome() })
        //     // commit('SET_AVATAR', result.avatar)
        //     resolve(result)
        //   })
        //   .catch(error => {
        //     reject(error)
        //   })
      })
    },

    // 登出
    // Logout({ commit, state }) {
    //   return new Promise((resolve) => {
    //     logout(state.token)
    //       .then(() => {
    //         commit('SET_TOKEN', '')
    //         commit('SET_ROLES', [])
    //         storage.remove(ACCESS_TOKEN)
    //         storage.remove('nick_name')
    //         resolve()
    //       })
    //       .catch(() => {
    //         resolve()
    //       })
    //       .finally(() => {})
    //   })
    // },

    // 登出
    Logout({ commit, state }) {
      return new Promise((resolve) => {
        console.log('退出登录')
        commit('SET_TOKEN', '')
        commit('SET_ROLES', [])
        storage.remove(ACCESS_TOKEN)
        storage.remove('nick_name')
        resolve()
      })
    }
  }
}

export default user

/* ============================================
   摇跑活力指数排行榜 - 应用逻辑
   ============================================ */

(function () {
  'use strict';

  // ===================== State =====================
  const state = {
    currentPage: 'home',
    mainTab: 'personal',       // personal | group
    personalTab: 'energy',     // energy | burst | endurance
    regionTab: 'global',       // global | china | city
    groupTab: 'brand',         // brand | city | country
    group: 'male-adult',
    newsFilter: 'all',
    isLoggedIn: false,
    personalPage: 1,
    pageSize: 15,
  };

  // ===================== Constants =====================
  // Cities data loaded from cities.js (CITIES_BY_COUNTRY, CITIES_CN, etc.)
  const AVATAR_COLORS = ['#FF6B35','#004E89','#1A936F','#9B59B6','#E74C3C','#2ECC71','#3498DB','#F39C12','#1ABC9C','#E67E22'];

  // ===================== Seed Ranking Data =====================
  const SEED_DATA = {
    'male-adult': [
      { name: 'Akis K.', city: 'Athens', country: 'Greece', energy: 22.25, rpm: 13200, dist: 1.712, trend: 'up' },
      { name: '李明远', city: '北京', country: '中国', energy: 22.22, rpm: 12981, dist: 1.712, trend: 'same' },
      { name: '王志强', city: '深圳', country: '中国', energy: 21.76, rpm: 13058, dist: 1.667, trend: 'up' },
      { name: 'Takeshi M.', city: 'Tokyo', country: '日本', energy: 21.58, rpm: 12909, dist: 1.672, trend: 'down' },
      { name: '陈浩然', city: '上海', country: '中国', energy: 20.98, rpm: 12702, dist: 1.652, trend: 'same' },
      { name: 'Liam B.', city: 'Dublin', country: '爱尔兰', energy: 20.44, rpm: 12519, dist: 1.632, trend: 'same' },
      { name: '赵鹏飞', city: '广州', country: '中国', energy: 19.5, rpm: 12225, dist: 1.595, trend: 'up' },
      { name: 'Kim S.H.', city: 'Seoul', country: '韩国', energy: 19.2, rpm: 11926, dist: 1.61, trend: 'down' },
      { name: '刘建国', city: '成都', country: '中国', energy: 18.65, rpm: 11694, dist: 1.595, trend: 'down' },
      { name: 'Marcus W.', city: 'Berlin', country: '德国', energy: 18.12, rpm: 11701, dist: 1.549, trend: 'down' },
      { name: '张伟', city: '武汉', country: '中国', energy: 17.79, rpm: 11386, dist: 1.563, trend: 'down' },
      { name: 'Santiago R.', city: 'Madrid', country: '西班牙', energy: 17.47, rpm: 11317, dist: 1.544, trend: 'same' },
      { name: '黄志明', city: '珠海', country: '中国', energy: 16.72, rpm: 11046, dist: 1.514, trend: 'up' },
      { name: 'Raj P.', city: 'Mumbai', country: '印度', energy: 15.95, rpm: 10799, dist: 1.477, trend: 'down' },
      { name: '周磊', city: '杭州', country: '中国', energy: 15.37, rpm: 10442, dist: 1.472, trend: 'same' },
      { name: 'Alex K.', city: 'Sydney', country: '澳大利亚', energy: 14.77, rpm: 10352, dist: 1.427, trend: 'same' },
      { name: '林大伟', city: '台北', country: '中国台湾', energy: 14.33, rpm: 10055, dist: 1.426, trend: 'up' },
      { name: 'Pierre D.', city: 'Paris', country: '法国', energy: 13.87, rpm: 9830, dist: 1.411, trend: 'down' },
      { name: '吴强', city: '南京', country: '中国', energy: 13.49, rpm: 9741, dist: 1.385, trend: 'same' },
      { name: 'James T.', city: 'Toronto', country: '加拿大', energy: 12.96, rpm: 9504, dist: 1.364, trend: 'same' },
      { name: '孙涛', city: '重庆', country: '中国', energy: 12.19, rpm: 9067, dist: 1.345, trend: 'down' },
      { name: 'Roberto V.', city: 'Rome', country: '意大利', energy: 11.93, rpm: 8903, dist: 1.34, trend: 'same' },
      { name: '马超', city: '天津', country: '中国', energy: 11.07, rpm: 8564, dist: 1.292, trend: 'up' },
      { name: 'John P.', city: 'Chicago', country: '美国', energy: 11.01, rpm: 8549, dist: 1.288, trend: 'same' },
      { name: '郑刚', city: '西安', country: '中国', energy: 10.21, rpm: 8025, dist: 1.272, trend: 'down' },
      { name: 'Hans M.', city: 'Munich', country: '德国', energy: 9.83, rpm: 7784, dist: 1.262, trend: 'up' },
      { name: '杨帆', city: '长沙', country: '中国', energy: 9.41, rpm: 7682, dist: 1.224, trend: 'down' },
      { name: 'Takahashi K.', city: 'Nagoya', country: '日本', energy: 9.13, rpm: 7453, dist: 1.224, trend: 'same' },
      { name: '何军', city: '厦门', country: '中国', energy: 8.32, rpm: 7046, dist: 1.181, trend: 'down' },
      { name: 'Ethan B.', city: 'London', country: '英国', energy: 8.05, rpm: 6879, dist: 1.17, trend: 'same' },
    ],
    'male-youth': [
      { name: '陈子轩', city: '北京', country: '中国', energy: 20.1, rpm: 12200, dist: 1.647, trend: 'down' },
      { name: 'Yuki T.', city: 'Osaka', country: '日本', energy: 19.87, rpm: 12186, dist: 1.63, trend: 'up' },
      { name: '张博文', city: '上海', country: '中国', energy: 19.16, rpm: 11903, dist: 1.61, trend: 'up' },
      { name: 'Park J.M.', city: 'Busan', country: '韩国', energy: 18.96, rpm: 11713, dist: 1.619, trend: 'down' },
      { name: '刘天佑', city: '深圳', country: '中国', energy: 18.84, rpm: 11745, dist: 1.604, trend: 'down' },
      { name: 'Ethan C.', city: 'London', country: '英国', energy: 18.13, rpm: 11613, dist: 1.561, trend: 'down' },
      { name: '王浩宇', city: '广州', country: '中国', energy: 17.69, rpm: 11306, dist: 1.564, trend: 'down' },
      { name: 'Leo M.', city: 'São Paulo', country: '巴西', energy: 17.28, rpm: 11226, dist: 1.539, trend: 'up' },
      { name: '赵一鸣', city: '成都', country: '中国', energy: 16.7, rpm: 11048, dist: 1.512, trend: 'up' },
      { name: 'Noah S.', city: 'New York', country: '美国', energy: 16.22, rpm: 10744, dist: 1.51, trend: 'same' },
      { name: '李泽宇', city: '武汉', country: '中国', energy: 15.75, rpm: 10497, dist: 1.5, trend: 'up' },
      { name: 'Ahmed K.', city: 'Dubai', country: '阿联酋', energy: 15.54, rpm: 10507, dist: 1.479, trend: 'down' },
      { name: '周子豪', city: '杭州', country: '中国', energy: 14.96, rpm: 10298, dist: 1.453, trend: 'down' },
      { name: 'Lucas R.', city: 'Berlin', country: '德国', energy: 14.06, rpm: 9926, dist: 1.417, trend: 'down' },
      { name: '黄俊杰', city: '珠海', country: '中国', energy: 13.77, rpm: 9653, dist: 1.427, trend: 'up' },
      { name: 'Omar H.', city: 'Cairo', country: '埃及', energy: 13.11, rpm: 9399, dist: 1.395, trend: 'up' },
      { name: '吴天翔', city: '南京', country: '中国', energy: 12.74, rpm: 9364, dist: 1.361, trend: 'up' },
      { name: 'Jake T.', city: 'Toronto', country: '加拿大', energy: 12.48, rpm: 9150, dist: 1.364, trend: 'down' },
      { name: '孙浩然', city: '重庆', country: '中国', energy: 11.99, rpm: 8945, dist: 1.34, trend: 'up' },
      { name: 'Raj P.', city: 'Mumbai', country: '印度', energy: 11.28, rpm: 8530, dist: 1.323, trend: 'same' },
      { name: '马博文', city: '天津', country: '中国', energy: 11.21, rpm: 8530, dist: 1.315, trend: 'down' },
      { name: 'Tomás G.', city: 'Madrid', country: '西班牙', energy: 10.72, rpm: 8280, dist: 1.295, trend: 'same' },
      { name: '郑凯文', city: '西安', country: '中国', energy: 9.99, rpm: 7915, dist: 1.262, trend: 'same' },
      { name: 'Alex K.', city: 'Sydney', country: '澳大利亚', energy: 9.76, rpm: 7876, dist: 1.239, trend: 'up' },
      { name: '杨子墨', city: '长沙', country: '中国', energy: 9.05, rpm: 7510, dist: 1.206, trend: 'up' },
      { name: 'Pierre D.', city: 'Paris', country: '法国', energy: 8.98, rpm: 7382, dist: 1.216, trend: 'down' },
      { name: '何宇轩', city: '厦门', country: '中国', energy: 8.3, rpm: 7065, dist: 1.175, trend: 'up' },
      { name: 'James W.', city: 'Dublin', country: '爱尔兰', energy: 7.84, rpm: 6831, dist: 1.148, trend: 'up' },
      { name: '林子杰', city: '台北', country: '中国台湾', energy: 7.63, rpm: 6720, dist: 1.136, trend: 'up' },
      { name: 'David L.', city: 'San Francisco', country: '美国', energy: 6.92, rpm: 6213, dist: 1.114, trend: 'down' },
    ],
    'male-senior': [
      { name: '老陈', city: '北京', country: '中国', energy: 18.5, rpm: 11700, dist: 1.581, trend: 'up' },
      { name: 'Hans M.', city: 'Munich', country: '德国', energy: 17.98, rpm: 11468, dist: 1.568, trend: 'same' },
      { name: '王德福', city: '上海', country: '中国', energy: 17.67, rpm: 11418, dist: 1.548, trend: 'up' },
      { name: 'Takahashi K.', city: 'Nagoya', country: '日本', energy: 17.43, rpm: 11330, dist: 1.538, trend: 'same' },
      { name: 'John P.', city: 'Chicago', country: '美国', energy: 17.02, rpm: 11264, dist: 1.511, trend: 'same' },
      { name: '张国栋', city: '广州', country: '中国', energy: 16.54, rpm: 10936, dist: 1.513, trend: 'down' },
      { name: 'Roberto V.', city: 'Rome', country: '意大利', energy: 16.3, rpm: 10880, dist: 1.498, trend: 'up' },
      { name: '李振华', city: '成都', country: '中国', energy: 15.58, rpm: 10693, dist: 1.457, trend: 'same' },
      { name: '周国强', city: '杭州', country: '中国', energy: 15.31, rpm: 10621, dist: 1.441, trend: 'same' },
      { name: 'Lars E.', city: 'Stockholm', country: '瑞典', energy: 14.87, rpm: 10442, dist: 1.424, trend: 'same' },
      { name: '黄志远', city: '珠海', country: '中国', energy: 14.39, rpm: 10047, dist: 1.432, trend: 'up' },
      { name: 'James W.', city: 'London', country: '英国', energy: 13.86, rpm: 9841, dist: 1.408, trend: 'same' },
      { name: '吴建平', city: '南京', country: '中国', energy: 13.57, rpm: 9863, dist: 1.376, trend: 'same' },
      { name: 'Pierre D.', city: 'Paris', country: '法国', energy: 13.2, rpm: 9652, dist: 1.368, trend: 'down' },
      { name: '孙德明', city: '重庆', country: '中国', energy: 12.81, rpm: 9366, dist: 1.368, trend: 'down' },
      { name: 'Santiago R.', city: 'Madrid', country: '西班牙', energy: 12.1, rpm: 9099, dist: 1.33, trend: 'down' },
      { name: '马福生', city: '天津', country: '中国', energy: 11.95, rpm: 8939, dist: 1.337, trend: 'down' },
      { name: 'Liam B.', city: 'Dublin', country: '爱尔兰', energy: 11.18, rpm: 8682, dist: 1.288, trend: 'same' },
      { name: '郑国荣', city: '西安', country: '中国', energy: 10.8, rpm: 8526, dist: 1.267, trend: 'same' },
      { name: 'Alex K.', city: 'Sydney', country: '澳大利亚', energy: 10.72, rpm: 8402, dist: 1.275, trend: 'up' },
      { name: '杨振邦', city: '长沙', country: '中国', energy: 10.13, rpm: 8029, dist: 1.262, trend: 'down' },
      { name: 'Marcus W.', city: 'Berlin', country: '德国', energy: 9.69, rpm: 7928, dist: 1.223, trend: 'same' },
      { name: '何耀祖', city: '厦门', country: '中国', energy: 9.02, rpm: 7579, dist: 1.19, trend: 'up' },
      { name: 'Ethan C.', city: 'Toronto', country: '加拿大', energy: 8.86, rpm: 7406, dist: 1.196, trend: 'same' },
      { name: '林长寿', city: '台北', country: '中国台湾', energy: 8.39, rpm: 7145, dist: 1.175, trend: 'down' },
      { name: 'Takeshi M.', city: 'Tokyo', country: '日本', energy: 8.1, rpm: 7016, dist: 1.154, trend: 'up' },
      { name: '刘永康', city: '武汉', country: '中国', energy: 7.71, rpm: 6793, dist: 1.135, trend: 'same' },
      { name: 'Kim S.H.', city: 'Seoul', country: '韩国', energy: 7.21, rpm: 6561, dist: 1.098, trend: 'down' },
      { name: '赵德厚', city: '郑州', country: '中国', energy: 6.77, rpm: 6202, dist: 1.092, trend: 'up' },
      { name: 'Raj P.', city: 'Mumbai', country: '印度', energy: 6.66, rpm: 6136, dist: 1.085, trend: 'same' },
    ],
    'female-adult': [
      { name: 'Maria G.', city: 'Athens', country: 'Greece', energy: 18.3, rpm: 11600, dist: 1.577, trend: 'up' },
      { name: '陈思琪', city: '深圳', country: '中国', energy: 17.89, rpm: 11543, dist: 1.55, trend: 'down' },
      { name: 'Yuki S.', city: 'Tokyo', country: '日本', energy: 17.68, rpm: 11313, dist: 1.563, trend: 'down' },
      { name: 'Emma L.', city: 'London', country: '英国', energy: 17.29, rpm: 11354, dist: 1.523, trend: 'down' },
      { name: '王丽华', city: '北京', country: '中国', energy: 16.62, rpm: 10962, dist: 1.517, trend: 'down' },
      { name: 'Sarah K.', city: 'Sydney', country: '澳大利亚', energy: 16.2, rpm: 10901, dist: 1.486, trend: 'up' },
      { name: '林雅婷', city: '上海', country: '中国', energy: 15.82, rpm: 10686, dist: 1.481, trend: 'up' },
      { name: 'Kim H.J.', city: 'Seoul', country: '韩国', energy: 15.69, rpm: 10640, dist: 1.474, trend: 'down' },
      { name: 'Claire D.', city: 'Paris', country: '法国', energy: 15.05, rpm: 10361, dist: 1.453, trend: 'same' },
      { name: '张晓燕', city: '广州', country: '中国', energy: 14.82, rpm: 10276, dist: 1.442, trend: 'down' },
      { name: 'Ana R.', city: 'Madrid', country: '西班牙', energy: 14.09, rpm: 9997, dist: 1.41, trend: 'down' },
      { name: '刘梦瑶', city: '成都', country: '中国', energy: 13.87, rpm: 9840, dist: 1.41, trend: 'up' },
      { name: 'Priya S.', city: 'Mumbai', country: '印度', energy: 13.6, rpm: 9721, dist: 1.399, trend: 'same' },
      { name: '吴佳琪', city: '杭州', country: '中国', energy: 13.05, rpm: 9606, dist: 1.359, trend: 'down' },
      { name: 'Julia W.', city: 'Berlin', country: '德国', energy: 12.64, rpm: 9401, dist: 1.345, trend: 'same' },
      { name: '周雨桐', city: '武汉', country: '中国', energy: 12.31, rpm: 9136, dist: 1.347, trend: 'down' },
      { name: 'Sofia M.', city: 'Mexico City', country: '墨西哥', energy: 11.66, rpm: 8927, dist: 1.307, trend: 'up' },
      { name: '黄雅婷', city: '珠海', country: '中国', energy: 11.14, rpm: 8610, dist: 1.293, trend: 'same' },
      { name: 'Ingrid S.', city: 'Stockholm', country: '瑞典', energy: 10.97, rpm: 8571, dist: 1.28, trend: 'down' },
      { name: '孙若曦', city: '重庆', country: '中国', energy: 10.15, rpm: 8164, dist: 1.243, trend: 'up' },
      { name: 'Chloe T.', city: 'Toronto', country: '加拿大', energy: 9.75, rpm: 7941, dist: 1.227, trend: 'up' },
      { name: '马欣然', city: '天津', country: '中国', energy: 9.4, rpm: 7716, dist: 1.218, trend: 'same' },
      { name: 'Hannah M.', city: 'Dublin', country: '爱尔兰', energy: 9.26, rpm: 7735, dist: 1.197, trend: 'down' },
      { name: '郑诗琪', city: '西安', country: '中国', energy: 8.74, rpm: 7406, dist: 1.18, trend: 'down' },
      { name: 'Anna P.', city: 'Rome', country: '意大利', energy: 8.4, rpm: 7129, dist: 1.178, trend: 'same' },
      { name: '杨雨萱', city: '长沙', country: '中国', energy: 7.78, rpm: 6862, dist: 1.134, trend: 'same' },
      { name: 'Zoe C.', city: 'São Paulo', country: '巴西', energy: 7.49, rpm: 6585, dist: 1.138, trend: 'same' },
      { name: '何思颖', city: '厦门', country: '中国', energy: 7.24, rpm: 6541, dist: 1.107, trend: 'down' },
      { name: 'Lily Chen', city: 'San Francisco', country: '美国', energy: 6.86, rpm: 6341, dist: 1.081, trend: 'up' },
      { name: 'Elena V.', city: 'Nagoya', country: '日本', energy: 6.4, rpm: 5970, dist: 1.072, trend: 'down' },
    ],
    'female-youth': [
      { name: '赵雨萱', city: '北京', country: '中国', energy: 19.3, rpm: 11800, dist: 1.635, trend: 'down' },
      { name: 'Sakura I.', city: 'Fukuoka', country: '日本', energy: 18.87, rpm: 11598, dist: 1.627, trend: 'up' },
      { name: '李欣怡', city: '上海', country: '中国', energy: 18.78, rpm: 11667, dist: 1.609, trend: 'same' },
      { name: 'Chloe T.', city: 'Toronto', country: '加拿大', energy: 18.5, rpm: 11531, dist: 1.605, trend: 'down' },
      { name: '陈诗涵', city: '深圳', country: '中国', energy: 17.7, rpm: 11229, dist: 1.577, trend: 'same' },
      { name: 'Mia L.', city: 'Los Angeles', country: '美国', energy: 17.32, rpm: 11061, dist: 1.566, trend: 'up' },
      { name: '王思琪', city: '广州', country: '中国', energy: 16.83, rpm: 10981, dist: 1.532, trend: 'up' },
      { name: 'Sofia M.', city: 'Mexico City', country: '墨西哥', energy: 16.71, rpm: 10904, dist: 1.532, trend: 'up' },
      { name: '周雨桐', city: '成都', country: '中国', energy: 16.33, rpm: 10713, dist: 1.524, trend: 'down' },
      { name: 'Emma W.', city: 'London', country: '英国', energy: 15.55, rpm: 10468, dist: 1.485, trend: 'up' },
      { name: '林晓彤', city: '台北', country: '中国台湾', energy: 14.99, rpm: 10239, dist: 1.464, trend: 'up' },
      { name: 'Yuna K.', city: 'Seoul', country: '韩国', energy: 14.53, rpm: 9995, dist: 1.453, trend: 'down' },
      { name: '张梦瑶', city: '武汉', country: '中国', energy: 14.14, rpm: 9849, dist: 1.436, trend: 'same' },
      { name: 'Isabella R.', city: 'São Paulo', country: '巴西', energy: 13.59, rpm: 9660, dist: 1.406, trend: 'same' },
      { name: '吴佳琪', city: '杭州', country: '中国', energy: 13.17, rpm: 9411, dist: 1.4, trend: 'up' },
      { name: 'Hannah M.', city: 'Berlin', country: '德国', energy: 13.05, rpm: 9360, dist: 1.394, trend: 'same' },
      { name: '刘思涵', city: '南京', country: '中国', energy: 12.49, rpm: 9083, dist: 1.375, trend: 'same' },
      { name: 'Priya S.', city: 'Mumbai', country: '印度', energy: 11.66, rpm: 8723, dist: 1.336, trend: 'same' },
      { name: '黄雅婷', city: '珠海', country: '中国', energy: 11.37, rpm: 8670, dist: 1.311, trend: 'same' },
      { name: 'Zoe C.', city: 'Sydney', country: '澳大利亚', energy: 10.94, rpm: 8277, dist: 1.322, trend: 'same' },
      { name: '孙若曦', city: '重庆', country: '中国', energy: 10.53, rpm: 8093, dist: 1.301, trend: 'same' },
      { name: 'Elena V.', city: 'Madrid', country: '西班牙', energy: 9.88, rpm: 7900, dist: 1.25, trend: 'down' },
      { name: '郑诗琪', city: '西安', country: '中国', energy: 9.76, rpm: 7700, dist: 1.267, trend: 'same' },
      { name: 'Anna P.', city: 'Paris', country: '法国', energy: 9.39, rpm: 7600, dist: 1.236, trend: 'same' },
      { name: '马欣然', city: '天津', country: '中国', energy: 8.58, rpm: 7175, dist: 1.196, trend: 'down' },
      { name: 'Sophie L.', city: 'Dublin', country: '爱尔兰', energy: 8.28, rpm: 7013, dist: 1.181, trend: 'up' },
      { name: '杨雨萱', city: '长沙', country: '中国', energy: 8.12, rpm: 6899, dist: 1.177, trend: 'up' },
      { name: 'Lily Chen', city: 'San Francisco', country: '美国', energy: 7.44, rpm: 6506, dist: 1.143, trend: 'same' },
      { name: '何思颖', city: '厦门', country: '中国', energy: 7.05, rpm: 6309, dist: 1.117, trend: 'down' },
      { name: 'Julia W.', city: 'Rome', country: '意大利', energy: 6.78, rpm: 6048, dist: 1.121, trend: 'up' },
    ],
    'female-senior': [
      { name: '王秀兰', city: '北京', country: '中国', energy: 16.2, rpm: 11000, dist: 1.472, trend: 'same' },
      { name: 'Ingrid S.', city: 'Stockholm', country: '瑞典', energy: 16.11, rpm: 10946, dist: 1.472, trend: 'down' },
      { name: '陈淑芬', city: '上海', country: '中国', energy: 15.88, rpm: 10869, dist: 1.461, trend: 'same' },
      { name: 'Margaret B.', city: 'Melbourne', country: '澳大利亚', energy: 15.15, rpm: 10566, dist: 1.434, trend: 'up' },
      { name: '李桂芳', city: '成都', country: '中国', energy: 14.71, rpm: 10461, dist: 1.406, trend: 'up' },
      { name: 'Yoko N.', city: 'Kyoto', country: '日本', energy: 14.69, rpm: 10462, dist: 1.404, trend: 'same' },
      { name: '周玉珍', city: '杭州', country: '中国', energy: 14.04, rpm: 10241, dist: 1.371, trend: 'down' },
      { name: 'Helen M.', city: 'London', country: '英国', energy: 13.56, rpm: 9926, dist: 1.366, trend: 'same' },
      { name: '黄丽华', city: '珠海', country: '中国', energy: 13.32, rpm: 9783, dist: 1.362, trend: 'up' },
      { name: 'Maria G.', city: 'Athens', country: 'Greece', energy: 12.82, rpm: 9599, dist: 1.336, trend: 'up' },
      { name: '孙桂英', city: '重庆', country: '中国', energy: 12.78, rpm: 9577, dist: 1.334, trend: 'down' },
      { name: 'Claire D.', city: 'Paris', country: '法国', energy: 12.1, rpm: 9288, dist: 1.303, trend: 'up' },
      { name: '马秀珍', city: '天津', country: '中国', energy: 11.94, rpm: 9128, dist: 1.308, trend: 'up' },
      { name: 'Sarah K.', city: 'Sydney', country: '澳大利亚', energy: 11.41, rpm: 8999, dist: 1.268, trend: 'down' },
      { name: '郑淑芳', city: '西安', country: '中国', energy: 11.14, rpm: 8845, dist: 1.259, trend: 'same' },
      { name: 'Ana R.', city: 'Madrid', country: '西班牙', energy: 10.52, rpm: 8565, dist: 1.229, trend: 'up' },
      { name: '杨凤英', city: '长沙', country: '中国', energy: 10.51, rpm: 8495, dist: 1.237, trend: 'down' },
      { name: 'Emma L.', city: 'Dublin', country: '爱尔兰', energy: 9.95, rpm: 8285, dist: 1.201, trend: 'down' },
      { name: '何玉兰', city: '厦门', country: '中国', energy: 9.57, rpm: 8003, dist: 1.195, trend: 'down' },
      { name: 'Julia W.', city: 'Berlin', country: '德国', energy: 9.39, rpm: 7929, dist: 1.184, trend: 'down' },
      { name: '林月娥', city: '台北', country: '中国台湾', energy: 8.71, rpm: 7594, dist: 1.148, trend: 'down' },
      { name: 'Sofia M.', city: 'Mexico City', country: '墨西哥', energy: 8.43, rpm: 7342, dist: 1.148, trend: 'down' },
      { name: '刘翠花', city: '武汉', country: '中国', energy: 8.14, rpm: 7278, dist: 1.119, trend: 'same' },
      { name: 'Kim H.J.', city: 'Seoul', country: '韩国', energy: 7.63, rpm: 6971, dist: 1.095, trend: 'same' },
      { name: '赵桂芬', city: '郑州', country: '中国', energy: 7.48, rpm: 6810, dist: 1.098, trend: 'down' },
      { name: 'Priya S.', city: 'Mumbai', country: '印度', energy: 7.07, rpm: 6520, dist: 1.084, trend: 'down' },
      { name: '吴金花', city: '南京', country: '中国', energy: 6.73, rpm: 6307, dist: 1.067, trend: 'down' },
      { name: 'Chloe T.', city: 'Toronto', country: '加拿大', energy: 6.31, rpm: 6060, dist: 1.041, trend: 'up' },
      { name: '张秀珍', city: '广州', country: '中国', energy: 5.93, rpm: 5893, dist: 1.006, trend: 'up' },
      { name: 'Anna P.', city: 'Rome', country: '意大利', energy: 5.68, rpm: 5659, dist: 1.004, trend: 'same' },
    ],
  };

  const NEWS_DATA = [
    { id: 1, category: 'event', tag: '重磅活动', tagClass: 'tag-event', icon: 'fa-trophy', title: '第一届摇跑公益大赛正式启动！', excerpt: '全球首个摇跑运动公益赛事正式拉开帷幕。不限年龄、不限地域、不限身体条件，用一分钟证明你的活力！', date: '2026-04-12', featured: true,
      content: '<p>全球首个摇跑运动公益赛事——<strong>第一届摇跑公益大赛</strong>正式拉开帷幕！</p><p>本次大赛面向全球开放，不限年龄、不限地域、不限身体条件，用一分钟证明你的活力！</p><h3>🏆 赛事亮点</h3><ul><li><strong>7大竞赛组别</strong>：按性别和年龄分组，确保公平竞技</li><li><strong>60秒个人挑战</strong>：一分钟全力冲刺，争夺全球排名</li><li><strong>3v3团队PK</strong>：3人组队，3人对3人同时PK 3分钟</li><li><strong>公益使命</strong>：报名费在扣除运营成本后全部捐赠给公益基金</li></ul><p>个人报名赠送数字摇跑球和腕力球，团队赛采用RunOrb App进行组队PK。立即下载 RunOrb App，开启你的摇跑公益之旅！</p>' },
    { id: 2, category: 'announcement', tag: '平台公告', tagClass: 'tag-announcement', icon: 'fa-bullhorn', title: '霸族排行榜全新升级：活力指数正式上线', excerpt: '霸族排行榜（bazu-ranking.com）完成全面升级，以"活力指数"为核心指标。', date: '2026-04-10',
      content: '<p>霸族排行榜（bazu-ranking.com）完成全面升级，以<strong>"活力指数"（Energy Index）</strong>为核心指标，涵盖全球6大组别排名。</p><h3>📊 升级内容</h3><ul><li>新增活力指数排名维度</li><li>支持按性别、年龄分组查看</li><li>新增全球/中国/城市三级区域排名</li><li>个人中心新增历史趋势图表</li></ul><p>致力于打造最专业的摇跑运动排名平台，让每一位摇跑爱好者都能找到自己的位置。</p>' },
    { id: 3, category: 'sports', tag: '运动科普', tagClass: 'tag-sports', icon: 'fa-dumbbell', title: '什么是摇跑运动？一项适合所有人的上肢革命', excerpt: '摇跑（Gyroball Exercise）是通过手腕转动内置陀螺仪的摇跑球进行的运动。', date: '2026-04-08',
      content: '<p><strong>摇跑（Gyroball Exercise）</strong>是通过手腕转动内置陀螺仪的摇跑球进行的运动。球芯高速旋转产生离心力，通过手腕控制阻力，实现低冲击、高效率的上肢锻炼。</p><h3>🎯 核心特点</h3><ul><li>普通用户转速5000-12000 RPM</li><li>适合各年龄段人群</li><li>不受场地、天气限制</li><li>手部可动即可参与</li></ul><p>有效锻炼上肢力量、手部灵活性和心肺功能；缓解久坐带来的肩颈疲劳。</p>' },
    { id: 4, category: 'sports', tag: '运动科普', tagClass: 'tag-sports', icon: 'fa-heartbeat', title: '活力指数：量化你身体状态的全新指标', excerpt: '活力指数 = 最高转速（爆发力）× 一分钟距离（耐力）。', date: '2026-04-06',
      content: '<p><strong>活力指数（Energy Index）</strong>是一个综合性的身体活力评估指标。</p><h3>📐 计算公式</h3><p>活力指数 = 最高转速（爆发力）× 一分钟距离（耐力）</p><p>不同于传统体检指标的局部性，活力指数综合评估了上肢爆发力和耐力两个维度。</p><h3>📊 参考标准</h3><ul><li>入门级：活力指数 5-10</li><li>进阶级：活力指数 10-20</li><li>专业级：活力指数 20+</li></ul>' },
    { id: 5, category: 'sports', tag: '健康研究', tagClass: 'tag-sports', icon: 'fa-flask', title: 'PubMed研究证实：陀螺球训练有效改善腕部疼痛与耐力', excerpt: '发表在PubMed上的临床研究表明，使用Powerball陀螺球进行训练效果显著。', date: '2026-04-03',
      content: '<p>发表在<strong>PubMed</strong>上的临床研究表明，使用Powerball陀螺球进行训练，能显著改善非特异性腕部疼痛患者的疼痛程度和握力耐力。</p><h3>🔬 研究要点</h3><ul><li>训练组疼痛程度显著降低</li><li>握力耐力明显提升</li><li>无不良反应报告</li><li>为摇跑运动的健康价值提供了科学依据</li></ul>' },
    { id: 6, category: 'ranking', tag: '排名热点', tagClass: 'tag-ranking', icon: 'fa-crown', title: '世界纪录保持者：希腊选手Akis创造17,015 RPM惊人成绩', excerpt: '来自希腊的Akis Kritsinelis创造了17,015 RPM的惊人世界纪录。', date: '2026-04-01',
      content: '<p>来自希腊的<strong>Akis Kritsinelis</strong>在Powerball世界锦标赛中创造了<strong>17,015 RPM</strong>的惊人世界纪录，这一成绩至今无人打破。</p><h3>🏅 纪录详情</h3><ul><li>最高转速：17,015 RPM</li><li>活力指数：28.52</li><li>是目前已知的人类摇跑运动巅峰</li></ul><p>这一纪录激励着全球摇跑爱好者不断挑战自我。</p>' },
    { id: 7, category: 'sports', tag: '运动科普', tagClass: 'tag-sports', icon: 'fa-users', title: '摇跑运动的独特优势：对残疾人极其友好', excerpt: '摇跑运动只需要一只手即可完成，真正实现"运动无障碍"。', date: '2026-03-28',
      content: '<p>摇跑运动只需要<strong>一只手</strong>即可完成，对场地无要求，不受天气影响。</p><h3>♿ 无障碍特性</h3><ul><li>上肢残疾：单手即可完成</li><li>下肢残疾：无需站立或行走</li><li>视力障碍：通过App语音播报数据</li><li>老年人群：低冲击，安全无风险</li></ul><p>真正实现"运动无障碍"。</p>' },
    { id: 8, category: 'announcement', tag: '平台公告', tagClass: 'tag-announcement', icon: 'fa-mobile-alt', title: 'RunOrb App V1.2.2.11发布：全新活力指数功能', excerpt: 'RunOrb App最新版本正式发布，新增活力指数功能模块。', date: '2026-03-25',
      content: '<p>RunOrb App最新版本<strong>V1.2.2.11</strong>正式发布！</p><h3>✨ 新增功能</h3><ul><li>活力指数（Energy Index）功能模块</li><li>实时记录最高转速、一分钟距离和活力指数</li><li>历史趋势图表</li><li>全球排行榜实时同步</li></ul><p>让每一次训练都有据可查，下载更新体验全新功能！</p>' },
    { id: 9, category: 'ranking', tag: '排名热点', tagClass: 'tag-ranking', icon: 'fa-globe-asia', title: '全球腕力球市场规模突破9.5亿美元', excerpt: '据Cognitive Market Research报告，全球腕力球市场规模持续增长。', date: '2026-03-20',
      content: '<p>据<strong>Cognitive Market Research</strong>报告，全球腕力球（Wrist Ball）市场规模从2021年的7亿美元增长至2025年的<strong>9.5亿美元</strong>，年复合增长率超过8%。</p><h3>📈 市场趋势</h3><ul><li>亚太地区增长最快</li><li>健康意识提升推动需求</li><li>智能设备融合带来新机遇</li></ul>' },
    { id: 10, category: 'event', tag: '活动资讯', tagClass: 'tag-event', icon: 'fa-handshake', title: '公益大赛冠名赞助商招募中', excerpt: '第一届摇跑公益大赛面向全球招募冠名赞助商和团队赞助商。', date: '2026-03-18',
      content: '<p>第一届摇跑公益大赛面向全球招募<strong>冠名赞助商</strong>和<strong>团队赞助商</strong>。</p><h3>🤝 赞助权益</h3><ul><li>品牌冠名曝光</li><li>赛事现场展示</li><li>媒体报道覆盖</li><li>公益捐赠证书</li></ul><p>我们诚邀热心公益的运动品牌、科技企业和个人参与，共同推动这项全民运动的发展。</p>' },
    { id: 11, category: 'sports', tag: '运动科普', tagClass: 'tag-sports', icon: 'fa-book-open', title: '百度百科正式收录"摇跑运动"词条', excerpt: '百度百科已正式收录"摇跑运动"词条，标志着获得了权威认可。', date: '2026-03-15',
      content: '<p>百度百科已正式收录<strong>"摇跑运动"</strong>词条，标志着这项由珠海霸族健康管理有限公司与深圳市礼上科技有限公司联合推动的新兴运动获得了权威认可。</p><p>词条详细介绍了摇跑运动的定义、起源、锻炼方法、适用人群和健康价值，为公众了解这项运动提供了权威的信息来源。</p>' },
    { id: 12, category: 'sports', tag: '训练指南', tagClass: 'tag-sports', icon: 'fa-graduation-cap', title: '从零开始：5分钟学会摇跑运动', excerpt: '摇跑运动入门非常简单，三步即可开始。', date: '2026-03-12',
      content: '<p>摇跑运动入门非常简单，只需三步：</p><h3>1️⃣ 启动球芯</h3><p>握住摇跑球，用拇指或拉绳启动球芯旋转。</p><h3>2️⃣ 转动手腕</h3><p>顺时针或逆时针转动手腕，保持球芯加速。</p><h3>3️⃣ 坚持1分钟</h3><p>坚持1分钟，记录你的最高转速和距离。就这么简单！</p><p>下载 RunOrb App，连接蓝牙设备，开始你的第一次摇跑测试吧！</p>' },
  ];

  // ===================== DOM Helpers =====================
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => document.querySelectorAll(sel);

  function showToast(message, type = 'info') {
    const container = $('#toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const iconMap = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
    toast.innerHTML = `<i class="fas ${iconMap[type] || iconMap.info}"></i><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(40px)'; setTimeout(() => toast.remove(), 300); }, 3000);
  }

  // ===================== Navigation =====================
  function navigateTo(page, section) {
    state.currentPage = page;
    $$('.page').forEach(p => p.classList.remove('active'));
    const pageEl = $(`#page-${page}`);
    if (pageEl) pageEl.classList.add('active');
    // Update nav active state - match exact page or 'home' for leaderboard nav
    $$('.nav-item').forEach(n => {
      const navPage = n.dataset.page;
      const navSection = n.dataset.section;
      const isActive = (navPage === page && !navSection) ||
                      (navPage === page && navSection === section) ||
                      (page === 'home' && navPage === 'home');
      n.classList.toggle('active', isActive);
    });
    // Close mobile nav
    $('#mainNav').classList.remove('open');
    // Scroll to section if specified
    if (section) {
      const el = document.getElementById('section-' + section);
      if (el) {
        // Handle about-tab-content: show the target, hide siblings
        if (el.classList.contains('about-tab-content')) {
          el.closest('.page')?.querySelectorAll('.about-tab-content').forEach(s => {
            s.style.display = s === el ? '' : 'none';
          });
        }
        setTimeout(() => {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
        return;
      }
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
    // Render page-specific content
    if (page === 'leaderboard') renderLeaderboard();
    if (page === 'profile') renderProfile();
    if (page === 'news') renderNews();
  }

  // ===================== Tab System =====================
  function setupTabs(containerId, callback) {
    const container = $(containerId);
    if (!container) return;
    container.addEventListener('click', (e) => {
      const btn = e.target.closest('.tab-btn');
      if (!btn) return;
      container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      callback(btn.dataset.tab);
    });
  }

  // ===================== Leaderboard Rendering =====================
  function renderLeaderboard() {
    if (state.mainTab === 'personal') {
      loadMyRank();
      renderPersonalTable();
    } else {
      renderGroupTable();
    }
  }

  // Load and display user's personal ranking
  async function loadMyRank() {
    const banner = $('#myRankBanner');
    if (!banner) return;
    if (!state.currentUser) {
      banner.style.display = 'none';
      return;
    }
    try {
      const resp = await fetch(`/api/records?action=my-rank&userId=${state.currentUser.id}`);
      const data = await resp.json();
      if (!data.success || !data.ranks.energy) {
        banner.style.display = 'none';
        return;
      }
      const r = data.ranks;
      const b = data.best;
      const metricNames = { energy: '活力指数', rpm: '爆发力', dist: '耐力' };
      const regionNames = { global: '全球', country: state.currentUser.country || '本国', city: state.currentUser.city || '本市' };
      const regionOrder = ['global', 'country', 'city'];

      const genderMap = { male: '男', female: '女' };
      const ageMap = { youth: '少年组', adult: '成年组', senior: '长青组' };
      const genderLabel = genderMap[state.currentUser.gender] || state.currentUser.gender;
      const ageLabel = ageMap[state.currentUser.ageGroup] || state.currentUser.ageGroup;
      const groupLabel = `${genderLabel} ${ageLabel}`;

      let html = `<span class="rank-label"><i class="fas fa-user"></i> ${state.currentUser.nickname || state.currentUser.username}（${groupLabel}）的排名：</span>`;
      for (const metric of ['energy', 'rpm', 'dist']) {
        const parts = regionOrder.map(reg => {
          const rank = r[metric]?.[reg];
          return rank != null ? `<span class="rank-region">${regionNames[reg]} #${rank}</span>` : '';
        }).filter(Boolean).join(' / ');
        if (parts) {
          html += `<span class="rank-item">${metricNames[metric]}: ${parts}</span>`;
        }
      }
      banner.innerHTML = html;
      banner.style.display = 'flex';
    } catch (e) {
      banner.style.display = 'none';
    }
  }

  let leaderboardCache = {};  // key: "group-tab-sort-country-city" -> { data, total }

  async function fetchLeaderboard() {
    const group = state.group;
    const sortKey = state.personalTab === 'energy' ? 'energy' : state.personalTab === 'burst' ? 'rpm' : 'dist';
    const cacheKey = `${group}-${sortKey}-${state.regionTab}`;
    const now = Date.now();
    const cached = leaderboardCache[cacheKey];
    if (cached && now - cached._ts < 60000) return cached; // 60s cache

    try {
      let apiUrl = `/api/records?group=${encodeURIComponent(group)}&page=1&pageSize=200&sort=${sortKey}`;
      if (state.regionTab === 'china') {
        apiUrl += '&country=\u4e2d\u56fd';
      } else if (state.regionTab === 'city') {
        const userCountry = state._userCountry || '\u4e2d\u56fd';
        apiUrl += '&country=' + encodeURIComponent(userCountry);
        if (state._userCity) {
          apiUrl += '&city=' + encodeURIComponent(state._userCity);
        }
      }
      const resp = await fetch(apiUrl);
      const data = await resp.json();
      if (!data.success) {
        return { data: [], total: 0 };
      }

      let rows = (data.leaderboard || []).map((r, i) => ({
        rank: i + 1,
        name: r.name || r.nickname || r.username,
        city: r.city || '-',
        country: r.country || '\u4e2d\u56fd',
        avatar: (r.name || r.nickname || r.username)[0],
        color: r.avatar_color || AVATAR_COLORS[i % AVATAR_COLORS.length],
        energy: r.energy != null ? Number(r.energy) : 0,
        rpm: r.rpm != null ? Number(r.rpm) : 0,
        dist: r.dist != null ? (Number(r.dist) > 3 ? Number(r.dist) / 100 : Number(r.dist)) : 0,
        trend: r.trend || 'same',
      }));

      // Fallback to SEED_DATA when API returns empty
      if (rows.length === 0 && SEED_DATA[group]) {
        let seedRows = SEED_DATA[group].map((r, i) => ({
          rank: i + 1,
          name: r.name,
          city: r.city,
          country: r.country,
          avatar: r.name[0],
          color: AVATAR_COLORS[i % AVATAR_COLORS.length],
          energy: r.energy,
          rpm: r.rpm,
          dist: r.dist,
          trend: r.trend || 'same',
        }));

        // Filter by region when using seed data
        if (state.regionTab === 'china') {
          seedRows = seedRows.filter(r => r.country === '中国');
        } else if (state.regionTab === 'city') {
          const userCountry = state._userCountry || '中国';
          seedRows = seedRows.filter(r => r.country === userCountry);
          if (state._userCity) {
            seedRows = seedRows.filter(r => r.city && r.city.startsWith(state._userCity));
          }
          // If no city set, show all users in the country (same as china tab)
        }

        rows = seedRows;
      }

      rows.sort((a, b) => b[sortKey] - a[sortKey]);
      rows.forEach((d, i) => d.rank = i + 1);

      const result = { data: rows, total: rows.length, _ts: now };
      leaderboardCache[cacheKey] = result;
      return result;
    } catch (e) {
      console.warn('Leaderboard fetch error:', e.message);
      return { data: [], total: 0 };
    }
  }


  async function renderPersonalTable() {
    const headerMap = { energy: t('thEnergy'), burst: t('thBurst'), endurance: t('thEndurance') };
    const keyMap = { energy: 'energy', burst: 'rpm', endurance: 'dist' };
    $('#personalValueHeader').textContent = headerMap[state.personalTab];
    const key = keyMap[state.personalTab];

    // Show loading state
    const tbodyEl = $('#personalTableBody');
    if (tbodyEl) tbodyEl.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-secondary)"><i class="fas fa-spinner fa-spin" style="font-size:24px;margin-bottom:12px;display:block"></i>加载中...</td></tr>';

    const { data, total } = await fetchLeaderboard();

    // Auto-jump to the page containing the logged-in user
    if (state.currentUser && !state._pageJumped) {
      const myIdx = data.findIndex(d => d.name === (state.currentUser.nickname || state.currentUser.username));
      if (myIdx >= 0) {
        state.personalPage = Math.floor(myIdx / state.pageSize) + 1;
        state._pageJumped = true;
      }
    }

    // Auto-detect: if current group has no data, try to find a group with data
    if (data.length === 0 && !state._autoDetected) {
      state._autoDetected = true;
      const groups = ['male-youth','male-adult','male-senior','female-youth','female-adult','female-senior'];
      for (const g of groups) {
        if (g === state.group) continue;
        const testUrl = `/api/records?group=${encodeURIComponent(g)}&page=1&pageSize=1`;
        try {
          const resp = await fetch(testUrl);
          const d = await resp.json();
          if (d.success && d.total > 0) {
            state.group = g;
            $('#groupSelect').value = g;
            leaderboardCache = {};
            return renderPersonalTable();
          }
        } catch(e) {}
      }
    }

    // Region filter - data already filtered by API for china/global
    let filtered = data;
    // No client-side filtering needed - API handles country filter

    const start = (state.personalPage - 1) * state.pageSize;
    const pageData = filtered.slice(start, start + state.pageSize);

    const tbody = $('#personalTableBody');
    if (pageData.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--text-secondary)">
        <i class="fas fa-trophy" style="font-size:32px;margin-bottom:12px;display:block;opacity:0.3"></i>
        ${t('noData') || '暂无数据，快来提交你的第一条成绩吧！'}
      </td></tr>`;
      $('#personalPagination').innerHTML = '';
      return;
    }

    tbody.innerHTML = pageData.map(d => {
      const rankClass = d.rank <= 3 ? `rank-${d.rank}` : 'rank-default';
      const isMe = state.currentUser && d.name === (state.currentUser.nickname || state.currentUser.username);
      const rowStyle = isMe ? 'background:rgba(99,102,241,0.1);font-weight:600;' : '';
      const trendIcon = d.trend === 'up' ? '<i class="fas fa-arrow-up trend-up"></i>' :
                        d.trend === 'down' ? '<i class="fas fa-arrow-down trend-down"></i>' :
                        '<i class="fas fa-minus trend-same"></i>';
      const value = key === 'energy' ? Number(d[key]).toFixed(2) :
                    key === 'dist' ? Number(d[key]).toFixed(3) :
                    Number(d[key]).toLocaleString();
      return `<tr style="${rowStyle}">
        <td><span class="rank-badge ${rankClass}">${d.rank}</span></td>
        <td>
          <div class="user-cell">
            <div class="user-avatar" style="background:${d.color}">${d.avatar}</div>
            <div>
              <div class="user-name">${d.name}</div>
              <div class="user-city">${translateName(d.city && d.city !== '-' ? d.city : d.country)}</div>
            </div>
          </div>
        </td>
        <td><span class="value-cell">${value}</span></td>
        <td>${trendIcon}</td>
      </tr>`;
    }).join('');

    // Pagination with smart ellipsis
    const totalPages = Math.ceil(filtered.length / state.pageSize);
    const pag = $('#personalPagination');
    if (totalPages <= 1) { pag.innerHTML = ''; return; }
    const cur = state.personalPage;
    let pages = [];
    if (totalPages <= 7) {
      for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
      pages.push(1);
      if (cur > 3) pages.push('...');
      for (let i = Math.max(2, cur - 1); i <= Math.min(totalPages - 1, cur + 1); i++) pages.push(i);
      if (cur < totalPages - 2) pages.push('...');
      pages.push(totalPages);
    }
    let pagHtml = '';
    for (const p of pages) {
      if (p === '...') {
        pagHtml += '<span class="page-btn" style="cursor:default;border:none;background:none;color:var(--text-secondary)">…</span>';
      } else {
        pagHtml += `<button class="page-btn ${p === cur ? 'active' : ''}" data-page="${p}">${p}</button>`;
      }
    }
    pag.innerHTML = pagHtml;
    pag.querySelectorAll('.page-btn[data-page]').forEach(btn => {
      btn.addEventListener('click', () => {
        state.personalPage = parseInt(btn.dataset.page);
        renderPersonalTable();
      });
    });
  }

  async function renderGroupTable() {
    const thead = $('#groupTableHead');
    const tbody = $('#groupTableBody');

    if (state.groupTab === 'brand') {
      // Brand ranking - no real data yet, show placeholder
      thead.innerHTML = `<tr><th style="width:60px">排名</th><th>品牌</th><th>参与人数</th><th>数据上传量</th><th>平均活力指数</th><th>活力分值</th></tr>`;
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-secondary)">
        <i class="fas fa-shoe-prints" style="font-size:32px;margin-bottom:12px;display:block;opacity:0.3"></i>
        品牌排名功能即将上线，敬请期待
      </td></tr>`;
    } else if (state.groupTab === 'city') {
      thead.innerHTML = `<tr><th style="width:60px">${t('thRank')}</th><th>${t('tabCity')}</th><th>${t('thParticipants') || '参与人数'}</th><th>${t('headerEnergy')}</th><th>${t('thMaxEnergy') || '最高活力指数'}</th><th>${t('thCityScore') || '城市活力分'}</th></tr>`;
      try {
        const resp = await fetch('/api/records?action=group-stats');
        const data = await resp.json();
        if (data.success && data.cities && data.cities.length > 0) {
          const maxScore = data.cities[0].avg_energy || 1;
          tbody.innerHTML = data.cities.map((c, i) => {
            const score = Math.round(c.avg_energy * 0.7 + c.users * 5);
            const pct = Math.round((c.avg_energy / maxScore) * 100);
            return `<tr>
              <td><span class="rank-badge ${i < 3 ? `rank-${i+1}` : 'rank-default'}">${i+1}</span></td>
              <td><div class="group-name-cell"><div class="city-icon"><i class="fas fa-city"></i></div><div><div class="group-name">${translateName(c.city)}</div><div class="group-sub">${c.users} ${currentLang === 'zh' ? '名参与者' : 'participants'}</div></div></div></td>
              <td>${c.users}</td>
              <td><span class="value-cell">${Number(c.avg_energy).toFixed(2)}</span></td>
              <td><span class="value-cell">${Number(c.max_energy).toFixed(2)}</span></td>
              <td><div style="display:flex;align-items:center;gap:8px"><span style="font-weight:600">${score}</span><div class="score-bar"><div class="score-bar-fill" style="width:${pct}%"></div></div></div></td>
            </tr>`;
          }).join('');
        } else {
          tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-secondary)">
            <i class="fas fa-city" style="font-size:32px;margin-bottom:12px;display:block;opacity:0.3"></i>
            ${t('noData')}
          </td></tr>`;
        }
      } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-secondary)">${t('loadFailed') || '加载失败'}</td></tr>`;
      }
    } else {
      // Country ranking - not applicable yet
      thead.innerHTML = `<tr><th style="width:60px">${t('thRank')}</th><th>${t('tabCountry') || '国家'}</th><th>${t('thParticipants') || '参与人数'}</th><th>${t('headerEnergy')}</th><th>${t('thComposite') || '综合活力指数'}</th></tr>`;
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-secondary)">
        <i class="fas fa-globe" style="font-size:32px;margin-bottom:12px;display:block;opacity:0.3"></i>
        ${currentLang === 'zh' ? '全球排名功能即将上线，敬请期待' : 'Global ranking coming soon'}
      </td></tr>`;
    }
  }

  // ===================== Profile =====================
  let historyChart = null;

  function renderProfile() {
    const user = state.currentUser;
    if (user) {
      syncProfileDisplay(user);
      // Fill edit form
      $('#editCountry').value = user.country || '中国';
      populateCities(user.country || '中国', user.city);
      $('#editGender').value = user.gender || 'male';
      $('#editAgeGroup').value = user.ageGroup || 'adult';
      // Load user's best records from API, default to 0
      loadUserStats(user.id);
    } else {
      // Not logged in
      $('#profileAvatar').textContent = '';
      $('#profileAvatar').style.background = '#1e2a3a';
      $('#profileName').textContent = '';
      $('#profileGroup').textContent = '';
      $('#profileCountry').textContent = '-';
      $('#profileCity').textContent = '-';
      $('#profileGender').textContent = '-';
      $('#profileAgeGroup').textContent = '-';
      $('#profileJoined').textContent = '-';
      $('#statEnergy').textContent = '0';
      $('#statRPM').textContent = '0';
      $('#statDist').textContent = '0';
    }
    renderHistoryChart();
  }

  // Sync sidebar display from user data
  function syncProfileDisplay(user) {
    const genderMap = { male: '男', female: '女' };
    const ageMap = { teen: '10-17岁 少年组', adult: '18-59岁 成年组', senior: '60岁+ 长青组' };
    const name = user.nickname || user.username || '';
    $('#profileAvatar').textContent = name ? name[0] : '';
    $('#profileAvatar').style.background = user.avatarColor || '#FF6B35';
    $('#profileName').textContent = name;
    $('#profileGroup').textContent = `${genderMap[user.gender] || '男'} ${ageMap[user.ageGroup] || '18-59岁 成年组'}`;
    $('#profileCountry').textContent = translateName(user.country || '-');
    $('#profileCity').textContent = translateName(user.city || '-');
    $('#profileGender').textContent = genderMap[user.gender] || '-';
    $('#profileAgeGroup').textContent = ageMap[user.ageGroup] || '-';
    $('#profileJoined').textContent = (user.created_at || user.createdAt || '').slice(0, 10) || '-';
  }

  // Populate city dropdown based on country (flat list for mobile compatibility)
  function populateCities(country, selectedCity) {
    const citySelect = $('#editCity');
    const placeholder = I18N[currentLang]?.selectCity || '请选择城市';
      let html = `<option value="">${placeholder}</option>`;

    if (typeof CITIES_BY_COUNTRY !== 'undefined' && CITIES_BY_COUNTRY[country]) {
      const cities = CITIES_BY_COUNTRY[country];
      for (const c of cities) {
        html += `<option value="${c}" ${c === selectedCity ? 'selected' : ''}>${translateName(c)}</option>`;
      }
    }
    citySelect.innerHTML = html;
  }


  // Live preview: sync sidebar as user changes edit form
  function setupLivePreview() {
    const countrySelect = $('#editCountry');
    const citySelect = $('#editCity');
    const genderSelect = $('#editGender');
    const ageGroupSelect = $('#editAgeGroup');

    // Country change -> update city list, keep current city if available
    countrySelect.addEventListener('change', () => {
      const prevCity = citySelect.value;
      populateCities(countrySelect.value, prevCity);
      // If previous city is still in the new list, keep it; otherwise clear
      if (citySelect.value !== prevCity) {
        citySelect.value = '';
      }
      updatePreview();
    });

    function updatePreview() {
      const user = state.currentUser || {};
      syncProfileDisplay({
        ...user,
        country: countrySelect.value,
        city: citySelect.value,
        gender: genderSelect.value,
        ageGroup: ageGroupSelect.value,
      });
    }

    citySelect.addEventListener('change', updatePreview);
    genderSelect.addEventListener('change', updatePreview);
    ageGroupSelect.addEventListener('change', updatePreview);
  }

  async function loadUserStats(userId) {
    try {
      const resp = await fetch(`/api/records?action=my-best&userId=${userId}`);
      const data = await resp.json();
      if (data.success && data.best) {
        $('#statEnergy').textContent = data.best.energy != null ? Number(data.best.energy).toFixed(2) : '0.00';
        $('#statRPM').textContent = data.best.rpm != null ? Number(data.best.rpm).toLocaleString() : '0';
        $('#statDist').textContent = data.best.distance != null ? (Number(data.best.distance) > 3 ? (Number(data.best.distance) / 100).toFixed(3) : Number(data.best.distance).toFixed(3)) : '0.000';
      } else {
        $('#statEnergy').textContent = '0';
        $('#statRPM').textContent = '0';
        $('#statDist').textContent = '0';
      }
    } catch (e) {
      $('#statEnergy').textContent = '0';
      $('#statRPM').textContent = '0';
      $('#statDist').textContent = '0';
    }
  }

  async function renderHistoryChart() {
    const ctx = $('#historyChart');
    if (!ctx) return;
    if (historyChart) historyChart.destroy();

    // Fetch real history from API
    let labels = [], energyData = [], rpmData = [], distData = [];

    if (state.currentUser) {
      try {
        const resp = await fetch(`/api/records?action=my-history&userId=${state.currentUser.id}`);
        const data = await resp.json();
        if (data.success && data.records && data.records.length > 0) {
          // Sort by date ascending
          const records = data.records.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
          labels = records.map(r => (r.created_at || '').slice(5, 10)); // MM-DD
          energyData = records.map(r => r.energy != null ? Number(r.energy).toFixed(2) : 0);
          rpmData = records.map(r => r.rpm != null ? Number(r.rpm) : 0);
          distData = records.map(r => r.distance != null ? (Number(r.distance) > 3 ? (Number(r.distance) / 100).toFixed(3) : Number(r.distance).toFixed(3)) : 0);
        }
      } catch (e) { /* ignore */ }
    }

    if (labels.length === 0) {
      // No data - show empty chart
      labels = ['暂无数据'];
      energyData = [0];
      rpmData = [0];
      distData = [0];
    }

    historyChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: '活力指数',
            data: energyData,
            borderColor: '#FF6B35',
            backgroundColor: 'rgba(255,107,53,0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#FF6B35',
            yAxisID: 'y',
          },
          {
            label: '最高转速 (RPM)',
            data: rpmData,
            borderColor: '#004E89',
            backgroundColor: 'rgba(0,78,137,0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#004E89',
            yAxisID: 'y1',
          },
          {
            label: '一分钟里程 (km)',
            data: distData,
            borderColor: '#1A936F',
            backgroundColor: 'rgba(26,147,111,0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#1A936F',
            yAxisID: 'y1',
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            labels: { color: '#8892A4', font: { size: 12 }, usePointStyle: true, pointStyle: 'circle' },
          },
          tooltip: {
            backgroundColor: '#151D30',
            titleColor: '#E8ECF1',
            bodyColor: '#8892A4',
            borderColor: '#1E2A45',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
          },
        },
        scales: {
          x: {
            ticks: { color: '#5A6478' },
            grid: { color: 'rgba(30,42,69,0.5)' },
          },
          y: {
            type: 'linear',
            position: 'left',
            ticks: { color: '#FF6B35' },
            grid: { color: 'rgba(30,42,69,0.5)' },
            title: { display: true, text: '活力指数', color: '#FF6B35' },
          },
          y1: {
            type: 'linear',
            position: 'right',
            ticks: { color: '#004E89' },
            grid: { drawOnChartArea: false },
            title: { display: true, text: 'RPM / 里程(km)', color: '#004E89' },
          },
        },
      },
    });
  }

  function updateGroupLabel() {
    const g = state.group;
    const genderMap = { male: '男性', female: '女性' };
    const ageMap = { teen: '10-17岁 少年组', adult: '18-59岁 成年组', senior: '60岁+ 长青组' };
    const parts = g.split('-');
    return `${genderMap[parts[0]]} ${ageMap[parts[1]]}`;
  }

  // ===================== News =====================
  function renderNews() {
    const filtered = state.newsFilter === 'all' ? NEWS_DATA : NEWS_DATA.filter(n => n.category === state.newsFilter);
    const grid = $('#newsGrid');
    grid.innerHTML = filtered.map(n => `
      <div class="news-card ${n.featured ? 'news-card-featured' : ''}" style="cursor:pointer" onclick="showNewsDetail(${n.id})">
        <div class="news-card-img"><i class="fas ${n.icon}"></i></div>
        <div class="news-card-body">
          <span class="news-card-tag ${n.tagClass}">${n.tag}</span>
          <div class="news-card-title">${n.title}</div>
          <div class="news-card-excerpt">${n.excerpt}</div>
          <div class="news-card-meta">
            <span><i class="fas fa-calendar-alt"></i> ${n.date}</span>
            <span>${currentLang === 'zh' ? '阅读全文' : 'Read more'} <i class="fas fa-arrow-right"></i></span>
          </div>
        </div>
      </div>
    `).join('');
  }

  function showNewsDetail(newsId) {
    const article = NEWS_DATA.find(n => n.id === newsId);
    if (!article) return;

    const existing = document.getElementById('newsDetailModal');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.id = 'newsDetailModal';
    overlay.style.zIndex = '10000';
    overlay.innerHTML = `
      <div class="modal" style="width:90vw;max-width:720px;max-height:85vh;overflow-y:auto;padding:0;border-radius:16px">
        <div style="position:sticky;top:0;display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:var(--card-bg);border-bottom:1px solid var(--border);z-index:1">
          <span class="news-card-tag ${article.tagClass}" style="margin:0">${article.tag}</span>
          <button onclick="document.getElementById('newsDetailModal').remove()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-secondary);padding:4px 8px"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:24px 20px">
          <h2 style="margin:0 0 12px;font-size:22px;line-height:1.4">${article.title}</h2>
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;color:var(--text-secondary);font-size:13px">
            <span><i class="fas fa-calendar-alt"></i> ${article.date}</span>
            <span><i class="fas fa-tag"></i> ${article.tag}</span>
          </div>
          <div style="font-size:15px;line-height:1.8;color:var(--text-primary)">
            ${article.content || article.excerpt}
          </div>
        </div>
      </div>`;
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
  }

  // ===================== Auth Modal =====================
  function openAuthModal() {
    $('#authModal').classList.add('active');
  }

  // ===================== APK Download =====================
  function startAPKDownload(channel) {
    const urls = {
      1: 'https://api.runorb.us/app/V1.2.2.13.apk',
      2: 'https://api.runorb.us/app/V1.2.2.13.apk',
    };
    const url = urls[channel] || urls[1];
    window.open(url, '_blank');
  }

  function closeAuthModal() {
    $('#authModal').classList.remove('active');
  }

  // ===================== Sub-Nav Bars =====================
  function addSubNavBars() {
    const pages = document.querySelectorAll('.page');
    pages.forEach(page => {
      if (page.id === 'page-home') return; // no sub-nav on home
      if (page.querySelector('.sub-nav-bar')) return; // already added
      const bar = document.createElement('div');
      bar.className = 'sub-nav-bar';
      bar.style.cssText = 'display:flex;align-items:center;gap:12px;padding:12px 20px;background:var(--card-bg,#fff);border-bottom:1px solid var(--border,rgba(0,0,0,0.08));position:sticky;top:56px;z-index:100';
      bar.innerHTML = `
        <button onclick="history.back();return false" style="display:flex;align-items:center;gap:6px;padding:8px 16px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap">
          <i class="fas fa-arrow-left"></i> 返回
        </button>
        <button onclick="navigateTo('home')" style="display:flex;align-items:center;gap:6px;padding:8px 16px;background:var(--secondary);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap">
          <i class="fas fa-home"></i> 首页
        </button>
      `;
      page.insertBefore(bar, page.firstChild);
    });
  }

  // ===================== Event Listeners =====================
  function init() {
    // Add sub-nav bar to all non-home pages
    addSubNavBars();

    // Restore session from localStorage
    const savedUser = localStorage.getItem('yaopao_user');
    const savedToken = localStorage.getItem('yaopao_token');
    if (savedUser && savedToken) {
      try {
        state.currentUser = JSON.parse(savedUser);
        state.token = savedToken;
        state.isLoggedIn = true;
        state._userCountry = state.currentUser.country || '';
        state._userCity = state.currentUser.city || '';
        $('#btnLogin').innerHTML = `<i class="fas fa-user-circle"></i> <span>${state.currentUser.nickname || state.currentUser.username}</span>`;
        if (state.currentUser.role === 'admin') {
          $('#btnLogin').innerHTML += ` <a href="/admin.html" style="color:var(--primary);font-size:12px;margin-left:4px">[管理]</a>`;
        }
      } catch (e) { /* ignore parse errors */ }
    }

    // Navigation
    $$('.nav-item').forEach(item => {
      item.addEventListener('click', (e) => {
        if (!item.dataset.page) return; // skip non-page nav items (e.g. lang toggle)
        e.preventDefault();
        navigateTo(item.dataset.page, item.dataset.section || null);
      });
    });

    // Footer links
    $$('.footer-links a').forEach(a => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        navigateTo('about');
      });
    });

    // Footer column links (new footer)
    $$('.footer-col a[data-page]').forEach(a => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        if (a.dataset.page) navigateTo(a.dataset.page, a.dataset.section || null);
      });
    });

    // Footer language switch
    const footerLangZh = $('#footerLangZh');
    const footerLangEn = $('#footerLangEn');
    const footerLangAr = $('#footerLangAr');
    const footerLangJa = $('#footerLangJa');
    const allLangBtns = [footerLangZh, footerLangEn, footerLangAr, footerLangJa];
    function highlightLang(active) {
      allLangBtns.forEach(b => { if (b) b.style.color = ''; });
      if (active) active.style.color = 'var(--primary)';
    }
    if (footerLangZh) {
      footerLangZh.addEventListener('click', (e) => { e.preventDefault(); setLang('zh'); highlightLang(footerLangZh); });
    }
    if (footerLangEn) {
      footerLangEn.addEventListener('click', (e) => { e.preventDefault(); setLang('en'); highlightLang(footerLangEn); });
    }
    if (footerLangAr) {
      footerLangAr.addEventListener('click', (e) => { e.preventDefault(); setLang('ar'); highlightLang(footerLangAr); });
    }
    if (footerLangJa) {
      footerLangJa.addEventListener('click', (e) => { e.preventDefault(); setLang('ja'); highlightLang(footerLangJa); });
    }
    // Highlight current lang on load
    const langBtnMap = { zh: footerLangZh, en: footerLangEn, ar: footerLangAr, ja: footerLangJa };
    highlightLang(langBtnMap[currentLang]);

    // Share buttons
    const shareUrl = encodeURIComponent(window.location.href);
    const shareText = encodeURIComponent('RunOrb 全球活力指数排行榜 - 来挑战你的活力指数！');
    $('#shareWeChat')?.addEventListener('click', () => {
      showToast('请复制链接后在微信中打开分享', 'info');
      navigator.clipboard?.writeText(window.location.href);
    });
    $('#shareTikTok')?.addEventListener('click', () => {
      window.open(`https://www.tiktok.com/`, '_blank');
    });
    $('#shareYouTube')?.addEventListener('click', () => {
      window.open(`https://www.youtube.com/`, '_blank');
    });
    $('#shareCopy')?.addEventListener('click', () => {
      navigator.clipboard?.writeText(window.location.href).then(() => {
        showToast('链接已复制！', 'success');
      });
    });

    // Last update time
    const now = new Date();
    const timeStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
    const lastUpdateEl = $('#lastUpdateTime');
    if (lastUpdateEl) lastUpdateEl.textContent = timeStr;

    // Quick entry buttons
    $('#btnGlobalRanking')?.addEventListener('click', (e) => {
      e.preventDefault();
      // Switch to personal tab and global region
      const personalTab = document.querySelector('#mainTabs [data-tab="personal"]');
      if (personalTab) personalTab.click();
      const globalRegion = document.querySelector('#personalTabs [data-tab="global"]');
      if (globalRegion) globalRegion.click();
      document.querySelector('.lb-quick-entries')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    $('#btnAgeGroup')?.addEventListener('click', (e) => {
      e.preventDefault();
      // Scroll to group selector
      const groupSelector = document.querySelector('.group-selector');
      if (groupSelector) {
        groupSelector.scrollIntoView({ behavior: 'smooth', block: 'center' });
        groupSelector.style.boxShadow = '0 0 0 2px var(--primary)';
        setTimeout(() => { groupSelector.style.boxShadow = ''; }, 2000);
      }
    });
    $('#btnRules')?.addEventListener('click', (e) => {
      e.preventDefault();
      navigateTo('about');
    });
    $('#btnRegisterCTA')?.addEventListener('click', (e) => {
      e.preventDefault();
      $('#btnLogin')?.click();
    });

    // Home page buttons
    $('#btnHomeRanking')?.addEventListener('click', (e) => {
      e.preventDefault();
      navigateTo('leaderboard');
    });
    $('#btnHomeAbout')?.addEventListener('click', (e) => {
      e.preventDefault();
      navigateTo('about', 'intro');
    });

    // Home feature cards
    $('#cardFeature1')?.addEventListener('click', () => {
      navigateTo('about', 'join');
    });
    $('#cardFeature2')?.addEventListener('click', () => {
      navigateTo('leaderboard');
    });

    // Mobile menu
    $('#menuToggle').addEventListener('click', () => {
      $('#mainNav').classList.toggle('open');
    });

    // Auth modal
    $('#btnLogin').addEventListener('click', openAuthModal);
    $('#modalClose').addEventListener('click', closeAuthModal);
    $('#authModal').addEventListener('click', (e) => {
      if (e.target === $('#authModal')) closeAuthModal();
    });

    // Auth tabs
    $$('.auth-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        $$('.auth-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const isLogin = tab.dataset.tab === 'login';
        $('#loginForm').classList.toggle('hidden', !isLogin);
        $('#registerForm').classList.toggle('hidden', isLogin);
      });
    });

    // Privacy checkbox
    $('#agreeTerms').addEventListener('change', (e) => {
      $('#btnRegister').disabled = !e.target.checked;
    });

    // Register form: country-city联动
    function populateRegCities() {
      const country = $('#regCountry').value;
      const citySelect = $('#regCity');
      const placeholder = I18N[currentLang]?.selectCity || '请选择城市';
      let html = `<option value="">${placeholder}</option>`;

      if (typeof CITIES_BY_COUNTRY !== 'undefined' && CITIES_BY_COUNTRY[country]) {
        const cities = CITIES_BY_COUNTRY[country];
        for (const c of cities) {
          html += `<option value="${c}">${translateName(c)}</option>`;
        }
      }
      citySelect.innerHTML = html;
    }
    $('#regCountry').addEventListener('change', populateRegCities);
    populateRegCities(); // init on load

    // Login form
    $('#loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const username = $('#loginUsername').value.trim();
      const password = $('#loginPassword').value;
      if (!username || !password) return showToast('请填写用户名和密码', 'error');

      try {
        const resp = await fetch('/api/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password }),
        });
        const data = await resp.json();
        if (!data.success) return showToast(data.error || '登录失败', 'error');

        state.isLoggedIn = true;
        state.currentUser = data.user;
        state.token = data.token;
        localStorage.setItem('yaopao_token', data.token);
        localStorage.setItem('yaopao_user', JSON.stringify(data.user));
        closeAuthModal();
        showToast(`登录成功，欢迎 ${data.user.nickname}！`, 'success');
        $('#btnLogin').innerHTML = `<i class="fas fa-user-circle"></i> <span>${data.user.nickname}</span>`;
        if (data.user.role === 'admin') {
          $('#btnLogin').innerHTML += ` <a href="/admin.html" style="color:var(--primary);font-size:12px;margin-left:4px">[管理]</a>`;
        }
        // Navigate to leaderboard filtered by user's country/city
        if (data.user.country && data.user.city) {
          state.regionTab = 'city';
          state._userCountry = data.user.country;
          state._userCity = data.user.city;
        }
        navigateTo('leaderboard');
      } catch (err) {
        showToast('网络错误，请重试', 'error');
      }
    });

    // Register form
    $('#registerForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!$('#agreeTerms').checked) {
        showToast('请先同意隐私政策与用户协议', 'error');
        return;
      }
      const username = $('#regUsername').value.trim();
      const password = $('#regPassword').value;
      if (!username || !password) return showToast('请填写用户名和密码', 'error');

      try {
        const resp = await fetch('/api/register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            username,
            password,
            nickname: $('#regNickname').value.trim() || username,
            gender: $('#regGender').value,
            ageGroup: $('#regAgeGroup').value,
            country: $('#regCountry').value,
            city: $('#regCity').value,
          }),
        });
        const data = await resp.json();
        if (!data.success) return showToast(data.error || '注册失败', 'error');

        // Auto-login after registration
        state.isLoggedIn = true;
        state.currentUser = data.user;
        state.token = data.token;
        localStorage.setItem('yaopao_token', data.token);
        localStorage.setItem('yaopao_user', JSON.stringify(data.user));
        closeAuthModal();
        showToast(`注册成功，欢迎 ${data.user.nickname}！`, 'success');
        $('#btnLogin').innerHTML = `<i class="fas fa-user-circle"></i> <span>${data.user.nickname || data.user.username}</span>`;
        if (data.user.role === 'admin') {
          $('#btnLogin').innerHTML += ` <a href="/admin.html" style="color:var(--primary);font-size:12px;margin-left:4px">[管理]</a>`;
        }
        if (data.user.country && data.user.city) {
          state.regionTab = 'city';
          state._userCountry = data.user.country;
          state._userCity = data.user.city;
        }
        renderLeaderboard();
      } catch (err) {
        showToast('网络错误，请重试', 'error');
      }
    });

    // Main tabs (Personal / Group)
    setupTabs('#mainTabs', (tab) => {
      state.mainTab = tab;
      state.personalPage = 1;
      $('#tab-personal').style.display = tab === 'personal' ? '' : 'none';
      $('#tab-group').style.display = tab === 'group' ? '' : 'none';
      renderLeaderboard();
    });

    // Personal sub tabs
    setupTabs('#personalTabs', (tab) => {
      state.personalTab = tab;
      state.personalPage = 1;
      state._pageJumped = false;
      renderPersonalTable();
    });

    // Region tabs
    setupTabs('#regionTabs', (tab) => {
      state.regionTab = tab;
      state.personalPage = 1;
      state._pageJumped = false;
      renderPersonalTable();
    });

    // Group tabs
    setupTabs('#groupTabs', (tab) => {
      state.groupTab = tab;
      renderGroupTable();
    });

    // Group selector
    $('#groupSelect').addEventListener('change', (e) => {
      state.group = e.target.value;
      state.personalPage = 1;
      leaderboardCache = {};  // clear cache on group change
      renderLeaderboard();
    });

    // News tabs
    setupTabs('#newsTabs', (tab) => {
      state.newsFilter = tab;
      renderNews();
    });

    // Profile edit form
    $('#editForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!state.currentUser) return showToast('请先登录', 'error');

      const country = $('#editCountry').value;
      const city = $('#editCity').value;
      const gender = $('#editGender').value;
      const ageGroup = $('#editAgeGroup').value;

      try {
        const resp = await fetch('/api/admin?action=updateProfile', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${state.token}` },
          body: JSON.stringify({ action: 'updateProfile', userId: state.currentUser.id, country, city, gender, ageGroup }),
        });
        const data = await resp.json();
        if (!data.success) return showToast(data.error || '更新失败', 'error');

        // Update local state
        state.currentUser.country = country;
        state.currentUser.city = city;
        state.currentUser.gender = gender;
        state.currentUser.ageGroup = ageGroup;
        localStorage.setItem('yaopao_user', JSON.stringify(state.currentUser));

        // Update nav button
        const displayName = state.currentUser.nickname || state.currentUser.username;
        $('#btnLogin').innerHTML = `<i class="fas fa-user-circle"></i> <span>${displayName}</span>`;
        if (state.currentUser.role === 'admin') {
          $('#btnLogin').innerHTML += ` <a href="/admin.html" style="color:var(--primary);font-size:12px;margin-left:4px">[管理]</a>`;
        }

        // Update display
        renderProfile();
        showToast('个人信息更新成功！', 'success');
      } catch (err) {
        showToast('网络错误，请重试', 'error');
      }
    });

    // File upload
    const uploadArea = $('#uploadArea');
    const fileInput = $('#fileInput');

    // Upload tab switching
    $$('.upload-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        $$('.upload-tab').forEach(t => { t.style.background = '#1e2a3a'; t.style.color = '#8892a8'; });
        tab.style.background = 'var(--primary)';
        tab.style.color = '#fff';
        const target = tab.dataset.utab;
        $('#utab-screenshot').style.display = target === 'screenshot' ? '' : 'none';
        $('#utab-manual').style.display = target === 'manual' ? '' : 'none';
      });
    });

    // Manual form submit
    $('#manualForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!state.currentUser) return showToast('请先登录', 'error');

      const date = $('#manualDate').value;
      const time = $('#manualTime').value;
      const energy = parseFloat($('#manualEnergy').value);
      const rpm = parseInt($('#manualRPM').value);
      const dist = parseFloat($('#manualDist').value);

      if (!date || !time) return showToast('请填写日期和时间', 'error');
      if (isNaN(energy) || energy < 0 || energy > 30) return showToast('活力指数范围应为0-30', 'error');
      if (isNaN(rpm) || rpm < 0 || rpm > 20000) return showToast('最高转速范围应为0-20000', 'error');
      if (isNaN(dist) || dist < 0 || dist > 3) return showToast('一分钟里程范围应为0-3.00', 'error');

      try {
        const resp = await fetch('/api/records', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${state.token}` },
          body: JSON.stringify({
            energy, rpm, distance: dist,
            device: `manual-${date}T${time}`
          }),
        });
        const data = await resp.json();
        if (!data.success) return showToast(data.error || '提交失败', 'error');

        if (data.pending) {
          showToast(t('scorePending'), 'success');
        } else {
          showToast(t('scoreApproved'), 'success');
        }
        leaderboardCache = {};  // refresh leaderboard
        $('#manualForm').reset();
        // Set today's date as default
        $('#manualDate').value = new Date().toISOString().slice(0, 10);
        // Refresh stats
        loadUserStats(state.currentUser.id);
      } catch (err) {
        showToast('网络错误，请重试', 'error');
      }
    });

    // Set default date for manual form
    if ($('#manualDate')) $('#manualDate').value = new Date().toISOString().slice(0, 10);

    // Screenshot upload

    uploadArea.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.style.borderColor = 'var(--primary)'; });
    uploadArea.addEventListener('dragleave', () => { uploadArea.style.borderColor = ''; });
    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.style.borderColor = '';
      handleFileUpload(e.dataTransfer.files[0]);
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files[0]) handleFileUpload(fileInput.files[0]);
    });

    // Language toggle
    const langBtn = $('#langToggle');
    if (langBtn) {
      langBtn.querySelector('span').textContent = currentLang === 'zh' ? 'EN' : '中文';
      langBtn.addEventListener('click', (e) => {
        e.preventDefault();
        setLang(currentLang === 'zh' ? 'en' : 'zh');
        langBtn.querySelector('span').textContent = currentLang === 'zh' ? 'EN' : '中文';
        // Re-render dynamic content
        leaderboardCache = {};
        state._autoDetected = false;
        renderLeaderboard();
        renderNews();
        if (state.currentPage === 'profile') renderProfile();
      });
    }

    // Initial render
    renderLeaderboard();
    renderNews();
    renderHomeVideos();
    renderVideosPage();
    setupLivePreview();
    const avatarEl = $('#profileAvatar');
    const avatarInputCamera = $('#avatarInputCamera');
    const avatarInputAlbum = $('#avatarInputAlbum');

    $('#btnAvatarCamera').addEventListener('click', (e) => {
      e.stopPropagation();
      avatarInputCamera.click();
    });

    $('#btnAvatarAlbum').addEventListener('click', (e) => {
      e.stopPropagation();
      avatarInputAlbum.click();
    });

    // Also allow clicking the avatar itself (fallback)
    avatarEl.addEventListener('click', (e) => {
      if (e.target.closest('.avatar-action-btn')) return;
      avatarInputAlbum.click();
    });

    function handleAvatarFile(file) {
      if (!file) return;
      if (!file.type.startsWith('image/')) return showToast('请选择图片文件', 'error');
      if (file.size > 2 * 1024 * 1024) return showToast('头像图片不能超过 2MB', 'error');
      const reader = new FileReader();
      reader.onload = (ev) => {
        avatarEl.style.backgroundImage = `url(${ev.target.result})`;
        avatarEl.style.backgroundSize = 'cover';
        avatarEl.style.backgroundPosition = 'center';
        avatarEl.textContent = '';
        showToast('头像已更新', 'success');
      };
      reader.readAsDataURL(file);
    }

    avatarInputCamera.addEventListener('change', () => {
      handleAvatarFile(avatarInputCamera.files[0]);
    });

    avatarInputAlbum.addEventListener('change', () => {
      handleAvatarFile(avatarInputAlbum.files[0]);
    });
  }

  async function handleFileUpload(file) {
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      showToast(t('uploadImage') || 'Please upload an image (JPG, PNG)', 'error');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      showToast(currentLang === 'zh' ? '文件大小不能超过 5MB' : 'File size must be under 5MB', 'error');
      return;
    }

    showToast(t('recognizing'), 'success');

    try {
      const formData = new FormData();
      formData.append('image', file);

      const resp = await fetch('/api/ocr', {
        method: 'POST',
        body: formData,
      });
      const data = await resp.json();

      if (!data.success || data.error) {
        showToast(data.error || t('recognizeFail'), 'error');
        return;
      }

      const result = data.data;
      if (result.error) {
        showToast(t('cannotRecognize'), 'error');
        return;
      }

      // Fill in the form with recognized data (latest record)
      if (result.date) $('#manualDate').value = result.date;
      if (result.time) $('#manualTime').value = result.time;
      if (result.energy != null) $('#manualEnergy').value = result.energy;
      if (result.rpm != null) $('#manualRPM').value = result.rpm;
      if (result.distance != null) $('#manualDist').value = result.distance;

      showToast(t('recognizeSuccess'), 'success');
    } catch (err) {
      console.error('OCR error:', err);
      showToast(t('recognizeFail'), 'error');
    }
  }

  // ===================== Home Download Modals =====================
  function showAndroidDownload() {
    const modal = document.getElementById('androidDownloadModal');
    if (modal) { modal.classList.add('active'); return; }
    // Create modal if not exists
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.id = 'androidDownloadModal';
    overlay.innerHTML = `
      <div class="modal" style="max-width:480px;padding:32px;text-align:center">
        <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fas fa-times"></i></button>
        <i class="fab fa-android" style="font-size:64px;color:#3DDC84;margin-bottom:16px;display:block"></i>
        <h3 style="margin-bottom:8px">Android 下载</h3>
        <p style="color:var(--text-secondary);margin-bottom:20px">安卓手机直接安装 RunOrb App</p>
        <a href="https://api.runorb.us/app/V1.2.2.13.apk" target="_blank" rel="noopener" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:14px 32px;font-size:16px;text-decoration:none">
          <i class="fab fa-android"></i> 立即下载 APK (R1)
        </a>
      </div>`;
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('active'); });
    document.body.appendChild(overlay);
  }

  function showIOSDownload() {
    const modal = document.getElementById('iosDownloadModal');
    if (modal) { modal.classList.add('active'); return; }
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.id = 'iosDownloadModal';
    overlay.innerHTML = `
      <div class="modal" style="max-width:480px;padding:32px;text-align:center">
        <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fas fa-times"></i></button>
        <i class="fab fa-apple" style="font-size:64px;color:#000;margin-bottom:16px;display:block"></i>
        <h3 style="margin-bottom:8px">App Store 下载</h3>
        <p style="color:var(--text-secondary);margin-bottom:6px">RunOrb — 摇跑活力指数</p>
        <div style="display:flex;align-items:center;justify-content:center;gap:4px;margin-bottom:20px">
          <i class="fas fa-star" style="color:#FFD700"></i><i class="fas fa-star" style="color:#FFD700"></i><i class="fas fa-star" style="color:#FFD700"></i><i class="fas fa-star" style="color:#FFD700"></i><i class="fas fa-star-half-alt" style="color:#FFD700"></i>
          <span style="font-size:13px;color:var(--text-secondary);margin-left:4px">4.5</span>
        </div>
        <a href="https://apps.apple.com/cn/app/runorb/id1559861575" target="_blank" rel="noopener" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:14px 32px;font-size:16px;text-decoration:none;background:#000">
          <i class="fab fa-apple"></i> 前往 App Store
        </a>
      </div>`;
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('active'); });
    document.body.appendChild(overlay);
  }

  function showMiniProgram() {
    const modal = document.getElementById('miniProgramModal');
    if (modal) { modal.classList.add('active'); return; }
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.id = 'miniProgramModal';
    overlay.innerHTML = `
      <div class="modal" style="max-width:480px;padding:32px;text-align:center">
        <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fas fa-times"></i></button>
        <i class="fab fa-weixin" style="font-size:64px;color:#07C160;margin-bottom:16px;display:block"></i>
        <h3 style="margin-bottom:8px">微信小程序</h3>
        <p style="color:var(--text-secondary);margin-bottom:20px">微信搜索「我爱摇跑」即可使用</p>
        <div style="width:180px;height:180px;margin:0 auto;background:var(--bg-secondary);border-radius:12px;display:flex;align-items:center;justify-content:center;border:2px dashed var(--border)">
          <div style="text-align:center;color:var(--text-secondary)">
            <i class="fas fa-qrcode" style="font-size:48px;margin-bottom:8px;display:block"></i>
            <span style="font-size:13px">小程序码</span>
          </div>
        </div>
        <p style="font-size:13px;color:var(--text-secondary);margin-top:12px">打开微信 → 搜索「我爱摇跑」</p>
      </div>`;
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('active'); });
    document.body.appendChild(overlay);
  }

  // ===================== Home Video Grid =====================
  function renderHomeVideos() {
    const grid = document.getElementById('homeVideosGrid');
    if (!grid) return;
    // Temporarily disabled videos for mobile performance
    grid.innerHTML = '';
    grid.style.display = 'none';
  }

  // ===================== Videos Page =====================
  const VIDEO_LIST = [
    // 摇跑精神系列
    { id: 'yes-you-can', category: 'spirit', title: 'Yes You Can', desc: '摇跑精神：你可以的' },
    { id: 'big-brother', category: 'spirit', title: 'Big Brother', desc: '摇跑精神：大哥风范' },
    { id: 'family-player', category: 'spirit', title: 'Family Player', desc: '摇跑精神：家庭玩家' },
    { id: 'you-high-i-win', category: 'spirit', title: 'You High I Win', desc: '摇跑精神：你高我赢' },
    // 产品展示
    { id: 'product-demo', category: 'product', title: '产品宣传片', desc: 'RunOrb 产品宣传片' },
    { id: 'product-features', category: 'product', title: '产品功能展示', desc: 'RunOrb 核心功能展示' },
    { id: 'product-1080p', category: 'product', title: '产品介绍1080P', desc: '高清产品介绍' },
    // 赛事活动
    { id: 'charity-moment', category: 'event', title: '公益活动精彩瞬间', desc: '公益赛事精彩回顾' },
    { id: 'event-preview', category: 'event', title: '赛事挑战预告', desc: ' upcoming 赛事预告' },
    { id: 'match-demo', category: 'event', title: '比赛演示', desc: '摇跑比赛实况演示' },
    // 教程指南
    { id: 'tutorial-start', category: 'tutorial', title: '入门教程', desc: '摇跑入门第一课' },
    { id: 'app-tutorial', category: 'tutorial', title: 'App使用教程', desc: 'RunOrb App 使用指南' },
    { id: 'technique-advanced', category: 'tutorial', title: '技巧提升', desc: '进阶摇跑技巧' },
    { id: 'training-advanced', category: 'tutorial', title: '高级训练', desc: '高级训练计划' },
    { id: 'tips', category: 'tutorial', title: '摇跑小贴士', desc: '实用摇跑小技巧' }
  ];

  const VIDEO_CATEGORIES = [
    { key: 'all', label: '全部', icon: 'fas fa-th' },
    { key: 'spirit', label: '摇跑精神', icon: 'fas fa-fire' },
    { key: 'product', label: '产品展示', icon: 'fas fa-box-open' },
    { key: 'event', label: '赛事活动', icon: 'fas fa-trophy' },
    { key: 'tutorial', label: '教程指南', icon: 'fas fa-graduation-cap' }
  ];

  let currentVideoCategory = 'all';

  function renderVideosPage() {
    const grid = document.getElementById('videosGrid');
    if (!grid) return;

    // Render category tabs if not exists
    let tabsContainer = document.getElementById('videoCategoryTabs');
    if (!tabsContainer) {
      tabsContainer = document.createElement('div');
      tabsContainer.id = 'videoCategoryTabs';
      tabsContainer.style.cssText = 'display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-bottom:24px;padding:0 20px';
      tabsContainer.innerHTML = VIDEO_CATEGORIES.map(c =>
        `<button class="tab-btn ${c.key === 'all' ? 'active' : ''}" data-videocat="${c.key}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:20px;font-size:14px;cursor:pointer;border:1px solid var(--border);background:${c.key === 'all' ? 'var(--primary)' : 'var(--card-bg)'};color:${c.key === 'all' ? '#fff' : 'var(--text-primary)'};transition:all 0.2s">
          <i class="${c.icon}"></i> ${c.label}
        </button>`
      ).join('');
      grid.parentNode.insertBefore(tabsContainer, grid);

      tabsContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-videocat]');
        if (!btn) return;
        currentVideoCategory = btn.dataset.videocat;
        tabsContainer.querySelectorAll('[data-videocat]').forEach(b => {
          b.classList.toggle('active', b.dataset.videocat === currentVideoCategory);
          b.style.background = b.dataset.videocat === currentVideoCategory ? 'var(--primary)' : 'var(--card-bg)';
          b.style.color = b.dataset.videocat === currentVideoCategory ? '#fff' : 'var(--text-primary)';
        });
        renderVideoCards();
      });
    }

    renderVideoCards();
  }

  function renderVideoCards() {
    const grid = document.getElementById('videosGrid');
    if (!grid) return;
    const filtered = currentVideoCategory === 'all'
      ? VIDEO_LIST
      : VIDEO_LIST.filter(v => v.category === currentVideoCategory);

    grid.innerHTML = filtered.map(v => `
      <div class="video-card" style="border-radius:12px;overflow:hidden;background:var(--card-bg);box-shadow:0 2px 12px rgba(0,0,0,0.08);transition:transform 0.2s,box-shadow 0.2s;cursor:pointer" onclick="playVideoModal('${v.id}','${v.title}')"
        onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.15)'"
        onmouseout="this.style.transform='';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
        <div style="position:relative;background:#000">
          <video muted loop playsinline preload="metadata" style="width:100%;display:block;aspect-ratio:16/9;object-fit:cover"
            onmouseenter="this.play()" onmouseleave="this.pause();this.currentTime=0">
            <source src="/videos/${v.id}.mp4" type="video/mp4">
          </video>
          <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.3);opacity:0;transition:opacity 0.2s" class="video-play-overlay"
            onmouseenter="this.style.opacity='0'" onmouseleave="this.style.opacity='1'">
            <i class="fas fa-play-circle" style="font-size:48px;color:#fff"></i>
          </div>
        </div>
        <div style="padding:12px 16px">
          <h4 style="margin:0 0 4px;font-size:15px;font-weight:600">${v.title}</h4>
          <p style="margin:0;font-size:13px;color:var(--text-secondary)">${v.desc}</p>
        </div>
      </div>
    `).join('');

    // Apply grid layout
    grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;max-width:1200px;margin:0 auto;padding:0 20px 40px';
  }

  function playVideoModal(videoId, title) {
    // Remove existing modal if any
    const existing = document.getElementById('videoPlayModal');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.id = 'videoPlayModal';
    overlay.style.zIndex = '10000';
    overlay.innerHTML = `
      <div class="modal" style="width:90vw;max-width:800px;padding:0;overflow:hidden;background:#000;border-radius:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:rgba(0,0,0,0.8);color:#fff">
          <span style="font-size:16px;font-weight:600">${title}</span>
          <button onclick="document.getElementById('videoPlayModal').remove()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;padding:4px 8px"><i class="fas fa-times"></i></button>
        </div>
        <video controls autoplay playsinline style="width:100%;display:block">
          <source src="/videos/${videoId}.mp4" type="video/mp4">
        </video>
      </div>`;
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
  }

  // ===================== Hash-based navigation for footer links =====================
  function handleHash() {
    const hash = location.hash.replace('#', '');
    if (!hash) return;
    const [page, section] = hash.split('/');
    if (page && section) {
      navigateTo(page, section);
    } else if (page) {
      navigateTo(page);
    }
  }
  window.addEventListener('hashchange', handleHash);
  // Also handle initial hash on load
  if (location.hash) {
    const origInit = init;
    init = function() {
      origInit();
      handleHash();
    };
  }

  // ===================== Start =====================
  document.addEventListener('DOMContentLoaded', init);

})();

// deploy 20260412b





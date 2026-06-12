@extends('common/admin')
@section('content')
<div class="wrapper">
	<div class="sidebar" data-color="{{ session()->get('admin_color') }}" data-image="{{ session()->get('admin_background') }}">

        <!--

            Tip 1: you can change the color of the sidebar using: data-color="blue | azure | green | orange | red | purple"
            Tip 2: you can also add an image using data-image tag

        -->

        <div class="logo">
            <a href="#" class="logo-text">
                麦凯莱跨境平台管理系统
            </a>
        </div>
        <div class="logo logo-mini">
            <a href="#" class="logo-text">
                MC
            </a>
        </div>

    	<div class="sidebar-wrapper">
            <div class="user">
                <div class="photo">
                    <img src="{{ !empty(session()->get('admin_image'))?session()->get('admin_image'):asset('admin/img/common/default-avatar.png') }}" />
                </div>
                <div class="info">
                    <a data-toggle="collapse" href="#collapseExample" class="collapsed">
                        {{ session()->get('admin_name') }}
                        <b class="caret"></b>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/index/admin' ? 'in' : ''}}" id="collapseExample">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/index/admin' ? 'active' : ''}}"><a href="/admin/index/admin">修改账户</a></li>

                        </ul>
                    </div>
                    
                </div>
            </div>

            <ul class="nav">

                <li class="{{ Request::getPathInfo() == '/admin/index' ? 'active' : ''}}">
                    <a href="/admin/index">
                        <i class="pe-7s-graph"></i>
                        <p>首页</p>
                    </a>
                </li>

                <!-- <li>
                    <a data-toggle="collapse" href="#pagesExamples">
                        <i class="pe-7s-gift"></i>
                        <p>首页
                           <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse" id="pagesExamples">
                        <ul class="nav">
                            <li><a href="pages/login.html">Login Page</a></li>
                            <li><a href="pages/register.html">Register Page</a></li>
                            <li><a href="pages/lock.html">Lock Screen Page</a></li>
                            <li><a href="pages/user.html">User Page</a></li>
                            <li><a href="#">More coming soon...</a></li>
                        </ul>
                    </div>
                </li> -->

                <li class="{{ Request::getPathInfo() == '/admin/admin' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/admin/add' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/admin/group' ? 'active' : ''}}
                {{ Request::getPathInfo() == '/admin/admin/group/add' ? 'active' : ''}}">
                    <a data-toggle="collapse" href="#admin">
                        <i class="pe-7s-user"></i>
                        <p>管理员
                           <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/admin' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/admin/add' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/admin/group' ? 'in' : ''}}
                    {{ Request::getPathInfo() == '/admin/admin/group/add' ? 'in' : ''}}" id="admin">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/admin/add' ? 'active' : ''}}"><a href="/admin/admin/add">添加管理员</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/admin' ? 'active' : ''}}"><a href="/admin/admin">管理员列表</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/admin/group' ? 'active' : ''}}"><a href="/admin/admin/group">管理员组</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/admin/group/add' ? 'active' : ''}}"><a href="/admin/admin/group/add">添加管理员组</a></li>
                        </ul>
                    </div>
                </li>
                
                <li class="{{ Request::getPathInfo() == '/admin/product' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/product/add' ? 'active' : ''}}">
                    <a data-toggle="collapse" href="#componentsExamples">
                        <i class="pe-7s-shopbag"></i>
                        <p>商品
                           <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/product' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/product/add' ? 'in' : ''}}" id="componentsExamples">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/product/add' ? 'active' : ''}}"><a href="/admin/product/add">添加商品</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/product' ? 'active' : ''}}"><a href="/admin/product">商品列表</a></li>
                        </ul>
                    </div>
                </li>


                <li class="{{ Request::getPathInfo() == '/admin/brand' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/brand/add' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/brand/add_series' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/brand/series' ? 'active' : ''}}">
                    <a data-toggle="collapse" href="#formsExamples">
                        <i class="pe-7s-science"></i>
                        <p>品牌
                           <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/brand' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/brand/add' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/brand/add_series' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/brand/series' ? 'in' : ''}}" id="formsExamples">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/brand/add' ? 'active' : ''}}"><a href="/admin/brand/add">添加品牌</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/brand' ? 'active' : ''}}"><a href="/admin/brand">品牌列表</a></li>
                            <!-- <li class="{{ Request::getPathInfo() == '/admin/brand/add_series' ? 'active' : ''}}"><a href="/admin/brand/add_series">添加系列</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/brand/series' ? 'active' : ''}}"><a href="/admin/brand/series">系列列表</a></li> -->
                        </ul>
                    </div>
                </li>


                <li class="{{ Request::getPathInfo() == '/admin/classification' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/classification/add' ? 'active' : ''}}">
                    <a data-toggle="collapse" href="#class_">
                        <i class="pe-7s-menu"></i>
                        <p>分类
                           <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/classification' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/classification/add' ? 'in' : ''}}" id="class_">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/classification/add' ? 'active' : ''}}"><a href="/admin/classification/add">添加类别</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/classification' ? 'active' : ''}}"><a href="/admin/classification">类别列表</a></li>
                        </ul>
                    </div>
                </li>

                <li class="{{ Request::getPathInfo() == '/admin/order' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/pay' ? 'in' : ''}}
                {{ Request::getPathInfo() == '/admin/order/delivery' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/goods' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/completed' ? 'in' : ''}}
                {{ Request::getPathInfo() == '/admin/order/network_pay' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/network_pay_complete' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/network_pay_complete_abnormal' ? 'in' : ''}}
                {{ Request::getPathInfo() == '/admin/order/alipay' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/alipay_complete' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/alipay_complete_abnormal' ? 'in' : ''}}
                {{ Request::getPathInfo() == '/admin/order/yinmeng_success' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/yinmeng_error' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/yinmeng_waiting' ? 'in' : ''}}
                {{ Request::getPathInfo() == '/admin/order/icbc_pay_order' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/icbc_pay_order_success' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/icbc_pay_order_exception' ? 'in' : ''}}">
                    <a data-toggle="collapse" href="#order">
                        <i class="pe-7s-photo-gallery"></i>
                        <p>订单
                            <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/order' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/pay' ? 'in' : ''}}
                    {{ Request::getPathInfo() == '/admin/order/delivery' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/goods' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/completed' ? 'in' : ''}}
                    {{ Request::getPathInfo() == '/admin/order/network_pay' ? 'in' : ''}}{{ Request::getPathInfo() == '/admin/order/network_pay_complete' ? 'in' : ''}}
                    {{ Request::getPathInfo() == '/admin/order/network_pay_complete_abnormal' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/alipay' ? 'in' : ''}}
                    {{ Request::getPathInfo() == '/admin/order/alipay_complete' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/alipay_complete_abnormal' ? 'in' : ''}}
                    {{ Request::getPathInfo() == '/admin/order/yinmeng_success' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/yinmeng_error' ? 'in' : ''}}
                    {{ Request::getPathInfo() == '/admin/order/yinmeng_waiting' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/icbc_pay_order' ? 'in' : ''}}
                    {{ Request::getPathInfo() == '/admin/order/icbc_pay_order_success' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/order/icbc_pay_order_exception' ? 'in' : ''}}" id="order">
                        <ul class="nav">
                            @if(session()->get('admin_group') == 1)
                            <li class="{{ Request::getPathInfo() == '/admin/order' ? 'active' : ''}}"><a href="/admin/order">全部订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/pay' ? 'active' : ''}}"><a href="/admin/order/pay">待支付订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/delivery' ? 'active' : ''}}"><a href="/admin/order/delivery">待发货订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/goods' ? 'active' : ''}}"><a href="/admin/order/goods">待发货收货</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/completed' ? 'active' : ''}}"><a href="/admin/order/completed">已完成订单</a></li>

                            <li class="{{ Request::getPathInfo() == '/admin/order/network_pay' ? 'active' : ''}}"><a href="/admin/order/network_pay">待网银支付订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/network_pay_complete' ? 'active' : ''}}"><a href="/admin/order/network_pay_complete">网银支付完成订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/network_pay_complete_abnormal' ? 'active' : ''}}"><a href="/admin/order/network_pay_complete_abnormal">待网银支付异常订单</a></li>

                            @endif

                            <li class="{{ Request::getPathInfo() == '/admin/order/alipay' ? 'active' : ''}}"><a href="/admin/order/alipay">支付宝待支付订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/alipay_complete' ? 'active' : ''}}"><a href="/admin/order/alipay_complete">支付宝已完成订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/alipay_complete_abnormal' ? 'active' : ''}}"><a href="/admin/order/alipay_complete_abnormal">支付宝异常订单</a></li>

                            <li class="{{ Request::getPathInfo() == '/admin/order/yinmeng_success' ? 'active' : ''}}"><a href="/admin/order/yinmeng_success">银盟支付成功订单</a></li>
                                <li class="{{ Request::getPathInfo() == '/admin/order/yinmeng_waiting' ? 'active' : ''}}"><a href="/admin/order/yinmeng_waiting">银盟支付等待返回订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/order/yinmeng_error' ? 'active' : ''}}"><a href="/admin/order/yinmeng_error">银盟支付失败订单</a></li>

                                <li class="{{ Request::getPathInfo() == '/admin/order/icbc_pay_order' ? 'active' : ''}}"><a href="/admin/order/icbc_pay_order">工行支付等待处理订单</a></li>
                                <li class="{{ Request::getPathInfo() == '/admin/order/icbc_pay_order_success' ? 'active' : ''}}"><a href="/admin/order/icbc_pay_order_success">工行支付成功订单</a></li>
                                <li class="{{ Request::getPathInfo() == '/admin/order/icbc_pay_order_exception' ? 'active' : ''}}"><a href="/admin/order/icbc_pay_order_exception">工行支付异常订单</a></li>
                        </ul>
                    </div>
                </li>

                @if(session()->get('admin_group') == 1)

                <li class="{{ Request::getPathInfo() == '/admin/user' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/user_group' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/add_user_group' ? 'active' : ''}}">
                    <a data-toggle="collapse" href="#user">
                        <i class="pe-7s-users"></i>
                        <p>用户
                            <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/user' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/user_group' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/add_user_group' ? 'in' : ''}}" id="user">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/user' ? 'active' : ''}}"><a href="/admin/user">用户列表</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/add_user_group' ? 'active' : ''}}"><a href="/admin/add_user_group">添加用户组</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/user_group' ? 'active' : ''}}"><a href="/admin/user_group">用户组</a></li>
                        </ul>
                    </div>
                </li>

                <li class="{{ Request::getPathInfo() == '/admin/other/banner' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/other' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/other/add_banner' ? 'active' : ''}}">
                    <a data-toggle="collapse" href="#other">
                        <i class="pe-7s-plugin"></i>
                        <p>其他
                           <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/other/banner' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/other/add_banner' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/other' ? 'in' : ''}}" id="other">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/other/add_banner' ? 'active' : ''}}"><a href="/admin/other/add_banner">添加首页切换</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/other/banner' ? 'active' : ''}}"><a href="/admin/other/banner">首页切换列表</a></li>
                            <!-- <li class="{{ Request::getPathInfo() == '/admin/other' ? 'active' : ''}}"><a href="/admin/other">公司信息</a></li> -->
                        </ul>
                    </div>
                </li>

                @endif

                <li class="{{ Request::getPathInfo() == '/admin/import_order/tmall' ? 'active' : ''}}{{ Request::getPathInfo() == '/admin/import_order/tmall_booking' ? 'active' : ''}}{{ Request::getPathInfo() == '/admin/import_order/other' ? 'active' : ''}}{{ Request::getPathInfo() == '/admin/import_order/channel' ? 'active' : ''}}{{ Request::getPathInfo() == '/admin/import_order/add_channel' ? 'active' : ''}}">
                    <a data-toggle="collapse" href="#import_order">
                        <i class="pe-7s-plugin"></i>
                        <p>订单导入
                            <b class="caret"></b>
                        </p>
                    </a>
                    <div class="collapse {{ Request::getPathInfo() == '/admin/import_order/tmall' ? 'in' : ''}}{{ Request::getPathInfo() == '/admin/import_order/tmall_booking' ? 'in' : ''}}{{ Request::getPathInfo() == '/admin/import_order/other' ? 'in' : ''}}{{ Request::getPathInfo() == '/admin/import_order/channel' ? 'in' : ''}}{{ Request::getPathInfo() == '/admin/import_order/add_channel' ? 'in' : ''}}" id="import_order">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/import_order/tmall' ? 'active' : ''}}"><a href="/admin/import_order/tmall">淘系订单导入</a></li>
                            @if(session()->get('admin_group') == 1)
                            <li class="{{ Request::getPathInfo() == '/admin/import_order/tmall_booking' ? 'active' : ''}}"><a href="/admin/import_order/tmall_booking">预售订单导入</a></li>
                            @endif
                            <li class="{{ Request::getPathInfo() == '/admin/import_order/other' ? 'active' : ''}}"><a href="/admin/import_order/other">其他订单导入</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/import_order/channel' ? 'active' : ''}}"><a href="/admin/import_order/channel">导入渠道列表</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/import_order/add_channel' ? 'active' : ''}}"><a href="/admin/import_order/add_channel">添加导入渠道</a></li>
                        </ul>
                    </div>
                </li>


                <li class="{{ Request::getPathInfo() == '/admin/customs/customs_orders' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/customs/customs_exception_orders' ? 'active' : ''}} {{ Request::getPathInfo() == '/admin/customs/customs_clearance' ? 'active' : ''}}">
                    <a data-toggle="collapse" href="#import_order">
                        <i class="pe-7s-plugin"></i>
                        <p>海关报关
                            <b class="caret"></b>
                        </p>
                    </a>
                    <div class="{{ Request::getPathInfo() == '/admin/customs/customs_orders' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/customs/customs_exception_orders' ? 'in' : ''}} {{ Request::getPathInfo() == '/admin/customs/customs_clearance' ? 'in' : ''}}" id="import_order">
                        <ul class="nav">
                            <li class="{{ Request::getPathInfo() == '/admin/customs/customs_orders' ? 'active' : ''}}"><a href="/admin/customs/customs_orders">报关订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/customs/customs_exception_orders' ? 'active' : ''}}"><a href="/admin/customs/customs_exception_orders">报关异常订单</a></li>
                            <li class="{{ Request::getPathInfo() == '/admin/customs/customs_clearance' ? 'active' : ''}}"><a href="/admin/customs/customs_clearance">核注清单</a></li>
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
    </div>
    <div class="main-panel">
        <nav class="navbar navbar-default">
            <div class="container-fluid">
				<div class="navbar-minimize">
    				<button id="minimizeSidebar" class="btn btn-warning btn-fill btn-round btn-icon" style="background-color: {{ session()->get('admin_color') == 'orange'?'#FFA534':'' }}{{ session()->get('admin_color') == 'green'?'#87CB16':'' }}{{ session()->get('admin_color') == 'azure'?'#1DC7EA':'' }}{{ session()->get('admin_color') == 'black'?'#757575':'' }}{{ session()->get('admin_color') == 'purple'?'#9368E9':'' }}{{ session()->get('admin_color') == 'red'?'#FB404B':'' }}; border-color: {{ session()->get('admin_color') == 'orange'?'#FFA534':'' }}{{ session()->get('admin_color') == 'green'?'#87CB16':'' }}{{ session()->get('admin_color') == 'azure'?'#1DC7EA':'' }}{{ session()->get('admin_color') == 'black'?'#757575':'' }}{{ session()->get('admin_color') == 'purple'?'#9368E9':'' }}{{ session()->get('admin_color') == 'red'?'#FB404B':'' }}">
    					<i class="fa fa-ellipsis-v visible-on-sidebar-regular"></i>
    					<i class="fa fa-navicon visible-on-sidebar-mini"></i>
    				</button>
                    <div sytle="width: 30px; height: 30px; background-color: #000;"></div>
    			</div>
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">@yield('content_title')</a>
                </div>
                <div class="collapse navbar-collapse">

                    <form class="navbar-form navbar-left navbar-search-form" role="search">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" value="" class="form-control" placeholder="Search...">
                        </div>
                    </form>

                    <ul class="nav navbar-nav navbar-right">
                        <li>
                            <a href="../charts.html">
                                <i class="fa fa-line-chart"></i>
                                <p>Stats</p>
                            </a>
                        </li>

                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-gavel"></i>
                                <p class="hidden-md hidden-lg">
                                    Actions
                                    <b class="caret"></b>
                                </p>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="#">Create New Post</a></li>
                                <li><a href="#">Manage Something</a></li>
                                <li><a href="#">Do Nothing</a></li>
                                <li><a href="#">Submit to live</a></li>
                                <li class="divider"></li>
                                <li><a href="#">Another Action</a></li>
                            </ul>
                        </li>

                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-bell-o"></i>
                                <span class="notification">5</span>
                                <p class="hidden-md hidden-lg">
    								Notifications
    								<b class="caret"></b>
    							</p>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="#">Notification 1</a></li>
                                <li><a href="#">Notification 2</a></li>
                                <li><a href="#">Notification 3</a></li>
                                <li><a href="#">Notification 4</a></li>
                                <li><a href="#">Another notification</a></li>
                            </ul>
                        </li>

                        <li class="dropdown dropdown-with-icons">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-list"></i>
                                <p class="hidden-md hidden-lg">
    								More
    								<b class="caret"></b>
    							</p>
                            </a>
                            <ul class="dropdown-menu dropdown-with-icons">
                                <li>
                                    <a href="#">
                                        <i class="pe-7s-mail"></i> Messages
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="pe-7s-help1"></i> Help Center
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="pe-7s-tools"></i> Settings
                                    </a>
                                </li>
                                <li class="divider"></li>
                                <li>
                                    <a href="#">
                                        <i class="pe-7s-lock"></i> Lock Screen
                                    </a>
                                </li>
                                <li>
                                    <a href="/admin/index/exit" class="text-danger">
                                        <i class="pe-7s-close-circle"></i>
                                        退出登陆
                                    </a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                </div>
            </div>
        </nav>

		@section('content_center')







		@show

		<footer class="footer">
            <div class="container-fluid">
                <nav class="pull-left">
                    <ul>
                        <li>
                            <a href="/">
                                主页
                            </a>
                        </li>
                        <li>
                            <a href="/product">
                                商品
                            </a>
                        </li>
                        <li>
                            <a href="/brand">
                                品牌
                            </a>
                        </li>
                        <li>
                            <a href="/contact">
                               联系我们
                            </a>
                        </li>
                    </ul>
                </nav>
                <p class="copyright pull-right">
                    &copy; 2017 <a href="http://www.creative-tim.com">Mega Combine</a>, made with love for a better web
                </p>
            </div>
        </footer>


    </div>
</div>

@stop


@section('js')
<script>
    $('.adjustments-line span.badge').click(function(){
        var color = $(this).attr('data-color');
        $.post("/admin/index/color",{'color':color,'_token':"{{ csrf_token() }}"},function(data){
            
        })
    })
    $('.fixed-plugin ul li a img').click(function(){
        var src = $(this).attr('src');
        $.post("/admin/index/background",{'src':src,'_token':"{{ csrf_token() }}"},function(data){

        })
    })

    @if(session()->has('success'))
    $(document).ready(function(){

        $.notify({
            icon: 'pe-7s-bell',
            message: "<b>{{ session()->get('success') }}</b>",
            color: "{{ session()->get('admin_color') == 'orange'?'#FFA534':'' }}{{ session()->get('admin_color') == 'green'?'#87CB16':'' }}{{ session()->get('admin_color') == 'azure'?'#1DC7EA':'' }}{{ session()->get('admin_color') == 'black'?'#757575':'' }}{{ session()->get('admin_color') == 'purple'?'#9368E9':'' }}{{ session()->get('admin_color') == 'red'?'#FB404B':'' }}",
        },{
            type: 'warning',
            timer: 4000
        });

    });
    @elseif(session()->has('error'))
    $(document).ready(function(){
        $.notify({
            icon: 'pe-7s-bell',
            message: "<b>{{ session()->get('error') }}</b>",
            color: "{{ session()->get('admin_color') == 'orange'?'#FFA534':'' }}{{ session()->get('admin_color') == 'green'?'#87CB16':'' }}{{ session()->get('admin_color') == 'azure'?'#1DC7EA':'' }}{{ session()->get('admin_color') == 'black'?'#757575':'' }}{{ session()->get('admin_color') == 'purple'?'#9368E9':'' }}{{ session()->get('admin_color') == 'red'?'#FB404B':'' }}",
        },{
            type: 'warning',
            timer: 4000
        });

    });
    @endif
</script>
@stop
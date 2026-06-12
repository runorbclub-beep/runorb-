<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>麦凯莱跨境电商平台 - @yield('title')</title>
    <link rel="stylesheet" type="text/css" href="/common/css/index.css">
    <link href="{{ asset('common/css/pe-icon-7-stroke.css') }}" rel="stylesheet" />
    @section('style')
    @show
    <script type="text/javascript" src="/common/js/jquery.js"></script>
</head>
<body>
<a id="top"></a>
<div class="header">
    <div class="header_top">
        <div class="header_top_center">
            <div class="header_top_title"><a href="/">麦凯莱跨境购欢迎您！</a></div>
            <div class="header_top_user">
                <div class="header_top_user_title">
                    <img src="/common/image/header_user.png" class="header_top_user_title_user" />
                    <p class="header_top_user_title_text">我的账户</p>
                    <img src="/common/image/header_down.png" class="header_top_user_title_down" />
                </div>
                <div class="header_top_user_menu">
                    <a href="/user"><div>我的信息</div></a>
                    <a href="/login/exit"><div>退出登录</div></a>
                </div>
            </div>
            <div class="header_top_user_order"><a href="/user/order"><img src="/common/image/header_order.png" /><p>我的订单</p></a></div>
            @if(!session()->get('user'))
                <div class="header_top_login">
                    <span><a href="/login">登录</a></span>
                    <span>| </span>
                    <span><a href="/login/register">注册</a></span>
                </div>
            @endif
        </div>
    </div>
</div>
<div class="auxiliary">
    <div class="auxiliary_customer">
        <img src="/common/image/customer.png" alt="" class="auxiliary_image">
        <p class="auxiliary_text">联系客服</p>
        <div class="customer_content_box">
            <div class="customer_content">
                <div class="customer_content_title">全国服务热线:</div>
                <div class="customer_content_phone">400-050-8588</div>
                <div class="customer_content_qq"><p>客服1 </p><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=130055523&site=qq&menu=yes"><img src="/common/image/qq.png" alt=""></a></div>
                <div class="customer_content_qq"><p>客服2 </p><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=130055523&site=qq&menu=yes"><img src="/common/image/qq.png" alt=""></a></div>
                <div class="customer_content_time">工作时间:9:00-19:00</div>
            </div>
            <div class="triangle_border_right"><img src="/common/image/triangle_border_right.png" alt=""></div>
        </div>
    </div>
    <p></p>
    <div class="auxiliary_to_top">
        <a href="#top">
            <img src="/common/image/to_top.png" alt="" class="auxiliary_image">
            <p class="auxiliary_text">回到顶部</p>
        </a>
    </div>
</div>

<link rel="stylesheet" type="text/css" href="/common/css/index_menu.css">
<div class="header_content">
    <div class="header_center">
        <div class="header_center_contact">
            <img src="/common/image/header_contact.png"/>
            <p>服务热线:<br/>(0755) 2955 8852</p>
        </div>
        <div class="header_center_logo">
            <a href="/"><img src="/common/image/header_logo.png" /></a>
        </div>

        <div class="header_center_cart">
            <a href="">
                <div class="header_center_cart_image">
                    <img src="/common/image/header_center_cart.png" class="header_center_cart_image_img">
                    @if(session()->get('cart') || session()->get('user'))
                        <div class="header_center_cart_image_num">{{ session()->get('cart.num') }}</div>
                    @endif
                </div>
                <p class="header_center_cart_text">购物车</p>
            </a>
        </div>

        <div class="header_center_search">
            <form action="" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                <input type="text" name="name" id="header_center_search_input" placeholder="搜索" value="{{ isset($search)?$search:'' }}"/>
                <button></button>
                <div class="header_center_search_img">
                    <img src="/common/image/header_search.png"/>
                </div>
            </form>
        </div>
        <div class="header_center_search_box"></div>
    </div>
    <div class="header_menu">
        <div class="{{ Request::getPathInfo() == '/'?'action':'' }}"><a href="/">首页</a></div>
        <div class="{{ preg_match('/^\/product.*/',Request::getPathInfo())?'action':'' }}"><a href="/product">全部商品</a></div>
    </div>
</div>
<script type="text/javascript">
    $('#header_center_search_input').focus(function(){
        $('.header_center_search').width(286)
        $(this).width(216)
    }).blur(function(){
        // if(!$(".header_center_search_box").is(":hover")){
        //     $('.header_center_search_box').hide()
        // }else{
        //     $('.header_center_search').width(186)
        //     $(this).width(116)
        // }
    }).keyup(function(){
        var val = $(this).val();
        $.ajax({
            url : '/search/get_product_name',
            type : 'post',
            dataType : 'json',
            data : {_token: '{{ csrf_token()}}',name: val},
            success : function(data){
                if(data.length >= 1){
                    $('.header_center_search_box').html("");
                    for (var i = 0; i < data.length; i++){
                        $('.header_center_search_box').append("<a href='"+"/product/"+data[i]['id']+"'><div>"+data[i]['name']+"</div></a>");
                    }
                    $('.header_center_search_box').show();
                }
            }
        })
    })
</script>


<div class="success">
    <div class="prompt">
        <img src="/common/image/success.png">
        <p></p>
    </div>
</div>
<div class="error">
    <div class="prompt">
        <img src="/common/image/error.png">
        <p></p>
    </div>
</div>

<script type="text/javascript">


    $('.header_top_user').mouseover(function(){
        $('.header_top_user_title').css('background-color','#fff');
        $('.header_top_user_menu').css('top','50px')
    }).mouseout(function(){
        $('.header_top_user_title').css('background-color','#000');
        $('.header_top_user_menu').css('top','-16px')
    })


    $('.auxiliary_customer').mouseover(function(){
        $('.customer_content_box').show();
        $(this).find('.auxiliary_image').attr('src','/common/image/customer_action.png')
        $(this).find('.auxiliary_text').css('color','#99731A')
    }).mouseout(function(){
        $('.customer_content_box').hide();
        $(this).find('.auxiliary_image').attr('src','/common/image/customer.png')
        $(this).find('.auxiliary_text').css('color','#000')
    })

    $('.auxiliary_to_top').mouseover(function(){
        $(this).find('img').attr('src','/common/image/to_top_action.png')
        $(this).find('p').css('color','#99731A')
    }).mouseout(function(){
        $(this).find('img').attr('src','/common/image/to_top.png')
        $(this).find('p').css('color','#000')
    })


    $('.header_search > img').click(function(){
        $('.header_user').fadeOut('200');
        $('.header_menu').fadeOut('200');
        setTimeout('show_form()',200);
        $(document.body).css({
            "overflow-y": "hidden"
        });
    })


    $('.header_form_exit').click(function(){
        $('.header_search > img').animate({'left':0},200);
        $('.header_form').fadeOut('200');
        $('.header_form_select').fadeOut('200');
        setTimeout('hide_form()',200);
        $(document.body).css({
            "overflow-y": "auto"
        });
    })



    @if(isset($search))


        $('.header_user').hide();
    $('.header_menu').hide();
    $('.header_search > img').css('left','-50px');
    $('.header_form').show();

    @endif





        @if(session()->has('success'))
            $('.success p').text("{{ session()->get('success') }}")
    $('.success').show();
    setTimeout("$('.success').hide()",1200)
    @elseif(session()->has('error'))
        $('.error p').text("{{ session()->get('error') }}")
    $('.error').show();
    setTimeout("$('.error').hide()",1200)
    @endif







</script>

@section('content')

@show
<div class="footer_banner">
    <div class="footer_banner_center">
        <div>
            <img src="/common/image/footer_banner_1.png" alt="">
            <div class="footer_banner_title">专业服务</div>
            <div class="footer_banner_text">统一服务标准化，把控品质专业化，为您的需求保驾护航</div>
        </div>
        <p></p>
        <div>
            <img src="/common/image/footer_banner_2.png" alt="">
            <div class="footer_banner_title">价格透明</div>
            <div class="footer_banner_text">阳光下的交易，服务内容透明化，收费项目公开化</div>
        </div>
        <p></p>
        <div>
            <img src="/common/image/footer_banner_3.png" alt="">
            <div class="footer_banner_title">专业便捷</div>
            <div class="footer_banner_text">构建高效跨界平台，一站式专人服务，让您享受随时随地的便捷</div>
        </div>
    </div>
</div>
<div class="footer">
    <div class="footer_center">
        <div class="footer_menu">
            <span><a target="_blank" href="http://www.megacombine.com/">关于我们</a></span>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
            <span><a href="/product/">产品中心</a></span>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
            <span><a target="_blank" href="http://www.megacombine.com/news/">最新咨询</a></span>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
            <span><a target="_blank" href="http://www.megacombine.com/">联系我们</a></span>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
            <span><a target="_blank" href="http://www.megacombine.com/">在线地图</a></span>
        </div>
        <div class="footer_record"><a target="_blank" href="http://www.megacombine.com/">深圳市麦凯莱科技有限公司</a>&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.miitbeian.gov.cn/">@粤ICP备12054071号-1</a>&nbsp;&nbsp;&nbsp;&nbsp;地址：深圳市南山区科技南八路超多维大厦16M&nbsp;&nbsp;&nbsp;&nbsp;电话：(0755) 2955 8852</div>
    </div>
</div>

@section('javascript')
@show
</body>
</html>
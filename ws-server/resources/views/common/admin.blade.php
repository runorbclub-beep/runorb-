<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <link rel="icon" type="image/png" href="../../assets/img/favicon.ico">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>@yield('title')</title>

    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
    <meta name="viewport" content="width=device-width" />


    <!-- Canonical SEO -->
    <!-- <link rel="canonical" href="https://www.creative-tim.com/product/light-bootstrap-dashboard-pro"/> -->

    <!--  Social tags      -->
    <meta name="keywords" content="creative tim, html dashboard, html css dashboard, web dashboard, bootstrap dashboard, bootstrap, css3 dashboard, bootstrap admin, light bootstrap dashboard, frontend, responsive bootstrap dashboard">

    <meta name="description" content="Forget about boring dashboards, get an admin template designed to be simple and beautiful.">

    <!-- Schema.org markup for Google+ -->
    <meta itemprop="name" content="Light Bootstrap Dashboard PRO by Creative Tim">
    <meta itemprop="description" content="Forget about boring dashboards, get an admin template designed to be simple and beautiful.">

    <meta itemprop="image" content="http://s3.amazonaws.com/creativetim_bucket/products/34/original/opt_lbd_pro_thumbnail.jpg">
    <!-- Twitter Card data -->

    <meta name="twitter:card" content="product">
    <meta name="twitter:site" content="@creativetim">
    <meta name="twitter:title" content="Light Bootstrap Dashboard PRO by Creative Tim">

    <meta name="twitter:description" content="Forget about boring dashboards, get an admin template designed to be simple and beautiful.">
    <meta name="twitter:creator" content="@creativetim">
    <meta name="twitter:image" content="http://s3.amazonaws.com/creativetim_bucket/products/34/original/opt_lbd_pro_thumbnail.jpg">
    <meta name="twitter:data1" content="Light Bootstrap Dashboard PRO by Creative Tim">
    <meta name="twitter:label1" content="Product Type">
    <meta name="twitter:data2" content="$29">
    <meta name="twitter:label2" content="Price">

    <!-- Open Graph data -->
    <meta property="og:title" content="Light Bootstrap Dashboard PRO by Creative Tim" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="http://demos.creative-tim.com/light-bootstrap-dashboard-pro/examples/dashboard.html" />
    <meta property="og:image" content="http://s3.amazonaws.com/creativetim_bucket/products/34/original/opt_lbd_pro_thumbnail.jpg"/>
    <meta property="og:description" content="Forget about boring dashboards, get an admin template designed to be simple and beautiful." />
    <meta property="og:site_name" content="Creative Tim" />


    <!-- Bootstrap core CSS     -->
    <link href="{{ asset('admin/css/common/bootstrap.min.css') }}" rel="stylesheet" />

    <!--  Light Bootstrap Dashboard core CSS    -->
    <link href="{{ asset('admin/css/common/light-bootstrap-dashboard.css') }}" rel="stylesheet"/>

    <!--  CSS for Demo Purpose, don't include it in your project     -->
    <link href="{{ asset('admin/css/common/demo.css') }}" rel="stylesheet" />


    <!--     Fonts and icons     -->
    <link href="{{ asset('admin/css/common/font-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin/css/common/pe-icon-7-stroke.css') }}" rel="stylesheet" />
    @section('style')
    @show
</head>
<body>
@section('content')
@show
<div class="fixed-plugin">
    <div class="dropdown"><!-- show-dropdown -->
        <a href="#" data-toggle="dropdown">
            <i class="fa fa-cog fa-2x"> </i>
        </a>
        <ul class="dropdown-menu">
            <li class="header-title">背景风格</li>

            <li class="adjustments-line">
                <a href="javascript:void(0)" class="switch-trigger">
                    <p>滤镜</p>
                    <div class="pull-right">
                        <span class="badge filter {{ session()->get('admin_color') == 'black'? 'active' : ''}}" data-color="black"></span>
                        <span class="badge filter badge-azure {{ session()->get('admin_color') == 'azure'? 'active' : ''}}" data-color="azure"></span>
                        <span class="badge filter badge-green {{ session()->get('admin_color') == 'green'? 'active' : ''}}" data-color="green"></span>
                        <span class="badge filter badge-orange {{ session()->get('admin_color') == 'orange'? 'active' : ''}} {{ empty(session()->get('admin_color'))? 'active' : ''}}" data-color="orange"></span>
                        <span class="badge filter badge-red {{ session()->get('admin_color') == 'red'? 'active' : ''}}" data-color="red"></span>
                        <span class="badge filter badge-purple {{ session()->get('admin_color') == 'purple'? 'active' : ''}}" data-color="purple"></span>
                    </div>
                    <div class="clearfix"></div>
                </a>
            </li>
            <li class="header-title">背景图片</li>
            <li class="{{ session()->get('background') == asset('admin/img/common/full-screen-image-1.jpg')? 'active' : ''}}">
                <a class="img-holder switch-trigger" href="javascript:void(0)">
                    <img src="{{ asset('admin/img/common/full-screen-image-1.jpg') }}">
                </a>
            </li>
            <li class="{{ session()->get('background') == asset('admin/img/common/full-screen-image-2.jpg')? 'active' : ''}}">
                <a class="img-holder switch-trigger" href="javascript:void(0)">
                    <img src="{{ asset('admin/img/common/full-screen-image-2.jpg') }}">
                </a>
            </li>
            <li class="{{ session()->get('background') == asset('admin/img/common/full-screen-image-3.jpg')? 'active' : ''}}">
                <a class="img-holder switch-trigger" href="javascript:void(0)">
                    <img src="{{ asset('admin/img/common/full-screen-image-3.jpg') }}">
                </a>
            </li>
            <li class="{{ session()->get('background') == asset('admin/img/common/full-screen-image-4.jpg')? 'active' : ''}}">
                <a class="img-holder switch-trigger" href="javascript:void(0)">
                    <img src="{{ asset('admin/img/common/full-screen-image-4.jpg') }}" onclick="">
                </a>
            </li>

        </ul>
    </div>
</div>

</body>
<!--   Core JS Files and PerfectScrollbar library inside jquery.ui   -->
<script src="{{ asset('admin/js/common/jquery.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('admin/js/common/jquery-ui.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('admin/js/common/bootstrap.min.js') }}" type="text/javascript"></script>


<!--  Forms Validations Plugin -->
<script src="{{ asset('admin/js/common/jquery.validate.min.js') }}"></script>

<!--  Plugin for Date Time Picker and Full Calendar Plugin-->
<!-- <script src="{{ asset('admin/js/common/moment.min.js') }}"></script> -->

<!--  Date Time Picker Plugin is included in this js file -->
<!-- <script src="{{ asset('admin/js/common/bootstrap-datetimepicker.js') }}"></script> -->

<!--  Select Picker Plugin -->
<script src="{{ asset('admin/js/common/bootstrap-selectpicker.js') }}"></script>

<!--  Checkbox, Radio, Switch and Tags Input Plugins -->
<script src="{{ asset('admin/js/common/bootstrap-checkbox-radio-switch-tags.js') }}"></script>

<!--  Charts Plugin -->
<!-- <script src="{{ asset('admin/js/common/chartist.min.js') }}"></script> -->

<!--  Notifications Plugin    -->
<script src="{{ asset('admin/js/common/bootstrap-notify.js') }}"></script>

<!-- Sweet Alert 2 plugin -->
<!-- <script src="{{ asset('admin/js/common/sweetalert2.js') }}"></script> -->

<!-- Vector Map plugin -->
<!-- <script src="{{ asset('admin/js/common/jquery-jvectormap.js') }}"></script> -->

<!--  Google Maps Plugin    -->
<!-- <script src="{{ asset('admin/js/common/aa743e8f448a4792bad10d201a7080f6.js') }}"></script> -->

<!-- Wizard Plugin    -->
<script src="{{ asset('admin/js/common/jquery.bootstrap.wizard.min.js') }}"></script>

<!--  Datatable Plugin    -->
<script src="{{ asset('admin/js/common/bootstrap-table.js') }}"></script>

<!--  Full Calendar Plugin    -->
<!-- <script src="{{ asset('admin/js/common/fullcalendar.min.js') }}"></script> -->

<!-- Light Bootstrap Dashboard Core javascript and methods -->
<script src="{{ asset('admin/js/common/light-bootstrap-dashboard.js') }}"></script>

<!--   Sharrre Library    -->
<script src="{{ asset('admin/js/common/jquery.sharrre.js') }}"></script>

<!-- Light Bootstrap Dashboard DEMO methods, don't include it in your project! -->
<script src="{{ asset('admin/js/common/demo.js') }}"></script>
<!-- <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-46172202-1', 'auto');
  ga('send', 'pageview');

</script> -->
@section('js')
@show
@section('javascript')
@show
</html>
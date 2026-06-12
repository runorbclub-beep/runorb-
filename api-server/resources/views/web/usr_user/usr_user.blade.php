<table>
    <thead>
        <tr>
            <th>手机号（必填）</th>
            <th>姓名（最多32个字）</th>
            <th>性别（男/女）</th>
            <th>生日（比如：1989/08/08 或 1989-08-08）</th>
            <th>微信（最多16个字符）</th>
            <th>备注</th>
            <th>省</th>
            <th>市</th>
            <th>区/县</th>
            <th>详细地址</th>
            <th>积分（限整数）</th>
            <th>储值余额（单位：元，精确小数点后两位）</th>
            <th>储值赠送金（单位：元，精确小数点后两位）</th>
            <th>标签（注：多个用、隔开，标签不存在则新建标签。如：标签A、标签B）</th>
            <th>会员等级值（VIP+等级值。如：VIP1）</th>
            <th>成长值（限整数）</th>
            <th>[销售员]手机号（11位手机号）</th>
        </tr>
    </thead>
    <tbody>
@foreach($list as $k => $v)
        <tr>
            <td>{{ $v['phone'] }}</td>
            <td>{{ empty($v['user_name']) ? : hide_nickname($v['user_name']) }}</td>
            <td>{{--{{ $v['sys_sex_id'] }}--}}</td>
            <td>{{--{{ empty($v['birthday']) ? : $v['birthday'] }}--}}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>{{ $v['integral'] }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
@endforeach
    </tbody>
</table>

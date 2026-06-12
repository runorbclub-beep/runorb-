<table>
    <thead>
        <tr>
            <th>序号</th>
            <th>姓名</th>
            <th>性别</th>
            <th>手机号</th>
            <th>摇跑指数</th>
        </tr>
    </thead>
    <tbody>
@foreach($user_achievement as $k=>$v)
        <tr>
            <td>{{ $k+1}}</td>
            <td>{{ $v['name'] }}</td>
            <td>{{ $v['sex'] }}</td>
            <td>{{ $v['phone'] }}</td>
            <td>{{ $v['runball_exponent'] }}</td>
        </tr>
@endforeach
    </tbody>
</table>

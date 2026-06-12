<table>
    <thead>
        <tr>
            <th>序号</th>
            <th>开始时间</th>
            <th>结束时间</th>
            <th>所属组织</th>
            <th>部门</th>
            <th>姓名</th>
            <th>性别</th>
{{--            <th>手机号</th>--}}
            <th>打卡距离(km)</th>
            <th>每日目标距离(km)</th>
            <th>打卡天数</th>
        </tr>
    </thead>
    <tbody>
@foreach($user_play as $k=>$v)
        <tr>
            <td>{{ $k+1}}</td>
            <td>{{ $v['s_time'] }}</td>
            <td>{{ $v['e_time'] }}</td>
            <td>{{ $v['title'] }}</td>
            <td>{{ $v['department'] }}</td>
            <td>{{ $v['name'] }}</td>
            <td>{{ $v['sex'] }}</td>
{{--            <td>{{ $v['phone'] }}</td>--}}
            <td>{{ $v['distance'] }}</td>
            <td>{{ $v['day_distance'] }}</td>
            <td>{{ $v['day_count'] }}</td>
        </tr>
@endforeach
    </tbody>
</table>

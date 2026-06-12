<table>
    <thead>
    <tr>
        <th>排名</th>
        <th>账户昵称</th>
        <th>性别（男/女）</th>
        <th>摇加油助力距离(km)</th>
        <th>获得积分</th>
        <th>手机号</th>
        <th>马匹名称</th>
        <th>马匹号</th>
        <th>时间</th>
{{--        <th>用户ID</th>--}}
{{--        <th>类型</th>--}}
    </tr>
    </thead>
    <tbody>
    @foreach($list as $k => $v)
        <tr>
            <td>{{ $k+1 }}</td>
            <td>{{ $v['user_name'] }}</td>
            <td>@switch($v['sys_sex_id'])
                    @case(1791224340025344)
                        男
                    @break
                    @case(1791224373579776)
                        女
                    @break
                    @case(1791224373579777)
                        保密
                    @break
                    @case(1791224373579778)
                        /
                    @break
                @endswitch
            </td>
            <td>{{ round($v['distance']/1000,2) }}</td>
            <td>{{$v['integral']}}</td>
            <td>{{$v['phone']}}</td>
            <td>{{$v['title']}}</td>
            <td>{{$v['index']+1}}</td>
            <td>{{ date('Y-m-d',$v['datetime']) }}</td>
{{--            <td>{{ $v['user_id'] }}</td>--}}
        </tr>
    @endforeach
    </tbody>
</table>

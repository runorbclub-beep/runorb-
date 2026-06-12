<table>
    <thead>
    <tr>
        <th>排名</th>
        <th>账户昵称</th>
        <th>性别（男/女）</th>
        <th>地区</th>
        <th>成绩</th>
        <th>单位</th>
        <th>上榜时间</th>
{{--        <th>用户ID</th>--}}
{{--        <th>类型</th>--}}
    </tr>
    </thead>
    <tbody>
    @foreach($list as $k => $v)
        <tr>
            <td>{{ $v['index'] }}</td>
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
            <td>{{ $v['address'] }}</td>
            <td>{{empty($v['value']) ? '/' : $v['value']}}</td>
            <td>{{empty($v['unit']) ? '/' : $v['unit']}}</td>
            <td>{{ $v['value'] ? $v['time'] : "/" }}</td>
{{--            <td>{{ $v['user_id'] }}</td>--}}
        </tr>
    @endforeach
    </tbody>
</table>

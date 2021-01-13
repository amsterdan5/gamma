layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        table = layui.table;

    var user_index;

    table.render({
        elem: '#adsTable',
        url: '/admin/ads/list',
        method: 'post',
        toolbar: '#toolbar',
        defaultToolbar: ['filter', 'exports', 'print'],
        response: {
            statusCode: 200
        },
        parseData: function(res){ //res 即为原始返回的数据
            return {
                "code": res.code,
                "msg": res.message,
                "count": res.data.total,
                "data": res.data.list
            };
        },
        cols: [[
            {type: "checkbox", width: 50},
            {field: 'id', width: 80, title: 'ID', sort: true},
            {field: 'content', minWidth: 200, title: '描述'},
            {title: '操作', minWidth: 150, toolbar: '#adsTableBar', align: "center"}
        ]],
        limits: [10, 15, 20, 25, 50, 100],
        limit: 15,
        page: true,
        skin: 'line'
    });

    /**
     * toolbar监听事件
     */
    table.on('toolbar(adsTableFilter)', function (obj) {
        if (obj.event === 'table-ads-add') {  // 监听添加操作
            user_index = layer.open({
                title: '添加广告',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: '/admin/ads/add',
            });
            $(window).on("resize", function () {
                layer.full(user_index);
            });
        }
    });

    //监听表格复选框选择
    table.on('checkbox(adsTableFilter)', function (obj) {
        console.log(obj)
    });

    table.on('tool(adsTableFilter)', function (obj) {
        var data = obj.data;
        if (obj.event === 'table-ads-edit') {
            user_index = layer.open({
                title: '编辑广告',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: '/admin/ads/edit?ads_id=' + data.id,
            });
            return false;
        } else if (obj.event === 'table-ads-delete') {
            layer.confirm('确认删除吗', function (user_index) {
                $.ajax({
                    url: '/admin/ads/del',
                    type: 'post',
                    dataType: 'json',
                    data: {ads_id: data.id},
                    error: function () {
                        layer.msg('请重试');
                    },
                    success: function (res) {
                        if (res.code == 200) {
                            layer.msg(res.message, function() {
                                // 重新加载
                                // table.reload('adsTable',{});
                            });
                        } else {
                            layer.msg(res.message);
                        }
                    }
                });
                return false;
            });
        }
    });


    form.on('submit(edit-ads)', function(data) {
        $.ajax({
            url: '/admin/ads/edit',
            type: 'post',
            dataType: 'json',
            data: $('#edit-ads-form').serialize(),
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        layer.close(user_index);
                        // 重新加载
                        window.location.href='/admin/ads/list';
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });

    form.on('submit(add-ads)', function(data) {
        $.ajax({
            url: '/admin/ads/add',
            type: 'post',
            dataType: 'json',
            data: $('#add-ads-form').serialize(),
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        layer.close(user_index);
                        window.location.href='/admin/ads/list';
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });
});
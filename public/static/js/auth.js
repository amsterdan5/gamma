layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        table = layui.table;

    var user_index;

    table.render({
        elem: '#currentTableId',
        url: '/admin/authority/user_list',
        method: 'post',
        toolbar: '#toolbarDemo',
        defaultToolbar: ['filter', 'exports', 'print', {
            title: '提示',
            layEvent: 'LAYTABLE_TIPS',
            icon: 'layui-icon-tips'
        }],
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
            {field: 'name', minWidth: 120, title: '用户名'},
            {title: '操作', minWidth: 150, toolbar: '#currentTableBar', align: "center"}
        ]],
        limits: [10, 15, 20, 25, 50, 100],
        limit: 15,
        page: true,
        skin: 'line'
    });

    // 重新渲染还没完成，会刷新页面
    // 监听搜索操作
    form.on('submit(data-search-btn)', function (data) {
        table.reload('currentTableId', {
            elem: '#currentTableId',
            url: '/admin/authority/user_list',
            method: 'post',
            where: {name: $('#search-name').val()},
            toolbar: '#toolbarDemo',
            defaultToolbar: ['filter', 'exports', 'print', {
                title: '提示',
                layEvent: 'LAYTABLE_TIPS',
                icon: 'layui-icon-tips'
            }],
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
            limits: [10, 15, 20, 25, 50, 100],
            limit: 15,
            page: true,
            skin: 'line'
        });
        return false;
    });

    /**
     * toolbar监听事件
     */
    table.on('toolbar(currentTableFilter)', function (obj) {
        if (obj.event === 'add') {  // 监听添加操作
            user_index = layer.open({
                title: '添加用户',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: '/admin/authority/add_user',
            });
            $(window).on("resize", function () {
                layer.full(user_index);
            });
        }
    });

    //监听表格复选框选择
    table.on('checkbox(currentTableFilter)', function (obj) {
        console.log(obj)
    });

    table.on('tool(currentTableFilter)', function (obj) {
        var data = obj.data;
        if (obj.event === 'edit') {
            user_index = layer.open({
                title: '编辑用户',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: '/admin/authority/edit_user?admin_id=' + data.id,
            });
            return false;
        } else if (obj.event === 'edit_auth') {
            user_index = layer.open({
                title: '编辑用户权限',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: '/admin/authority/edit?admin_id=' + data.id,
            });
            return false;
        } else if (obj.event === 'delete') {
            layer.confirm('确认删除吗', function (user_index) {
                $.ajax({
                    url: '/admin/authority/del_user',
                    type: 'post',
                    dataType: 'json',
                    data: {id: data.id},
                    error: function () {
                        layer.msg('请重试');
                    },
                    success: function (res) {
                        if (res.code == 200) {
                            layer.msg(res.message, function() {
                                // 重新加载
                                // table.reload('currentTableId',{});
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


    form.on('submit(edit-user)', function(data) {
        $.ajax({
            url: '/admin/authority/edit_user',
            type: 'post',
            dataType: 'json',
            data: $('#edit_user').serialize(),
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        layer.close(user_index);
                        // 重新加载
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });


    form.on('submit(add-user)', function(data) {
        $.ajax({
            url: '/admin/authority/add_user',
            type: 'post',
            dataType: 'json',
            data: $('#add_user').serialize(),
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        layer.close(user_index);
                        window.location.href='/admin/authority/user_list';
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });

    form.on('submit(edit-auth)', function(data) {
        $.ajax({
            url: '/admin/authority/edit',
            type: 'post',
            dataType: 'json',
            data: $('#user_auth_id').serialize(),
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        layer.close(user_index);
                        // 重新加载
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });
});
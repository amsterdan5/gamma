layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        table = layui.table;

    var dd = '<div class="layui-table-cell laytable-cell-1-0-3"> <input type="checkbox" name="active" value="" title="启用" lay-filter="active"><div class="layui-unselect layui-form-checkbox layui-form-checked"><span>启用</span><i class="layui-icon layui-icon-ok"></i></div> </div>';

    // 管理员列表参数
    var user_list_table_options = {
        elem: '#user_list_table',
        url: '/admin/authority/user_list',
        method: 'post',
        toolbar: '#user_list_toolbar',
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
            {field: 'name', minWidth: 120, title: '用户名'},
            {field: 'active', width: 120, title: '状态', templet:function(d){
                var checked = ''
                    is_checked = '';
                if(d.active === '1') {
                    checked = ' layui-form-checked ';
                    is_checked = 'checked';
                }
                var string =  '<input type="checkbox" name="active" value="' +d.id+ '" title="启用" lay-filter="active" '+ is_checked +' ><div class="layui-form-checkbox layui-unselect ' + checked + '"><span>启用</span><i class="layui-icon layui-icon-ok"></i></div>';
                return string;
            }, unresize: true},
            {title: '操作', minWidth: 150, toolbar: '#user_list_table_bar', align: "center"}
        ]],
        limits: [10, 15, 20, 25, 50, 100],
        limit: 15,
        page: true,
        skin: 'line'
    };

    table.render(user_list_table_options);

    // 监听搜索操作
    form.on('submit(data-search-btn)', function () {
        user_list_table_options.where = {name: $('#search-name').val()};
        table.reload('user_list_table', user_list_table_options);
        return false;
    });

    form.verify({
        pass: [
          /^[\S]{6,12}$/
          ,'密码必须6到12位，且不能出现空格'
        ],
    });

    /**
     * toolbar监听事件
     */
    table.on('toolbar(user_list_table_filter)', function (obj) {
        if (obj.event === 'add') {  // 监听添加操作
            var user_index = layer.open({
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

    table.on('tool(user_list_table_filter)', function (obj) {
        var data = obj.data;
        if (obj.event === 'edit') {
            var user_index = layer.open({
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
            var user_index = layer.open({
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
            layer.confirm('确认删除吗', function () {
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
                                obj.del()
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

    form.on('submit(edit-user)', function() {
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
                        // 关闭当前页面
                        parent.layer.close(parent.layer.getFrameIndex(window.name))
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });

    form.on('submit(add-user)', function() {
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
                        // 关闭当前页面
                        parent.layer.close(parent.layer.getFrameIndex(window.name))
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });

    form.on('submit(edit-auth)', function() {
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
                        parent.layer.close(parent.layer.getFrameIndex(window.name))
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });

    form.on('checkbox(active)', function(obj){
        var active = obj.elem.checked === true? 1:0;
        $.ajax({
            url: '/admin/authority/user_active',
            type: 'post',
            dataType: 'json',
            data: {
                id: this.value,
                active: active,
            },
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        parent.layer.close(parent.layer.getFrameIndex(window.name))
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });
});
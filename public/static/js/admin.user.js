layui.use(['layer', 'form'], function(){
  var layer = layui.layer,
    form = layui.form,
    $ = layui.jquery;

    $('#reset').on('click', function(){
        $('#uname').val('');
        $('#pwd').val('');
    });

    form.on('submit(goto)', function(data) {
        $.ajax({
            url: '/admin/user/enter',
            type: 'post',
            dataType: 'json',
            data: $('#admin-login').serialize(),
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        window.location.href = '/admin/index/index';
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
        return false;
    });
});
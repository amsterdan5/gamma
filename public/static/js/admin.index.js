layui.use(['jquery', 'layer', 'miniAdmin'], function () {
    var $ = layui.jquery,
        layer = layui.layer,
        miniAdmin = layui.miniAdmin;

    var options = {
        iniUrl: "/admin/index/menu",    // 初始化接口
        urlHashLocation: true,      // 是否打开hash定位
        bgColorDefault: false,      // 主题默认配置
        multiModule: false,          // 是否开启多模块
        menuChildOpen: false,       // 是否默认展开菜单
        loadingTime: 0,             // 初始化加载时间
        pageAnim: true,             // iframe窗口动画
        maxTabNum: 20,              // 最大的tab打开数量
    };
    miniAdmin.render(options);

    $('.reset-pwd').on("click", function () {
        $.ajax({
            url: '/admin/index/reset_pwd',
            type: 'post',
            dataType: 'json',
            data: $('#reset_pwd_form').serialize(),
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        window.location.href = '/admin/user/logout';
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
    });

    $('.login-out').on("click", function () {
        $.ajax({
            url: '/admin/user/logout',
            error: function () {
                layer.msg('请重试');
            },
            success: function (res) {
                if (res.code == 200) {
                    layer.msg(res.message, function() {
                        window.location.href = '/admin/user/login';
                    });
                } else {
                    layer.msg(res.message);
                }
            }
        });
    });
});
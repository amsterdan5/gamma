layui.use(['form'], function() {
    var jQ = layui.jquery;

    jQ('#clear-url').on('click', function() {
        jQ('#url').val('');
    });

    jQ('#anaylyze').on('click', function() {
        if(!jQ('#url').val()) {
            layer.msg('请输入链接');
            return false;
        }
        getData();
        return false;
    });

    // 复制链接
    jQ('#cp-link').on('click', function() {
        var url = jQ('#play').attr('src');
        jQ('#video-link').val(url);
        jQ('#video-link').select();
        document.execCommand("Copy");
        layer.msg("链接复制成功");
    });

    // 下载文件
    jQ('#download-link').on('click', function() {
        var url = jQ('#play').attr('src');
        window.location.href='/index/download?url='+url;
        return false;
    });

    var url = getUrlParam('url');
    if(url) {
        jQ('#url').val(url);
        getData();
    }

    function getData() {
        jQ.ajax({
            url:'/index/analyze',
            type: 'post',
            dataType: 'json',
            data: {url: jQ('#url').val()},
            error: function(res) {
                layer.msg(res.message);
            },
            success: function(res) {
                if (res.code === 200) {
                    jQ('#analyze_video').show();
                    jQ('#play').attr('src', res.data.video_url);
                    jQ('#play').attr('muted', false);
                } else {
                    layer.msg(res.message);
                }
            }
        });
    };

    function getUrlParam(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
        var r = window.location.search.substr(1).match(reg);  //匹配目标参数
        if (r != null) return unescape(r[2]); return null; //返回参数值
    };
});
layui.use(['carousel','layer'], function() {
    var carousel = layui.carousel,
        $ = layui.jquery;

    var ren = carousel.render({
        elem: '#banner_list',
        interval: 1800,
        arrow: 'none',
        trigger: 'click'
    });
    carousel.render({
        elem: '#app-banner',
        interval: 1800,
        arrow: 'none',
        trigger: ''
    });

    $('#banner_list img').on('click',function() {
        ren.reload()
        window.location.href = $(this).attr('data-href');
        return false;
    })

    $('#goto-anaylyze').on('click', function() {
        var url = $('#url').val();
        if(url) {
            window.location.href = '/index/detail?url='+ url;
        }
    });

    $('#clear-url').on('click', function() {
        $('#url').val('');
    });

    $('.ads-video').attr('autoplay',true);
    
    $('.developing').on('click', function() {
        layer.msg('敬请期待...');
    });
});
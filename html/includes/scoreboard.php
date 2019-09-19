<?php
    require_once("common.php");
    require_once("rankings.php");
?>
<script>
    $(document).ready(function() {
        var element = $("#line_momentum");
        element.detach();
        $('body').append(element);
        $("#main_container").remove();
        $("#line_momentum").css({'height': ($(window).height()) + 'px'});

        // Note: dirty patch for proper resizing / redrawing
        var $_ = function() {
            if (chart == null)
                setTimeout($_, 10);
            else
                chart.render();
        };
        setTimeout($_, 10);

        setTimeout(function() {
            location.reload();
        }, SCOREBOARD_PAGE_RELOAD);

    });
</script>

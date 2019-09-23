<script>
    $(document).ready(function() {
        var element = $("#chat_messages").closest(".card");
        element.detach();
        $('body').append(element);
        $("#main_container").remove();
        $("#chat_messages").css({'height': ($(window).height() - ($("#chat_messages").offset().top + $("#chat_message").height() + $(".card-header").offset().top)) + 'px'});
        $(".fa-window-restore").remove();
    });
</script>

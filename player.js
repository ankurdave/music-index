var player;

$(document).ready(function() {
    $("#jquery_jplayer_1").jPlayer({swfPath: ""});
    $(".playlink").click(function(event) {
        event.preventDefault();
        ($("#jquery_jplayer_1")
         .jPlayer("clearMedia")
         .jPlayer("setMedia", {mp3: $(this).attr("href")})
         .jPlayer("play"));
        return false;
    });

    player = new followAlong('jp_interface_1');
});
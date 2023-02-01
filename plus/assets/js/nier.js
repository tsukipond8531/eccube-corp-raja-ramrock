
if (matchMedia('(max-width: 798px)').matches) {
  // ウィンドウサイズが798px以下のとき
  $(function() {
    var topBtn = $('#totop');    
    topBtn.hide();
    //スクロールが100に達したらボタン表示
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            topBtn.fadeIn();
        } else {
            topBtn.fadeOut();
        }
    });
    //スクロールしてトップ
    topBtn.click(function () {
        $('body,html').animate({
            scrollTop: 0
        }, 500);
        return false;
    });
});

} else {
  // それ以外
  $(function() {
    var topBtn = $('#totop');    
    topBtn.hide();
    //スクロールが100に達したらボタン表示
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            topBtn.fadeIn();
        } else {
            topBtn.fadeOut();
        }
    });
    //スクロールしてトップ
    topBtn.click(function () {
        $('body,html').animate({
            scrollTop: 0
        }, 500);
        return false;
    });
});
}

$(function(){
	$('a[href^=#]').click(function(){
		var speed = 1000;
		var href= $(this).attr("href");
		var target = $(href == "#" || href == "" ? 'html' : href);
		var position = target.offset().top;
		$("html, body").animate({scrollTop:position}, speed, "swing");
		return false;
	});
});

jQuery(function ($) {
  //コンテンツを非表示に
  $(".acod__box").css("display", "none");
  
  //タイトルがクリックされたら
  $(".acod__ttl").click(function () {
    //".open"はaccordion-titleでもOK
    //クリックしたaccordion-title以外の全てのopenを取る
    $(".acod__ttl").not(this).removeClass("open");
    //クリックされたtitle以外のcontentを閉じる
    $(".acod__ttl").not(this).next().slideUp(300);
    //$(".accordion-title").not(this).next().slideUp(300);
    //thisにopenクラスを付与
    $(this).toggleClass("open");
    //thisのcontentを展開、開いていれば閉じる
    $(this).next().slideToggle(300);
  });
});


function sound1()
{
	// [ID:sound-file]の音声ファイルを再生[play()]する
	document.getElementById( 'sound-file1' ).play() ;
}
function sound2()
{
	// [ID:sound-file]の音声ファイルを再生[play()]する
	document.getElementById( 'sound-file2' ).play() ;
}
function sound3()
{
	// [ID:sound-file]の音声ファイルを再生[play()]する
	document.getElementById( 'sound-file3' ).play() ;
}
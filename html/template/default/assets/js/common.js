(function($){
	$(document).ready(function(){
		
		//setting
		//on off rollover,advance load
		var src_on = "_on.",
			src_off = "_off.",

		//to page,to top
			offset = 0,
			scroll_speed = "slow",

		//opacty rollover
			on_speed = 0,
			off_speed = "fast",
			transp = 0.7,

		//pngfix exclusion
			png_exc = "exc";

		//to page
		$("a.to_page").click(function(){
			var href = $(this).attr("href");
			href = href.split("#");
			if($("#" + href[1]).size() > 0 || href[0] == ""){
				var p = $("#" + href[1]).offset().top - offset;
				$("html,body").animate({
					scrollTop:p
				},scroll_speed);
				
			}
			return false;
		});

		//to top
		$("a.to_top").click(function(){
			$("html,body").animate({
				scrollTop:0
			},scroll_speed);
			return false;
		});

		if (navigator.userAgent.match(/(iPhone|iPad|iPod|Android)/)) {
		  $(function() {
			$('.tel').each(function() {
			  var str = $(this).html();
			  if ($(this).children().is('img')) {
				$(this).html($('<a>').attr('href', 'tel:' + $(this).children().attr('alt').replace(/-/g, '')).append(str + '</a>'));
			  } else {
				$(this).html($('<a>').attr('href', 'tel:' + $(this).text().replace(/-/g, '')).append(str + '</a>'));
			  }
			});
		  });
		}
		
		$(function(){
			var ua = navigator.userAgent;
			if(ua.indexOf('iPhone') > 0 || ua.indexOf('Android') > 0){
				$('.tel-link').each(function(){
					var str = $(this).text();
					$(this).html($('<a>').attr('href', 'tel:' + str.replace(/-/g, '')).append(str + '</a>'));
				});
			}
		});
		
		$(".btnmenu").click(function(){
			if($(".gnavwrap").css("opacity") == "0"){
				$('.btnmenu').addClass('is_active');
				$('.gnavwrap').addClass('is_active');
				$("body").addClass("is_active");
			}else{
				$("body").removeClass("is_active");
				$(".btnmenu").removeClass('is_active');
				$(".gnavwrap").removeClass('is_active');
			}
			return false;
		});
		
			
		
		$(".mg > a").click(function(){
			$(this).next(".subnavblock").slideToggle();
			$(this).toggleClass("open"); 
		});	
		
		$(".acctitle").click(function(){
			$(this).next(".acccont").slideToggle();
			$(this).toggleClass("open");    
		});

		$('.about03_tabBtn_item').click(function() {
			var index = $('.about03_tabBtn li').index(this);
			$('.about03_tabBtn li').removeClass('is_active');
			$('.about03_tab li').removeClass('is_active');
			$(this).addClass('is_active');
			$('.about03_tab li').eq(index).addClass('is_active');
		});

		$(".float_bnr_close").click(function(){
			$('.float_bnr').hide();
		});	
		
	});
	
	$(window).on('load resize', function(){
		$('.mh01').matchHeight();
		$('.mh02').matchHeight();
		
	});
	
	$(window).load(function(){
		
		setTimeout(function(){
			
		},500);
		setTimeout(function(){
			
		},1000);

				
	});	
	
	if($(".headerwrap").size() > 0){}
	
		$(window).on('scroll', function () {
			if ($('.headerwrap').height() < jQuery(this).scrollTop()) {
			$('.headerwrap').addClass('sc');
			$('body').addClass('sc');
			} else {
				$('.headerwrap').removeClass('sc');
				$('body').removeClass('sc');
			}
		});		 
		function add_class_in_scrolling(target) {
		var winScroll = $(window).scrollTop();
		var winHeight = $(window).height();
		var scrollPos = winScroll + winHeight;
		if(target.offset().top < scrollPos) {
			target.addClass('is-show');
		}
	}
	
	
	$(window).on('load resize', function() {
	  var winW = $(window).width();
	  var devW = 750;
	  if (winW <= devW) {
		  
		$('body').addClass("typesp");
		$('body').removeClass("typepc");		
		
		$('.mg').hover(function(){
			$("div:not(:animated)", this).removeClass("mgavctive");
		}, function(){
			$("div.subnavblock",this).removeClass("mgavctive");
		});
		$('.mg').mouseover(function(e) {
			$('.megawrap').removeClass("mgavctive");
			$('.headerwrap').removeClass("mgavctive");
		});
		$(".headerwrap.mgavctive").hover(function() {
		    $('.megawrap').removeClass("mgavctive");
			$('.headerwrap').removeClass("mgavctive");
		}, function() {
		    $('.megawrap').removeClass("mgavctive");
			$('.headerwrap').removeClass("mgavctive");
		});
		
	  } else {
		
		$('body').addClass("typepc");
		$('body').removeClass("typesp");
		  
		$('.mg').hover(function(){
			$("div:not(:animated)", this).addClass("mgavctive");
		}, function(){
			$("div.subnavblock",this).removeClass("mgavctive");
		});
		$('.mg').mouseover(function(e) {
			$('.megawrap').addClass("mgavctive");
			$('.headerwrap').addClass("mgavctive");
		});	
		$('.mg').hover(function(){
			$(".megawrap").addClass("mgavctive");
		}, function(){
			$(".megawrap").removeClass("mgavctive");
		});
		$(".headerwrap").hover(function() {
		}, function() {
		    $('.megawrap').removeClass("mgavctive");
			$('.headerwrap').removeClass("mgavctive");
		});
		
	  }
	});
	
	
	
	
	
	
	
})(jQuery);

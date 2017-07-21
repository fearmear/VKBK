/*
 
  Name: VKBK.js
  ================================================
  Base functions for all pages and page specific.
  
*/

// GLOBAL
// ===============================================

$(".tip").tooltip();	// tooltips

$(document).pjax('a[data-pjax]', '#pj-content', {timeout:1000});	// pJax navigaion
$(document).pjax('a[data-pjauth]', '.nav-sidebar', {timeout:1000});

// Fancybox v3
// =====================================

// close fancybox
function fbox_close(){
    $.fancybox.close();
}

// global - video container - maximize
// ===========================
function fbox_maximize(){
    $(".fbox-maximize").hide();
    $(".fbox-minimize").show();
    $('.fbox-controls').removeClass('fbox-minimized');
    $('.fancybox-slide--iframe .fancybox-content').css({"max-width":"80%","max-height":"80%","padding":"0","background-color":"#fff"});
    $('.fancybox-slide--iframe').css({"overflow":"hidden"});
    $('.fancybox-container').css({"width":"100%", "height":"100%", "top":"0", "bottom":"initial", "left":"0"});
    // For video we need scroll
    jQuery("html").addClass("fancybox-enabled");
    jQuery(".fancybox-container").addClass("fancybox-is-open");
    Cookies.set('gplayer_minimized','0',{ expires: 365 }); // Save state to cookies
}
// ===========================X

// global - video container - minimize
// ===========================
function fbox_minimize(){
    $(".fbox-maximize").show();
    $(".fbox-minimize").hide();
    $('.fancybox-slide--iframe .fancybox-content').css({"max-width":"100%","max-height":"100%","padding":"25px 5px 5px","background-color":"rgba(0,0,0,.9)"});
    $('.fancybox-slide--iframe').css({"overflow":"hidden"});
    $('.fancybox-container').css({"width":"430px", "height":"265px", "top":"initial", "bottom":"55px", "left":"60px"});
    $('.fbox-controls').addClass('fbox-minimized');
    // For video we need scroll
    jQuery("html").removeClass("fancybox-enabled");
    jQuery(".fancybox-container").removeClass("fancybox-is-open");
    Cookies.set('gplayer_minimized','1',{ expires: 365 }); // Save state to cookies
}
// ===========================X

// global - video container
// ===========================
function fbox_video_global( url, local ){
    var fbox_controls = '<div class="fbox-controls"><div class="fbox-close" onclick="javascript:fbox_close();"><i class="fa fa-times"></i></div><div class="fbox-minimize" onclick="javascript:fbox_minimize();"><i class="fa fa-window-minimize"></i></div><div class="fbox-maximize" onclick="javascript:fbox_maximize();"><i class="fa fa-window-maximize"></i></div></div>';
    var minimized = Cookies.get('gplayer_minimized'); // Restore state from cookies
    
    if(url != '' && local === 1){
	// Close any fancybox
	jQuery.fancybox.close();
	// Open fancybox container with URL
	jQuery.fancybox.open({
	    src			: url,
	    type		: 'iframe',
	    loop		: false,
	    keyboard		: false,
	    arrows		: false,
	    infobar		: false,
	    toolbar		: false,
	    animationEffect	: false,
	    transitionEffect	: false,
	    touch		: {
		vertical	: false,
	    },
	    hash		: false,
	    clickOutside	: false,
	    clickSlide		: false,
	    afterShow		: function(){
		// Add custom buttons
		jQuery(".fancybox-slide--iframe .fancybox-content").append(fbox_controls);
		if(minimized == 1){ fbox_minimize(); }
	    },
	});
    } else { return false; }
}
// ===========================X

// Modal: Create jQuery plugin
// ===========================

$.fn.fancyMorph = function( opts ) {

  var Morphing = function( $btn, opts ) {
    var self = this;

    self.opts = $.extend({
      type       : 'iframe',
      animationEffect : false,
      infobar    : false,
      buttons    : ['close'],
      smallBtn   : false,
      touch      : false,
      baseClass  : 'fancybox-morphing',
      afterClose : function() {
        self.close();
      }
    }, opts);

    self.init( $btn );
  };

  Morphing.prototype.init = function( $btn ) {
    var self = this;

    self.$btn = $btn.addClass('morphing-btn');

    self.$clone = $('<div class="morphing-btn-clone" />')
      .hide()
      .insertAfter( $btn );

    // Add wrapping element and set initial width used for positioning
    $btn.wrap( '<span class="morphing-btn-wrap"></span>' ).on('click', function(e) {
      e.preventDefault();

      self.start();
    });

  };

  Morphing.prototype.start = function() {
    var self = this;

    if ( self.$btn.hasClass('morphing-btn_circle') ) {
      return;
    }

    // Set initial width, because it is not possible to start CSS transition from "auto"
    self.$btn.width( self.$btn.width() ).parent().width( self.$btn.outerWidth() );

    self.$btn.off('.fm').on("transitionend.fm webkitTransitionEnd.fm oTransitionEnd.fm MSTransitionEnd.fm", function(e) {

      if ( e.originalEvent.propertyName === 'width' ) {
        self.$btn.off('.fm');

        self.animateBg();
      }

    }).addClass('morphing-btn_circle');

  };

  Morphing.prototype.animateBg = function() {
    var self = this;

    self.scaleBg();

    self.$clone.show();

    // Trigger repaint
    self.$clone[0].offsetHeight;

    self.$clone.off('.fm').on("transitionend.fm webkitTransitionEnd.fm oTransitionEnd.fm MSTransitionEnd.fm", function(e) {
      self.$clone.off('.fm');

      self.complete();

    }).addClass('morphing-btn-clone_visible');
  };

  Morphing.prototype.scaleBg = function() {
    var self = this;

    var $clone = self.$clone;
    var scale  = self.getScale();
    var $btn   = self.$btn;
    var pos    = $btn.offset();

    $clone.css({
      top       : pos.top  + $btn.outerHeight() * 0.5 - ( $btn.outerHeight() * scale * 0.5 ) - $(window).scrollTop(),
      left      : pos.left + $btn.outerWidth()  * 0.5 - ( $btn.outerWidth()  * scale * 0.5 ) - $(window).scrollLeft(),
      width     : $btn.outerWidth()  * scale,
      height    : $btn.outerHeight() * scale,
      transform : 'scale(' + ( 1 / scale ) + ')'
    });
  };

  Morphing.prototype.getScale = function() {
    var $btn    = this.$btn,
        radius  = $btn.outerWidth() * 0.5,
        left    = $btn.offset().left + radius - $(window).scrollLeft(),
        top     = $btn.offset().top  + radius - $(window).scrollTop(),
        windowW = $(window).width(),
        windowH = $(window).height();

    var maxDistHor  = ( left > windowW / 2 ) ? left : ( windowW - left ),
        maxDistVert = ( top > windowH / 2 )  ? top  : ( windowH - top );

    return Math.ceil(Math.sqrt( Math.pow( maxDistHor, 2 ) + Math.pow( maxDistVert, 2 ) ) / radius );
  };

  Morphing.prototype.complete = function() {
    var self = this;
    var $btn = self.$btn;

    $.fancybox.open({ src : $btn.data('src') || $btn.attr('href') }, self.opts);

    $(window).on('resize.fm', function() {
      //self.scaleBg();
    });
  };

  Morphing.prototype.close = function() {
    var self   = this;
    var $clone = self.$clone;

    self.scaleBg();

    $clone.one('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd', function(e) {
      $clone.hide();

      self.$btn.removeClass('morphing-btn_circle');
    });

    $clone.removeClass('morphing-btn-clone_visible');

    $(window).off('resize.fm');
  };

  // Init
  this.each(function() {
    var $this = $(this);

    if ( !$this.data("morphing") ) {
      $this.data( "morphing", new Morphing( $this, opts ) );
    }

  });

  return this;
};

$("[data-morphing]").fancyMorph({
  hash : 'morphing'
});
// ===========================X

// Fancybox v3 -- END
// =====================================X

// SETTINGS
// ===============================================
$(document).ready(function() {
    $('input[data-toggle]').bootstrapToggle();
    $('#auto-queue-photo').change(function() {
	$.get("ajax/settings-save-bool.php", { "option":"auto-queue-photo","v":$(this).prop('checked') } );
    });
    $('#auto-queue-audio').change(function() {
	$.get("ajax/settings-save-bool.php", { "option":"auto-queue-audio","v":$(this).prop('checked') } );
    });
    $('#play-local-video').change(function() {
	$.get("ajax/settings-save-bool.php", { "option":"play-local-video","v":$(this).prop('checked') } );
    });
    $('#start-local-video').change(function() {
	$.get("ajax/settings-save-bool.php", { "option":"start-local-video","v":$(this).prop('checked') } );
    });
});

// DOCUMENTS
// ===============================================

// Autoplay gif's on hover
function doc_gif(){
    $(".docs-gif").hover(function(){ $(this).attr("style","background-image:url('"+$(this).attr('data-src-local')+"')") }, function(){ $(this).attr("style","background-image:url('"+$(this).attr('data-pre-local')+"')") });
}

// Filter-box toggle
$(document).mouseup(function (e){
    var container = $(".docs-filter-box");
    if (!container.is(e.target) // if the target of the click isn't the container...
    && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
	container.hide();
	container.unbind( 'click' );
    }
});


function jscroller(){
    $('#docs-list').jscroll({
	debug:false,
	refresh:true,
	nextSelector: 'div.paginator-next > a:last',
	padding: 20,
	callback: function(){
	    $(".tip").tooltip();
	    doc_gif();
	    var pval = jQuery("div.paginator-next:last .paginator-val").html();
	    if($.isNumeric(pval)){ urlCommands.urlPush({page:pval}); }
	}
    });
}
/*
 * Ripple Effect
 */
! function( $ ) {
	"use strict";

	var $body = document.body || document.documentElement,
		$bodyStyle = $body.style,
		isTransitionsSupported = $bodyStyle.transition !== undefined || $bodyStyle.WebkitTransition !== undefined,
		duration = 400, diff, delay;
	var removeRipple = function( $ripple ) {
		$ripple.off();
		diff = Date.now() - Number($ripple.data('ripple-time'));
		delay = diff < duration ? duration - diff : 100;
		if ( isTransitionsSupported ) {
			setTimeout( () => {
				$ripple.addClass("ripple-out");
			}, delay );
		} else {
			$ripple.animate({
				"opacity": 0
			}, 100, () => {
				$ripple.trigger("transitionend");
			});
		}

		$ripple.on( "transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", () => {
			setTimeout( () => {
				$ripple.remove();
			}, 300 );
		} );
	};

	$.fn.usRipple = function(){
		return this.each(function(){
			var $element = $( this ),
				$container, containerOffset;

			if (!$element.find('.ripple-container').length) {
				$element.append('<span class="ripple-container"></span>');
			}

			$container = $element.find(".ripple-container");

			// Storing last touch event for touchEnd coordinates
			var lastTouch = null;
			if ($.isMobile) {
				// Note: "touchmove.noPreventDefault" displays a warning
				$element.on('touchstart.noPreventDefault touchmove', ( e ) => {
					e = e.originalEvent;
					if (e.touches.length === 1) {
						lastTouch = e.touches[0];
					}
				});
			}

			$element.on( $.isMobile ? 'touchstart.noPreventDefault' : 'mousedown', function(e){
				if (e.button === 2) return false;
				var offsetLeft, offsetTop, offsetRight,
					$ripple = $( '<span class="ripple"></span>' ),
					rippleSize = Math.max($element.outerWidth(), $element.outerHeight()) / Math.max(20, $ripple.outerWidth()) * 2.5;
				containerOffset = $container.offset();
				$container.append($ripple);

				// get pointer position
				if (!$.isMobile) {
					offsetLeft = e.pageX - containerOffset.left;
					offsetTop = e.pageY - containerOffset.top;
				} else if (lastTouch !== null) {
					offsetLeft = lastTouch.pageX - containerOffset.left;
					offsetTop = lastTouch.pageY - containerOffset.top;
					lastTouch = null;
				} else {
					return;
				}

				if ( $('body').hasClass('rtl') ) {
					offsetRight = $container.width() - offsetLeft;
					$ripple.css({right: offsetRight, top: offsetTop});
				} else {
					$ripple.css({left: offsetLeft, top: offsetTop});
				}

				(() => {
					return window.getComputedStyle($ripple[0]).opacity;
				})();


				$element.on( $.isMobile ? 'touchend.noPreventDefault' : 'mouseup mouseleave', ( e ) => {
					removeRipple( $ripple );
				});

				$ripple
					.css( { "transform": `scale(${rippleSize})` } )
					.addClass( 'ripple-on' )
					.data( 'ripple-time', Date.now() );
			});
		});
	};

	$( () => {
		$('.w-toplink, .w-btn[href], a.w-nav-anchor[href], .w-grid-item-anchor[rel], .w-gallery-item[href], .g-filters-item, .w-iconbox.style_circle a[href] .w-iconbox-icon, .w-socials-item-link[href], .w-sharing-item[href], .w-tabs-item, .w-message-close, .post_navigation.layout_sided > a, .w-ibanner > a, .vc_column-link')
			.usRipple();
	} );

}( jQuery );

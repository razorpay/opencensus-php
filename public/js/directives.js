'use strict';

/* Directives */
// All the directives rely on jQuery.

angular.module('app.directives', ['ui.load'])
  .directive('uiModule', ['MODULE_CONFIG','uiLoad', '$compile', function(MODULE_CONFIG, uiLoad, $compile) {
    return {
      restrict: 'A',
      compile: function (el, attrs) {
        var contents = el.contents().clone();
        return function(scope, el, attrs){
          el.contents().remove();
          uiLoad.load(MODULE_CONFIG[attrs.uiModule])
          .then(function(){
            $compile(contents)(scope, function(clonedElement, scope) {
              el.append(clonedElement);
            });
          });
        }
      }
    };
  }])
  .directive('uiShift', ['$timeout', function($timeout) {
    return {
      restrict: 'A',
      link: function(scope, el, attr) {
        // get the $prev or $parent of this el
        var _el = $(el),
            _window = $(window),
            prev = _el.prev(),
            parent,
            width = _window.width()
            ;

        !prev.length && (parent = _el.parent());
        
        function sm(){
          $timeout(function () {
            var method = attr.uiShift;
            var target = attr.target;
            _el.hasClass('in') || _el[method](target).addClass('in');
          });
        }
        
        function md(){
          parent && parent['prepend'](el);
          !parent && _el['insertAfter'](prev);
          _el.removeClass('in');
        }

        (width < 768 && sm()) || md();

        _window.resize(function() {
          if(width !== _window.width()){
            $timeout(function(){
              (_window.width() < 768 && sm()) || md();
              width = _window.width();
            });
          }
        });
      }
    };
  }])
  .directive('uiToggleClass', ['$timeout', '$document', function($timeout, $document) {
    return {
      restrict: 'AC',
      link: function(scope, el, attr) {
        el.on('click', function(e) {
          e.preventDefault();
          var classes = attr.uiToggleClass.split(','),
              targets = (attr.target && attr.target.split(',')) || Array(el),
              key = 0;
          angular.forEach(classes, function( _class ) {
            var target = targets[(targets.length && key)];            
            ( _class.indexOf( '*' ) !== -1 ) && magic(_class, target);
            $( target ).toggleClass(_class);
            key ++;
          });
          $(el).toggleClass('active');

          function magic(_class, target){
            var patt = new RegExp( '\\s' + 
                _class.
                  replace( /\*/g, '[A-Za-z0-9-_]+' ).
                  split( ' ' ).
                  join( '\\s|\\s' ) + 
                '\\s', 'g' );
            var cn = ' ' + $(target)[0].className + ' ';
            while ( patt.test( cn ) ) {
              cn = cn.replace( patt, ' ' );
            }
            $(target)[0].className = $.trim( cn );
          }
        });
      }
    };
  }])
  .directive('uiNav', ['$timeout', function($timeout) {
    return {
      restrict: 'AC',
      link: function(scope, el, attr) {
        var _window = $(window);
        var _mb = 768;
        // unfolded
        $(el).on('click', 'a', function(e) {
          var _this = $(this);
          _this.parent().siblings( ".active" ).toggleClass('active');
          _this.parent().toggleClass('active');
          _this.next().is('ul') && e.preventDefault();
          _this.next().is('ul') || ( ( _window.width() < _mb ) && $('.app-aside').toggleClass('show') );
        });

        // folded
        var wrap = $('.app-aside'), next;
        $(el).on('mouseenter', 'a', function(e){
          if ( !$('.app-aside-fixed.app-aside-folded').length || ( _window.width() < _mb )) return;
          var _this = $(this);

          next && next.trigger('mouseleave.nav');

          if( _this.next().is('ul') ){
             next = _this.next();
          }else{
            return;
          }
          
          next.appendTo(wrap).css('top', _this.offset().top - _this.height());
          next.on('mouseleave.nav', function(e){
            next.appendTo(_this.parent());
            next.off('mouseleave.nav');
            _this.parent().removeClass('active');
          });
          _this.parent().addClass('active');
          
        });

        wrap.on('mouseleave', function(e){
          next && next.trigger('mouseleave.nav');
        });
      }
    };
  }])
  .directive('uiScroll', ['$location', '$anchorScroll', function($location, $anchorScroll) {
    return {
      restrict: 'AC',
      link: function(scope, el, attr) {
        el.on('click', function(e) {
          $location.hash(attr.uiScroll);
          $anchorScroll();
        });
      }
    };
  }])
  .directive('uiFullscreen', ['uiLoad', function(uiLoad) {
    return {
      restrict: 'AC',
      template:'<i class="fa fa-expand fa-fw text"></i><i class="fa fa-compress fa-fw text-active"></i>',
      link: function(scope, el, attr) {
        el.addClass('hide');
        uiLoad.load('js/libs/screenfull.min.js').then(function(){
          if (screenfull.enabled) {
            el.removeClass('hide');
          }
          el.on('click', function(){
            var target;
            attr.target && ( target = $(attr.target)[0] );            
            el.toggleClass('active');
            screenfull.toggle(target);
          });
        });
      }
    };
  }])
  .directive('uiButterbar', ['$rootScope', '$location', '$anchorScroll', function($rootScope, $location, $anchorScroll) {
     return {
      restrict: 'AC',
      template:'<span class="bar"></span>',
      link: function(scope, el, attrs) {        
        el.addClass('butterbar hide');        
        scope.$on('$stateChangeStart', function(event) {
          $location.hash('app');
          $anchorScroll();
          el.removeClass('hide').addClass('active');
        });
        scope.$on('$stateChangeSuccess', function( event, toState, toParams, fromState ) {
          event.targetScope.$watch('$viewContentLoaded', function(){
            el.addClass('hide').removeClass('active');
          })          
        });
      }
     };
  }])
;
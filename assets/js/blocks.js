/*
@codekit-prepend "vendors/list.js"
@codekit-prepend "vendors/list.paging.js"
*/

(function($) {

    var Blocks = function(options) {
        this.options = $.extend({
            containerSelector: '#blocks-area-control'
        }, options || {});
    };

    Blocks.prototype = {
        init: function() {
            var self = this;

            self.mainContainer = $(this.options.containerSelector);
            self.availableContainer = self.mainContainer.find('ul.list-pages');
            self.saveContainer = self.mainContainer.find('ul.save-block');

            // Remove function
            var removeEventFunction = function() {
                var area = $(this).parents('ul');
                $(this).closest('.block').remove();
                self.saveBlockAreaOnSinglePage(area);
            };

            // Remove block from saved
            $('#normal-sortables .remove-block').click( removeEventFunction );

            // If have blocks-area add draggable and sortable
            if ( $(".blocks-area").closest("body").length > 0 ) {
                $('.list li').draggable( {
                    helper:'clone',
                    connectToSortable:'.blocks-area'
                });

                // Make the areas sortable
                $('.blocks-area').sortable( {
                    placeholder: "blocksplaceholder",

                    update: function(event, ui) {
                        self.saveBlockAreaOnSinglePage($(this));
                        $('.remove-block', this).click( removeEventFunction );
                    }
                });
             }

            // Fire the list-function
            new List(
                'blocks_save',
                
                // Options for list.js Search on block-title, 8 Items and paging plugin
                {
                    valueNames: [ 'block-title' ],
                    page: 9,
                    plugins: [
                        [ 'paging' ]
                    ]
                }
            );
            
            $('.sidebar-name-arrow').live('click', function() {
               $(this).parent().next().toggleClass('open');
            });

            // Remove paging if only one page, should be native in lib
            if( $(".paging-holder ul", self.mainContainer).children().length === 1 ) {
               $('.paging li').remove();
            }

            // Search pages and posts on title
            $('.block-pages-search', self.mainContainer).bind('keydown keypress keyup change', function() {
                var search = this.value;
                var $li = $('.list-pages > li').hide();
                $li.filter(function() {
                    return $(this).text().toLowerCase().indexOf(search) >= 0;
                }).show();
            });

            // Add on available list is clicked
            $('.add', self.availableContainer).live('click', function() {
                var post = $(this).parents('li.block');
                self.moveBlockToSave(post);
            });

            // Remove on saved list is clicked
            $('.delete', self.saveContainer).live('click', function() {
                var post = $(this).parents('li.block');
                self.ajaxRemovePostFromBlock(post);
            });

            // Save block areas to pages
            $('.areas li', self.mainContainer).live('click', function(e) {
                e.preventDefault();

                var area = $(this);
                var post = area.parents('li.block');

                self.ajaxSavePostOnBlockArea(post, area, area.find('span').hasClass('saved'));

                e.stopPropagation();
            });

            // remove all open classes
            $(document).click(function(){
                $('.open', self.mainContainer).each(function() {
                    var area = $(this);
                    self.closeAreaSelectDialog(area);
                });
            });

            // Open areas-selector upon click
            $('.add-areas', self.mainContainer).click( function(e) {
                e.preventDefault();

                var button = $(this);
                var areas = button.siblings('ul.areas');

                if( !areas.hasClass('open') ) {
                    // Close all other opened areas
                    button.parents('ul.save-block').find('ul.areas.open').each(function() {
                        var area = $(this);
                        self.closeAreaSelectDialog(area);
                    });

                    areas.addClass('open');
                }
                else {
                    areas.removeClass('open');
                }

                e.stopPropagation();
            });
        },
        moveBlockToAvailable: function(block) {
            block.prependTo(this.availableContainer);

            block.find('.saved').removeClass('saved');
        },
        moveBlockToSave: function(block) {
            block.prependTo(this.saveContainer);

            var itemId = block.data('id');

            setTimeout(function(){ $('li[data-id=' + itemId + '] .add-areas').trigger('click'); }, 200);
        },
        closeAreaSelectDialog:function(area) {
            area.removeClass('open');

            if (area.find('.saved').length === 0) {
                var block = area.parents('li.block');
                this.moveBlockToAvailable(block);
            }
        },
        ajaxSavePostOnBlockArea:function(post, area, remove) {
            var span = area.find('span');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                async: true,
                cache: false,
                // context: self,
                dataType: 'json',
                data: {
                    action: 'save_post_on_block',
                    post_id: post.data('id'),
                    block_id: $('#post_ID').val(),
                    area: area.data('area'),
                    remove: remove,
                    nounce: BlocksConstants.AjaxNounce
                },
                beforeSend: function() {
                    span.addClass('loading');
                },
                complete: function() {
                    span.removeClass('loading').toggleClass('saved');
                }
            });
        },
        // Remove post from all block areas 
        ajaxRemovePostFromBlock:function(post) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                async: true,
                cache: false,
                context: this,
                dataType: 'json',
                data: {
                    action: 'remove_post_from_block',
                    post_id: post.data('id'),
                    block_id: $('#post_ID').val(),
                    nounce: BlocksConstants.AjaxNounce
                },
                beforeSend: function() {
                    post.addClass('loading');
                },
                complete: function() {
                    post.removeClass('loading');

                    this.moveBlockToAvailable(post);
                }
            });
        },
        saveBlockAreaOnSinglePage: function(area) {
            var data = [];

            area.find('li').each(function() {
                data.push($(this).attr('data-id'));
            });

            var inputData = $('input[type=hidden][name^="blocks[' + area.attr('data-area') + '][data]"]');

            inputData.val(data.join());
        }
    };

    new Blocks().init();
}(jQuery));
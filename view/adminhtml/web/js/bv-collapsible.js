define(['jquery', 'collapsible'], function($) {
    'use strict';

    const procesJson = function() {
        var that = this;
        var loading = $('<div class="bv-loader"><p>Working...</p></div>');
        if (this.content.attr('aria-busy')) {
            setTimeout(function () {
                that.content.html(loading);
                that.trigger.prop('disabled', true);
            }, 1);
        }
        $.when( this.xhr ).then(function( data, status, jqHR) {
            if(typeof status === "undefined") {
                return false;
            }

            var content = $('<div><ul class="list"></ul></div>');

            if(jqHR.status == 200) {
                data = JSON.parse(data);

                var overAllStatus = data.success ? 'success' : 'failed';

                that.element.addClass(overAllStatus);

                data.messages.map(function(message) {

                    var li = $('<li class="list-item"></li>');

                    switch(message.success) {
                        case true:
                                li.addClass('list-item-success');
                            break;
                        case false:
                                li.addClass('list-item-failed');
                            break;
                        default:
                            li.addClass('list-item-warning');
                    }

                    li.append('<p>'+ message.message +'</p>')

                    content.find('ul').append(li);
                    return message;
                });

                setTimeout(function () {
                    that.content.html(content.html());
                }, 1);
            }else {
                var li = $('<li class="list-item"></li>')
                    .addClass('list-item-failed')

                li.html('<p class="alert alert-error">An error has been occured.</p>');
                content.find('ul').html(li);

                setTimeout(function () {
                    that.content.html(content.html());
                }, 1);
            }
        }).always(function() {
            that.trigger.prop('disabled', false);
        })
    }

    $.widget('bv.collapsible', $.mage.collapsible, {

        _loadContent: function() {
            this._super();
            procesJson.bind(this)();
        }
    });

    return $.bv.collapsible;
});

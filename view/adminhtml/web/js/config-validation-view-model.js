define(['jquery', 'uiComponent', 'ko'], function ($, Component, ko) {
        'use strict';

        return Component.extend({
            bv_validations: ko.observableArray([
                {
                    "name": "cron_jobs",
                    "title":"Cron Jobs",
                    "description":"Validating BV cron jobs status",
                    "url":"validate/cron"
                },
                {
                    "name": "product_feed",
                    "title":"Product Feed",
                    "description":"Validating BV cron jobs status",
                    "url":"validate/cron"
                }
            ]),
            initialize: function () {
                this._super();
            }
        });
    }
);


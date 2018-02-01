'use strict';


define([
    'jquery',
    'underscore',
    'oro/translator',
    'pim/fetcher-registry',
    'pim/job/common/edit/field/select'
], function (
    $,
    _,
    __,
    FetcherRegistry,
    SelectField
) {
    return SelectField.extend({
        /**
         * {@inherit}
         */
        configure: function () {
            return $.when(
                FetcherRegistry.getFetcher('dnd/currencies-list').fetchAll(),
                SelectField.prototype.configure.apply(this, arguments)
            ).then(function (availableCurrenciesList) {
                if (_.isEmpty(availableCurrenciesList)) {
                    this.config.readOnly = true;
                    this.config.options = {'NO OPTION': __('dnd_criteo.criteo_writer.currency.no_currency')};
                } else {
                    this.config.options = availableCurrenciesList;
                }
            }.bind(this));
        }
    });
});
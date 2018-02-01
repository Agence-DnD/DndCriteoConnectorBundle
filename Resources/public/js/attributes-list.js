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
                FetcherRegistry.getFetcher('dnd/attributes-list').fetchAll(),
                SelectField.prototype.configure.apply(this, arguments)
            ).then(function (attributesList) {
                if (_.isEmpty(attributesList)) {
                    this.config.readOnly = true;
                    this.config.options = {'NO OPTION': __('dnd_criteo.criteo_writer.attributes.no_attribute')};
                } else {
                    this.config.options = attributesList;
                }
            }.bind(this));
        }
    });
});
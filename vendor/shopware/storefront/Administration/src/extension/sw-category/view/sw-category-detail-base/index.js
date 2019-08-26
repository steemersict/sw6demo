import CriteriaFactory from 'src/core/factory/criteria.factory';
import template from './sw-category-detail-base.html.twig';

const { Component } = Shopware;

Component.override('sw-category-detail-base', {
    template,

    data() {
        return {
            seoUrlStore: null,
            seoUrls: []
        };
    },

    created() {
        if (!this.next741) {
            this.initSeoUrls();

            this.$root.$on('on-change-application-language', this.initSeoUrls);
        }
    },

    methods: {
        initSeoUrls() {
            this.seoUrlStore = this.category.getAssociation('seoUrls');
            const params = {
                page: 1,
                limit: 50,
                criteria: CriteriaFactory.equals('isCanonical', true)
            };
            this.seoUrlStore.getList(params).then((response) => {
                this.seoUrls = response.items;
            });
        }
    }
});

import Plugin from 'src/script/plugin-system/plugin.class';
import DomAccess from 'src/script/helper/dom-access.helper';
import Debouncer from 'src/script/helper/debouncer.helper';
import HttpClient from 'src/script/service/http-client.service';
import ButtonLoadingIndicator from 'src/script/utility/loading-indicator/button-loading-indicator.util';
import DeviceDetection from 'src/script/helper/device-detection.helper';
import ArrowNavigationHelper from 'src/script/helper/arrow-navigation.helper';
import Iterator from 'src/script/helper/iterator.helper';

export default class SearchWidgetPlugin extends Plugin {

    static options = {
        searchWidgetSelector: '.js-search-form',
        searchWidgetResultSelector: '.js-search-result',
        searchWidgetResultItemSelector: '.js-result',
        searchWidgetInputFieldSelector: 'input[type=search]',
        searchWidgetButtonFieldSelector: 'button[type=submit]',
        searchWidgetUrlDataAttribute: 'data-url',
        searchWidgetCollapseButtonSelector: '.js-search-toggle-btn',
        searchWidgetCollapseClass: 'collapsed',

        searchWidgetDelay: 250,
        searchWidgetMinChars: 3,
    };

    init() {
        try {
            this._inputField = DomAccess.querySelector(this.el, this.options.searchWidgetInputFieldSelector);
            this._submitButton = DomAccess.querySelector(this.el, this.options.searchWidgetButtonFieldSelector);
            this._url = DomAccess.getAttribute(this.el, this.options.searchWidgetUrlDataAttribute);
        } catch (e) {
            return;
        }

        this._client = new HttpClient(window.accessKey, window.contextToken);

        // initialize the arrow navigation
        this._navigationHelper = new ArrowNavigationHelper(
            this._inputField,
            this.options.searchWidgetResultSelector,
            this.options.searchWidgetResultItemSelector,
            true,
        );

        this._registerEvents();
    }

    /**
     * Register events
     * @private
     */
    _registerEvents() {
        // add listener to the form's input event
        this._inputField.addEventListener(
            'input',
            Debouncer.debounce(this._handleInputEvent.bind(this), this.options.searchWidgetDelay),
            {
                capture: true,
                passive: true,
            },
        );

        // add click event listener to body
        const event = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click';
        document.body.addEventListener(event, this._onBodyClick.bind(this));

        // add click event for mobile search
        this._registerInputFocus();
    }

    /**
     * Fire the XHR request if user inputs a search term
     * @private
     */
    _handleInputEvent() {
        const value = this._inputField.value;

        // stop search if minimum input value length has not been reached
        if (value.length < this.options.searchWidgetMinChars) {
            // further clear possibly existing search results
            this._clearSearchResults();
            return;
        }

        this._search(value);

        this.$emitter.publish('handleInputEvent');
    }

    /**
     * Process the AJAX search and show results
     * @param {string} value
     * @private
     */
    _search(value) {
        const url = this._url + encodeURI(value);

        // init loading indicator
        const indicator = new ButtonLoadingIndicator(this._submitButton);
        indicator.create();

        this.$emitter.publish('beforeSearch');

        this._client.abort();
        this._client.get(url, (response) => {
            // remove existing search results popover first
            this._clearSearchResults();

            // remove indicator
            indicator.remove();

            // attach search results to the DOM
            this.el.insertAdjacentHTML('beforeend', response);

            this.$emitter.publish('afterSearch');
        });
    }

    /**
     * Remove existing search results popover from DOM
     * @private
     */
    _clearSearchResults() {
        // reseet arrow navigation helper to enable form submit on enter
        this._navigationHelper.resetIterator();

        // remove all result popovers
        const results = document.querySelectorAll(this.options.searchWidgetResultSelector);
        Iterator.iterate(results, result => result.remove());

        this.$emitter.publish('clearSearchResults');
    }

    /**
     * Close/remove the search results from DOM if user
     * clicks outside the form or the results popover
     * @param {Event} e
     * @private
     */
    _onBodyClick(e) {
        // early return if click target is the search form or any of it's children
        if (e.target.closest(this.options.searchWidgetSelector)) {
            return;
        }

        // early return if click target is the search result or any of it's children
        if (e.target.closest(this.options.searchWidgetResultSelector)) {
            return;
        }
        // remove existing search results popover
        this._clearSearchResults();

        this.$emitter.publish('onBodyClick');
    }

    /**
     * When the search is shown, trigger the focus on the input field
     * @private
     */
    _registerInputFocus() {
        try {
            this._toggleButton = DomAccess.querySelector(document, this.options.searchWidgetCollapseButtonSelector);
        } catch (e) {
            // something went wrong
            throw new Error(`the search-toggle-btn doesn´t own the "${this.options.searchWidgetCollapseButtonSelector}" class. So the search-input-field wont´t have an autofocus, on Mobile.`);
        }

        const event = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click';
        this._toggleButton.addEventListener(event, () => {
            setTimeout(() => this._focusInput(), 0);
        });
    }

    /**
     * Sets the focus on the input field
     * @private
     */
    _focusInput() {
        if (!this._toggleButton.classList.contains(this.options.searchWidgetCollapseClass)) {
            this._toggleButton.blur(); // otherwise iOS won´t focus the field.
            this._inputField.setAttribute('tabindex', '-1');
            this._inputField.focus();
        }

        this.$emitter.publish('focusInput');
    }
}

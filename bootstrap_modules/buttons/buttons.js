/**
 * --------------------------------------------------------------------------
 * Bootstrap (v5.1.3): button.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
 * --------------------------------------------------------------------------
 */

/*!
{{ IMPORT: util-index,dom-event-handler,base-component}}
*/

if(typeof bootstrap === 'undefined'){
  var bootstrap = {}
}

/**
   * ------------------------------------------------------------------------
   * Constants
   * ------------------------------------------------------------------------
   */

 const NAME$c = 'button';
 const DATA_KEY$b = 'bs.button';
 const EVENT_KEY$b = `.${DATA_KEY$b}`;
 const DATA_API_KEY$7 = '.data-api';
 const CLASS_NAME_ACTIVE$3 = 'active';
 const SELECTOR_DATA_TOGGLE$5 = '[data-bs-toggle="button"]';
 const EVENT_CLICK_DATA_API$6 = `click${EVENT_KEY$b}${DATA_API_KEY$7}`;
 /**
  * ------------------------------------------------------------------------
  * Class Definition
  * ------------------------------------------------------------------------
  */

 class Button extends BaseComponent {
   // Getters
   static get NAME() {
     return NAME$c;
   } // Public


   toggle() {
     // Toggle class and sync the `aria-pressed` attribute with the return value of the `.toggle()` method
     this._element.setAttribute('aria-pressed', this._element.classList.toggle(CLASS_NAME_ACTIVE$3));
   } // Static


   static jQueryInterface(config) {
     return this.each(function () {
       const data = Button.getOrCreateInstance(this);

       if (config === 'toggle') {
         data[config]();
       }
     });
   }

 }
 /**
  * ------------------------------------------------------------------------
  * Data Api implementation
  * ------------------------------------------------------------------------
  */


 EventHandler.on(document, EVENT_CLICK_DATA_API$6, SELECTOR_DATA_TOGGLE$5, event => {
   event.preventDefault();
   const button = event.target.closest(SELECTOR_DATA_TOGGLE$5);
   const data = Button.getOrCreateInstance(button);
   data.toggle();
 });
 /**
  * ------------------------------------------------------------------------
  * jQuery
  * ------------------------------------------------------------------------
  * add .Button to jQuery only if jQuery is present
  */

 defineJQueryPlugin(Button);

 bootstrap.Button = Button;
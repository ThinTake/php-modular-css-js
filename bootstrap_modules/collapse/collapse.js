/**
 * --------------------------------------------------------------------------
 * Bootstrap (v5.1.3): collapse.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
 * --------------------------------------------------------------------------
 */

/*!
{{ IMPORT: util-index,dom-data,dom-event-handler,dom-manipulator,dom-selector-engine,base-component}}
*/

if(typeof bootstrap === 'undefined'){
  var bootstrap = {}
}

/**
   * ------------------------------------------------------------------------
   * Constants
   * ------------------------------------------------------------------------
   */

 const NAME$a = 'collapse';
 const DATA_KEY$9 = 'bs.collapse';
 const EVENT_KEY$9 = `.${DATA_KEY$9}`;
 const DATA_API_KEY$5 = '.data-api';
 const Default$9 = {
   toggle: true,
   parent: null
 };
 const DefaultType$9 = {
   toggle: 'boolean',
   parent: '(null|element)'
 };
 const EVENT_SHOW$5 = `show${EVENT_KEY$9}`;
 const EVENT_SHOWN$5 = `shown${EVENT_KEY$9}`;
 const EVENT_HIDE$5 = `hide${EVENT_KEY$9}`;
 const EVENT_HIDDEN$5 = `hidden${EVENT_KEY$9}`;
 const EVENT_CLICK_DATA_API$4 = `click${EVENT_KEY$9}${DATA_API_KEY$5}`;
 const CLASS_NAME_SHOW$7 = 'show';
 const CLASS_NAME_COLLAPSE = 'collapse';
 const CLASS_NAME_COLLAPSING = 'collapsing';
 const CLASS_NAME_COLLAPSED = 'collapsed';
 const CLASS_NAME_DEEPER_CHILDREN = `:scope .${CLASS_NAME_COLLAPSE} .${CLASS_NAME_COLLAPSE}`;
 const CLASS_NAME_HORIZONTAL = 'collapse-horizontal';
 const WIDTH = 'width';
 const HEIGHT = 'height';
 const SELECTOR_ACTIVES = '.collapse.show, .collapse.collapsing';
 const SELECTOR_DATA_TOGGLE$4 = '[data-bs-toggle="collapse"]';
 /**
  * ------------------------------------------------------------------------
  * Class Definition
  * ------------------------------------------------------------------------
  */

 class Collapse extends BaseComponent {
   constructor(element, config) {
     super(element);
     this._isTransitioning = false;
     this._config = this._getConfig(config);
     this._triggerArray = [];
     const toggleList = SelectorEngine.find(SELECTOR_DATA_TOGGLE$4);

     for (let i = 0, len = toggleList.length; i < len; i++) {
       const elem = toggleList[i];
       const selector = getSelectorFromElement(elem);
       const filterElement = SelectorEngine.find(selector).filter(foundElem => foundElem === this._element);

       if (selector !== null && filterElement.length) {
         this._selector = selector;

         this._triggerArray.push(elem);
       }
     }

     this._initializeChildren();

     if (!this._config.parent) {
       this._addAriaAndCollapsedClass(this._triggerArray, this._isShown());
     }

     if (this._config.toggle) {
       this.toggle();
     }
   } // Getters


   static get Default() {
     return Default$9;
   }

   static get NAME() {
     return NAME$a;
   } // Public


   toggle() {
     if (this._isShown()) {
       this.hide();
     } else {
       this.show();
     }
   }

   show() {
     if (this._isTransitioning || this._isShown()) {
       return;
     }

     let actives = [];
     let activesData;

     if (this._config.parent) {
       const children = SelectorEngine.find(CLASS_NAME_DEEPER_CHILDREN, this._config.parent);
       actives = SelectorEngine.find(SELECTOR_ACTIVES, this._config.parent).filter(elem => !children.includes(elem)); // remove children if greater depth
     }

     const container = SelectorEngine.findOne(this._selector);

     if (actives.length) {
       const tempActiveData = actives.find(elem => container !== elem);
       activesData = tempActiveData ? Collapse.getInstance(tempActiveData) : null;

       if (activesData && activesData._isTransitioning) {
         return;
       }
     }

     const startEvent = EventHandler.trigger(this._element, EVENT_SHOW$5);

     if (startEvent.defaultPrevented) {
       return;
     }

     actives.forEach(elemActive => {
       if (container !== elemActive) {
         Collapse.getOrCreateInstance(elemActive, {
           toggle: false
         }).hide();
       }

       if (!activesData) {
         Data.set(elemActive, DATA_KEY$9, null);
       }
     });

     const dimension = this._getDimension();

     this._element.classList.remove(CLASS_NAME_COLLAPSE);

     this._element.classList.add(CLASS_NAME_COLLAPSING);

     this._element.style[dimension] = 0;

     this._addAriaAndCollapsedClass(this._triggerArray, true);

     this._isTransitioning = true;

     const complete = () => {
       this._isTransitioning = false;

       this._element.classList.remove(CLASS_NAME_COLLAPSING);

       this._element.classList.add(CLASS_NAME_COLLAPSE, CLASS_NAME_SHOW$7);

       this._element.style[dimension] = '';
       EventHandler.trigger(this._element, EVENT_SHOWN$5);
     };

     const capitalizedDimension = dimension[0].toUpperCase() + dimension.slice(1);
     const scrollSize = `scroll${capitalizedDimension}`;

     this._queueCallback(complete, this._element, true);

     this._element.style[dimension] = `${this._element[scrollSize]}px`;
   }

   hide() {
     if (this._isTransitioning || !this._isShown()) {
       return;
     }

     const startEvent = EventHandler.trigger(this._element, EVENT_HIDE$5);

     if (startEvent.defaultPrevented) {
       return;
     }

     const dimension = this._getDimension();

     this._element.style[dimension] = `${this._element.getBoundingClientRect()[dimension]}px`;
     reflow(this._element);

     this._element.classList.add(CLASS_NAME_COLLAPSING);

     this._element.classList.remove(CLASS_NAME_COLLAPSE, CLASS_NAME_SHOW$7);

     const triggerArrayLength = this._triggerArray.length;

     for (let i = 0; i < triggerArrayLength; i++) {
       const trigger = this._triggerArray[i];
       const elem = getElementFromSelector(trigger);

       if (elem && !this._isShown(elem)) {
         this._addAriaAndCollapsedClass([trigger], false);
       }
     }

     this._isTransitioning = true;

     const complete = () => {
       this._isTransitioning = false;

       this._element.classList.remove(CLASS_NAME_COLLAPSING);

       this._element.classList.add(CLASS_NAME_COLLAPSE);

       EventHandler.trigger(this._element, EVENT_HIDDEN$5);
     };

     this._element.style[dimension] = '';

     this._queueCallback(complete, this._element, true);
   }

   _isShown(element = this._element) {
     return element.classList.contains(CLASS_NAME_SHOW$7);
   } // Private


   _getConfig(config) {
     config = { ...Default$9,
       ...Manipulator.getDataAttributes(this._element),
       ...config
     };
     config.toggle = Boolean(config.toggle); // Coerce string values

     config.parent = getElement(config.parent);
     typeCheckConfig(NAME$a, config, DefaultType$9);
     return config;
   }

   _getDimension() {
     return this._element.classList.contains(CLASS_NAME_HORIZONTAL) ? WIDTH : HEIGHT;
   }

   _initializeChildren() {
     if (!this._config.parent) {
       return;
     }

     const children = SelectorEngine.find(CLASS_NAME_DEEPER_CHILDREN, this._config.parent);
     SelectorEngine.find(SELECTOR_DATA_TOGGLE$4, this._config.parent).filter(elem => !children.includes(elem)).forEach(element => {
       const selected = getElementFromSelector(element);

       if (selected) {
         this._addAriaAndCollapsedClass([element], this._isShown(selected));
       }
     });
   }

   _addAriaAndCollapsedClass(triggerArray, isOpen) {
     if (!triggerArray.length) {
       return;
     }

     triggerArray.forEach(elem => {
       if (isOpen) {
         elem.classList.remove(CLASS_NAME_COLLAPSED);
       } else {
         elem.classList.add(CLASS_NAME_COLLAPSED);
       }

       elem.setAttribute('aria-expanded', isOpen);
     });
   } // Static


   static jQueryInterface(config) {
     return this.each(function () {
       const _config = {};

       if (typeof config === 'string' && /show|hide/.test(config)) {
         _config.toggle = false;
       }

       const data = Collapse.getOrCreateInstance(this, _config);

       if (typeof config === 'string') {
         if (typeof data[config] === 'undefined') {
           throw new TypeError(`No method named "${config}"`);
         }

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


 EventHandler.on(document, EVENT_CLICK_DATA_API$4, SELECTOR_DATA_TOGGLE$4, function (event) {
   // preventDefault only for <a> elements (which change the URL) not inside the collapsible element
   if (event.target.tagName === 'A' || event.delegateTarget && event.delegateTarget.tagName === 'A') {
     event.preventDefault();
   }

   const selector = getSelectorFromElement(this);
   const selectorElements = SelectorEngine.find(selector);
   selectorElements.forEach(element => {
     Collapse.getOrCreateInstance(element, {
       toggle: false
     }).toggle();
   });
 });
 /**
  * ------------------------------------------------------------------------
  * jQuery
  * ------------------------------------------------------------------------
  * add .Collapse to jQuery only if jQuery is present
  */

 defineJQueryPlugin(Collapse);

 bootstrap.Collapse = Collapse;
/**
 * Sets the value of a native DOM element, bypassing the default behavior.
 * This is a workaround for dispatching manual events on native elements.
 * 
 * @param {HTMLElement} element - The native DOM element to set the value for.
 * @param {string} value - The value to set on the element.
 * 
 * @example
 * // Assuming there's an input element with a ref
 * const inputElement = document.querySelector('input');
 * setNativeValue(inputElement, 'Hello, World!');
 */
export function setNativeValue(element: HTMLElement, value: string): void {
  const valueSetter = Object.getOwnPropertyDescriptor(element, 'value')?.set;
  const prototype = Object.getPrototypeOf(element);
  const prototypeValueSetter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;

  if (valueSetter) {
    if (prototypeValueSetter && valueSetter !== prototypeValueSetter) {
      prototypeValueSetter.call(element, value);
    } else {
      valueSetter.call(element, value);
    }
  }
}

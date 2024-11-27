export const isPojo = (val: any): val is Record<string, any> =>
  val === null || typeof val !== 'object' ? false : Object.getPrototypeOf(val) === Object.prototype;

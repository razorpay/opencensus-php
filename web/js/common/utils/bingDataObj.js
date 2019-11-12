export default class BingDataObj {
  /**
   * event category
   * {String}
   */
  category;
  /**
   * event action
   * {String}
   */
  action;
  /**
   * event label
   * {String}
   */
  label;
  /**
   * event value
   * {Number}
   */
  value;

  constructor(category, action, label, value) {
    this.category = category;
    this.action = action;
    this.label = label;
    this.value = value;
  }
}

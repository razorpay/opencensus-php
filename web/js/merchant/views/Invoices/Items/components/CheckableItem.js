import { Component } from 'react';
import PropTypes from 'prop-types';

export default class CheckableItem extends Component {
  static propTypes = {
    /**
     * Text to display.
     */
    text: PropTypes.string.isRequired,

    /**
     * Value to be passed in onCheck and onUncheck callbacks.
     */
    value: PropTypes.any,

    /**
     * Whether or not the component is checked.
     */
    checked: PropTypes.bool,

    /**
     * Callback for when the item is checked.
     */
    onCheck: PropTypes.func,

    /**
     * Callback for when the item is unchecked.
     */
    onUncheck: PropTypes.func,
  };

  static defaultProps = {
    value: null,
    checked: false,
    onCheck: () => {},
    onUncheck: () => {},
  };

  /**
   * Method that will be called when this item is clicked.
   * @param e Event
   */
  onClick = e => {
    const { checked, value, onCheck, onUncheck } = this.props;

    if (checked) {
      onUncheck(value);
    } else {
      onCheck(value);
    }
  };

  render() {
    let { text, checked } = this.props;

    return (
      <div
        className={`CheckableItem__Container
                ${checked ? 'CheckableItem__Checked' : ''}`}
        onClick={this.onClick}
      >
        <div className="CheckableItem__Text">{text}</div>
        <div className="CheckableItem__Tick">
          <i className="i-check" />
        </div>
      </div>
    );
  }
}

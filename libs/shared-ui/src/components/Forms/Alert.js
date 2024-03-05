import React, { Component, isValidElement } from 'react';
import PropTypes from 'prop-types';
import { makeArray, classList } from '@dashboard/shared-utils/rzp-utils';

class Alert extends Component {
  constructor(...args) {
    super(...args);
    this.state = {
      close: false,
    };
    this.close = this.close.bind(this);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.message && nextProps.message !== this.props.message) {
      this.setState({
        close: false,
      });
      window.scrollTo(0, 0);
    }
  }

  close() {
    this.setState({
      close: true,
    });

    this.props.onCloseClick && this.props.onCloseClick();
  }

  render() {
    const props = this.props;
    let msgs = makeArray(props.message);
    msgs = msgs.filter((msg) => typeof msg !== 'string' || msg.indexOf('Status Code') === -1);

    if (!(!this.state.close && msgs.length)) {
      return null;
    }

    return (
      <div
        className={classList(
          'alert',
          'alert-dismissable',
          `alert-${props.type === 'error' ? 'danger' : props.type}`,
          props.className,
        )}
        style={{ borderRadius: 0 }}
      >
        {props.showDismiss ? (
          <button type="button" className="close" onClick={this.close}>
            <span>×</span>
          </button>
        ) : null}

        <ul
          className={`${msgs.length === 1 ? 'list-unstyled' : ''}`}
          style={{ paddingLeft: msgs.length === 1 ? 5 : 15 }}
        >
          {msgs.map((msg, index) => {
            return (
              <li key={index}>
                {isValidElement(msg)
                  ? msg
                  : msg.stack
                  ? msg.stack
                  : typeof msg === 'object'
                  ? JSON.stringify(msg)
                  : msg}
              </li>
            );
          })}
        </ul>
      </div>
    );
  }
}

Alert.displayName = 'FormAlert';

Alert.defaultProps = {
  showDismiss: true,
  type: 'success',
};

Alert.propTypes = {
  type: PropTypes.string,
  showDismiss: PropTypes.bool,
};

export default Alert;

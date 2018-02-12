import { Component, isValidElement } from 'react';
import PropTypes from 'prop-types';
import { makeArray } from 'rzp/utils/rzp-utils';

class Alert extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      close: false,
    };
    this.close = ::this.close;
  }

  componentWillReceiveProps(nextProps) {
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
  }

  render() {
    let props = this.props;
    let msgs = makeArray(props.message);
    msgs = msgs.filter(
      msg => typeof msg !== 'string' || msg.indexOf('Status Code') === -1
    );

    if (!(!this.state.close && msgs.length)) {
      return null;
    }

    return (
      <div
        class={`alert alert-dismissable alert-${
          props.type === 'error' ? 'danger' : props.type
        }`}
        style={{ borderRadius: 0 }}
      >
        {props.showDismiss && (
          <button type="button" class="close" onClick={this.close}>
            <span>×</span>
          </button>
        )}

        <ul
          class={`${msgs.length === 1 ? 'list-unstyled' : ''}`}
          style={{ paddingLeft: msgs.length === 1 ? 5 : 15 }}
        >
          {msgs.map((msg, index) => {
            return (
              <li key={index}>
                {isValidElement(msg)
                  ? msg
                  : msg.stack
                    ? msg.stack
                    : typeof msg === 'object' ? JSON.stringify(msg) : msg}
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

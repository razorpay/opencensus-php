import { Component } from 'react';

const NOTIFICATION_TYPES = {
  success: 'Notification--success',
  error: 'Notification--error',
  info: 'Notification--info',
  neutral: 'Notification--neutral',
};

class Notification extends Component {
  constructor(...args) {
    super(...args);
    this.close = ::this.close;
  }

  componentDidMount() {
    setTimeout(() => {
      if (this.notificationEle) {
        this.notificationEle.classList.add('Notification__show');
      }
    }, 0);

    this.timerId = setTimeout(() => {
      this.close();
    }, this.props.closeTimeout);
  }

  componentWillUpdate(nextProps) {
    if (nextProps.hidePrevious) {
      clearTimeout(this.timerId);
      this.close();
    }
  }

  componentWillUnmount() {
    this.close();
  }

  close() {
    if (this.isClosed) {
      return;
    }
    if (this.notificationEle) {
      this.notificationEle.classList.remove('Notification__show');
    }
    this.isClosed = true;
    clearTimeout(this.timerId);

    setTimeout(() => {
      this.props.onClose();
      if (this.props.onTimeOutClose) {
        this.props.onTimeOutClose();
      }
    }, this.props.transitionTimeout);
  }

  onCloseClick = () => {
    this.close();
    if (this.props.onCloseClick) {
      this.props.onCloseClick();
    }
  };

  render() {
    const { type, message, showClose, hidePrevious } = this.props;

    if (hidePrevious) {
      return null;
    }

    return (
      <div
        ref={(notificationEle) => {
          this.notificationEle = notificationEle;
        }}
        class={`Notification ${NOTIFICATION_TYPES[type]}`}
      >
        {typeof message === 'function' ? (
          message()
        ) : Array.isArray(message) ? (
          <ul class="list-unstyled">
            {message.map((msg, idx) => (
              <li key={idx}>{msg}</li>
            ))}
          </ul>
        ) : (
          message
        )}
        {showClose && <i class="i i-close" onClick={this.onCloseClick} />}
      </div>
    );
  }
}

Notification.defaultProps = {
  type: 'success',
  showClose: true,
  closeTimeout: 5000,
  transitionTimeout: 300,
  onClose: () => {},
};

export default Notification;

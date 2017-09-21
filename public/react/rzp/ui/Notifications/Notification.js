import { Component } from 'react';

const NOTIFICATION_TYPES = {
  success: 'Notification--success',
  error: 'Notification--error',
  info: 'Notification--info',
  neutral: 'Notification--neutral',
};

class Notification extends Component {
  constructor() {
    super(...arguments);
    this.close = ::this.close;
  }

  componentDidMount() {
    setTimeout(() => {
      $(this.notificationEle).addClass('Notification__show');
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

    $(this.notificationEle).removeClass('Notification__show');
    this.isClosed = true;
    clearTimeout(this.timerId);

    setTimeout(() => {
      this.props.onClose();
    }, this.props.transitionTimeout);
  }

  render() {
    let { type, message, showClose, hidePrevious } = this.props;

    if (hidePrevious) {
      return null;
    }

    return (
      <div
        ref={notificationEle => {
          this.notificationEle = notificationEle;
        }}
        class={`Notification ${NOTIFICATION_TYPES[type]}`}
      >
        {typeof message === 'function'
          ? message()
          : Array.isArray(message)
            ? <ul class="list-unstyled">
                {message.map((msg, idx) =>
                  <li key={idx}>
                    {msg}
                  </li>
                )}
              </ul>
            : message}
        {showClose && <i class="icon icon-close" onClick={this.close} />}
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

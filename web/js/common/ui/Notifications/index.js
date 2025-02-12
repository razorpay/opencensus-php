import { Component } from 'react';
import { withZustand } from 'shell/commonStore';

import { classList } from 'common/utils/rzp-utils';

import Notification from './Notification';
class Notifications extends Component {
  closeNotification = (notification) => {
    const { hideNotification } = this.props.store;
    hideNotification?.(notification);
  };

  getExtraClass = (notifications) => {
    const extraClasses = notifications
      .filter((notification) => notification.hasOwnProperty('className'))
      .map((item) => item.className);
    const notifictaionClassName = classList(extraClasses);
    return notifictaionClassName.length !== 0 ? ` ${notifictaionClassName}` : '';
  };

  render() {
    const { notifications: { notifications = [], hidePrevious } = {} } = this.props.store;

    if (!notifications.length) {
      return null;
    }

    return (
      <div className={`Notifications${this.getExtraClass(notifications)}`}>
        {notifications.map((notification, idx) => (
          <Notification
            key={notification.id}
            type={notification.type}
            message={notification.message}
            showClose={notification.showClose}
            onClose={() => this.closeNotification(notification)}
            closeTimeout={notification.closeTimeout}
            hidePrevious={hidePrevious && idx > 0}
            onCloseClick={notification.onCloseClick}
            onTimeOutClose={notification.onTimeOutClose}
          />
        ))}
      </div>
    );
  }
}

export default withZustand(Notifications, ['notifications']);

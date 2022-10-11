import { Component } from 'react';
import { connect } from 'react-redux';
import Notification from './Notification';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { classList } from 'common/utils/rzp-utils';

@connect((state) => state.notifications, NotificationsActions)
export default class Notifications extends Component {
  closeNotification = (notification) => {
    this.props.hideNotification(notification);
  };

  getExtraClass = (notifications) => {
    const extraClasses = notifications
      .filter((notification) => notification.hasOwnProperty('className'))
      .map((item) => item.className);
    const notifictaionClassName = classList(extraClasses);
    return notifictaionClassName.length !== 0 ? ` ${notifictaionClassName}` : '';
  };

  render() {
    const { notifications, hidePrevious } = this.props;

    if (!notifications.length) {
      return null;
    }

    return (
      <div class={`Notifications${this.getExtraClass(notifications)}`}>
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

import { Component } from 'react'
import { connect } from 'react-redux'
import Notification from './Notification'
import * as NotificationsActions from 'rzp/modules/notifications'
import './Notifications.styl'

@connect(
  (state) => state.notifications,
  NotificationsActions
)
export default class Notifications extends Component {
  constructor() {
    super(...arguments)
    this.closeNotification = ::this.closeNotification
  }

  closeNotification(notification) {
    this.props.hideNotification(notification)
  }

  render() {
    let { notifications } = this.props

    if (!notifications.length) {
      return null
    }

    return (
      <div class='Notifications'>
        {
          notifications.map((notification, idx) => (
            <Notification
              key={notification.id}
              type={notification.type}
              message={notification.message}
              showClose={notification.showClose}
              onClose={() => this.closeNotification(notification)}
              closeTimeout={notification.closeTimeout}
            />
          ))
        }
      </div>
    )
  }
}

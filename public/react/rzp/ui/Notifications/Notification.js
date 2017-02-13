import { Component } from 'react'

class Notification extends Component {
  constructor() {
    super(...arguments)
    this.close = ::this.close
  }

  componentDidMount() {
    setTimeout(() => {
      $(this.notificationEle).addClass('Notification__show')
    }, 0)

    setTimeout(() => {
      this.close()
    }, 5000)
  }

  close() {
    if (this.isClosed) {
      return
    }

    $(this.notificationEle).removeClass('Notification__show')
    this.isClosed = true
    setTimeout(() => {
      this.props.onClose()
    }, 300)
  }

  render() {
    let { type, message, showClose } = this.props
    return (
      <div
        ref={(notificationEle) => { this.notificationEle = notificationEle }}
        class={`Notification ${type === 'success' ? 'Notification--success' : 'Notification--error'}`}>
        {
          typeof message === 'function' ? message() : message
        }
        {
          showClose && <i class='fa fa-close' onClick={this.close}></i>
        }
      </div>
    )
  }
}

Notification.defaultProps = {
  type: 'success',
  showClose: true,
  onClose: () => {}
}

export default Notification

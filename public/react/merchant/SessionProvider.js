import { Component, PropTypes, Children } from 'react'
import session from 'merchant/modules/session'

export default class SessionProvider extends Component {
  getChildContext() {
    return {
      session: this.session
    }
  }

  componentWillMount() {
    let user = this.props.user
    let identity = user.getIdentity()
    debugger
    this.session = session.initialize(identity)
  }

  render() {
    return Children.only(this.props.children)
  }
}

SessionProvider.propTypes = {
  children: PropTypes.element.isRequired
}

SessionProvider.childContextTypes = {
  session: PropTypes.object
}

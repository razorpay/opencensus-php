import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'

@connect(
  (state) => state.session,
  null
)
export default class ShowWhen extends Component {
  render() {
    let {
      notMyRole,
      children,
      featureEnabled,
    } = this.props

    let roles = notMyRole.split(' ')
    let user = this.props.user
    let userRole

    if (user) {
      userRole = user.merchants[user.id].pivot.role
    } else {
      return null
    }

    if(roles.indexOf(userRole) > -1) {
      return null
    }

    if (featureEnabled && user.tags && user.tags.indexOf(featureEnabled) === -1) {
      return null
    }

    return children
  }
}

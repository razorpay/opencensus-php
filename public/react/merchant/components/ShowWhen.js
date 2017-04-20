import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'

@connect(
  (state) => state.session,
  null
)
export default class ShowWhen extends Component {
  render() {
    let {
      notMyRole = '',
      children,
      featureEnabled,
    } = this.props

    let roles = notMyRole.split(' ')
    let user = this.props.user
    let tags = (user && user.tags) || []
    let userRole

    if (user) {
      userRole = user.merchants[user.id].pivot.role
    }

    if(roles.indexOf(userRole) > -1) {
      return null
    }

    if (featureEnabled && tags.indexOf(featureEnabled) === -1) {
      return null
    }

    return children
  }
}

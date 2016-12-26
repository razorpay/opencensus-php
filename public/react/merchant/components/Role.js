import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'

@connect(
  (state) => state.session,
  null
)
export default class Role extends Component {
  render() {
    let { notMyRole, children, className } = this.props
    let roles = notMyRole.split(' ')
    let user = this.props.user
    let userRole

    if (user) {
      userRole = user.merchants[user.id].pivot.role
    }

    if(roles.indexOf(userRole) > -1) {
      return null;
    }

    return (
      <div class={className}>
        {children}
      </div>
    )
  }
}

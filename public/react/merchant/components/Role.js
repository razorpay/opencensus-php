import { PropTypes } from 'react'

export default function Role(props, context) {
  let { notMyRole, children, className } = props
  let roles = notMyRole.split(' ')

  if(roles.indexOf(context.session.userRole) > -1) {
    return null;
  }

  return (
    <div class={className}>
      {children}
    </div>
  )
}

Role.contextTypes = {
  session: PropTypes.object
}

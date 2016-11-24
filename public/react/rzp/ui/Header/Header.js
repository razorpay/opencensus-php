import { PropTypes } from 'react'
import { titleCase } from 'rzp/utils/rzp-utils'
import './Header.styl'

export default function Header(props, context) {
  let { title, showMode, isLoading, children, ...attributes } = props
  return (
    <div class='header'>
      <h1 {...attributes}>
        {title} {showMode && `(${titleCase(context.session.currentMode)} Mode)`}
      </h1>
      {children}
    </div>
  )
}

Header.defaultProps = {
  showMode: true
}

Header.propTypes = {
  showMode: PropTypes.bool,
  title: PropTypes.string
}

Header.contextTypes = {
  session: PropTypes.object
}

import { Component, PropTypes } from 'react'
import { titleCase } from 'rzp/utils/rzp-utils'
import './Header.styl'
import { connect } from 'react-redux'

@connect(
  (state) => state.session,
  null
)
export default class Header extends Component {
  render() {
    let { title, showMode, isLoading, children, className } = this.props
    return (
      <div class='header'>
        <h1 class={className}>
          {title} {showMode && `(${titleCase(this.props.mode)} Mode)`}
        </h1>
        {children}
      </div>
    )
  }
}

Header.defaultProps = {
  showMode: true
}

Header.propTypes = {
  showMode: PropTypes.bool,
  title: PropTypes.string
}

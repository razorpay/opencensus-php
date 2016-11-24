import { Component, PropTypes, Children } from 'react'

export default class NgRouterProvider extends Component {
  getChildContext() {
    return {
      ngRouter: this.ngRouter
    }
  }

  constructor(props, context) {
    super(props, context)
    this.ngRouter = props.ngRouter
  }

  render() {
    return Children.only(this.props.children)
  }
}

NgRouterProvider.propTypes = {
  ngRouter: PropTypes.object.isRequired,
  children: PropTypes.element.isRequired
}

NgRouterProvider.childContextTypes = {
  ngRouter: PropTypes.object.isRequired
}

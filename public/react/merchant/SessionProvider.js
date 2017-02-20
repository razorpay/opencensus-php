import { Component, PropTypes, Children } from 'react'
import { connect } from 'react-redux'
import { updateSession } from 'merchant/modules/session'

@connect(
  null,
  { updateSession }
)
export default class SessionProvider extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      loading: false
    }
  }

  updateSession(user = null) {
    this.props.updateSession({
      user,
      mode: this.props.modeFactory.getMode()
    })
    this.setState({
      loading: false
    })
  }

  componentWillMount() {
    let user = this.props.user
    this.setState({
      loading: true
    })
    user.identity().then((user) => {
      this.updateSession(user)
    }).catch((err) => {
      throw err
    })
  }

  render() {
    if (this.state.loading) {
      return null
    }

    return Children.only(this.props.children)
  }
}

SessionProvider.propTypes = {
  children: PropTypes.element.isRequired
}

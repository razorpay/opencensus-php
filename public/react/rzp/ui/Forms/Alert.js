import { PropTypes, Component } from 'react'
import cx from 'classnames'
import { makeArray } from 'rzp/utils/rzp-utils'

class Alert extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      close: false
    }
    this.close = ::this.close
  }

  componentWillReceiveProps(nextProps) {
    if (typeof nextProps.message === 'string' || (nextProps.message && (nextProps.message !== this.props.message))) {
      this.setState({
        close: false
      })
    }
  }

  close() {
    this.setState({
      close: true
    })
  }

  render() {
    let props = this.props
    let msgs = makeArray(props.message)

    return (
      <div>
        {
          (!this.state.close && msgs.length) ?
          <div
            class={cx(
              'alert alert-dismissable text-center',
              props.type === 'error'? 'alert-danger' : 'alert-success'
            )}
            style={{borderRadius: 0}}>
            <button type='button' class='close' onClick={this.close}>
              <span>×</span>
            </button>

            <ul class='list-unstyled'>
              {msgs.map((msg, index) => <li key={index}>{JSON.stringify(msg)}</li>)}
            </ul>
          </div> : null
        }
      </div>
    )
  }
}

Alert.displayName = 'FormAlert'

Alert.propTypes = {
  type: PropTypes.string
}

export default Alert

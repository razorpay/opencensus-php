import { Component, PropTypes, Children } from 'react'
import ConfirmModal from './ConfirmModal'

export default class ConfirmModalProvider extends Component {
  getChildContext() {
    return {
      confirm: this.confirm
    }
  }

  constructor(props, context) {
    super(props, context)
    this.state = {
      confirmResolve: null,
      confirmReject: null,
      show: false,
      options: {}
    }
    this.confirm = ::this.confirm
    this.affirm = ::this.affirm
    this.abort = ::this.abort
  }

  confirm(options = {}) {
    let promise = new Promise((resolve, reject) => {
      this.setState({
        confirmResolve: resolve,
        confirmReject: reject
      })
    })

    if (typeof options === 'string') {
      options = {
        message: options
      }
    }

    this.setState({
      show: true,
      options
    })

    return promise
  }

  affirm() {
    this.close()
    return this.state.confirmResolve()
  }

  abort() {
    this.close()
    return this.state.confirmReject()
  }

  close() {
    this.setState({
      show: false
    })
  }

  render() {
    let { show, options } = this.state
    return (
      <div>
        <ConfirmModal
          show={show}
          options={options}
          onAffirm={this.affirm}
          onAbort={this.abort}
        />

        {Children.only(this.props.children)}
      </div>
    )
  }
}

ConfirmModalProvider.propTypes = {
  children: PropTypes.element.isRequired
}

ConfirmModalProvider.childContextTypes = {
  confirm: PropTypes.func
}

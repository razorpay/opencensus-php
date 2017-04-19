import { Component } from 'react'
import './FileUploadButton.styl'

// Duplicated AsyncButton logic here.
// TODO: Should make `react-async-button` quite composable in the upstream
export default class FileUploadButton extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      asyncState: null,
    }
  }

  componentWillUnmount() {
    this.isUnmounted = true
  }

  resetState() {
    this.setState({
      asyncState: null,
    })
  }

  handleChange(...args) {
    const eventHandler = this.props.onChange
    if (typeof eventHandler === 'function') {
      this.setState({
        asyncState: 'pending',
      })

      const returnFn = eventHandler.apply(null, args)
      if (returnFn && typeof returnFn.then === 'function') {
        returnFn.then(() => {
          if (this.isUnmounted) {
            return
          }
          this.setState({
            asyncState: 'fulfilled',
          })
        }).catch((error) => {
          if (this.isUnmounted) {
            return
          }
          this.setState({
            asyncState: 'rejected',
          })
          throw error
        })
      } else {
        this.resetState()
      }
    }
  }
  render() {
    const {
      text,
      pendingText,
      fulFilledText,
      rejectedText,
      disabled,
      labelClass,
      maxSize,
      ...attributes,
    } = this.props

    const { asyncState } = this.state
    const isPending = asyncState === 'pending'
    const isFulfilled = asyncState === 'fulfilled'
    const isRejected = asyncState === 'rejected'
    const isDisabled = disabled || isPending

    let buttonText
    if (isPending) {
      buttonText = pendingText
    } else if (isFulfilled) {
      buttonText = fulFilledText
    } else if (isRejected) {
      buttonText = rejectedText
    }
    buttonText = buttonText || text

    return (
      <label
        class={`fileupload-btn btn ${labelClass}`}
        disabled={isDisabled}
      >
        <i class='fa fa-folder-open'></i>
        <span>{ buttonText }</span>

        <input
          {...attributes}
          type='file'
          onChange={(event) => {
            if (event.target.files.length) {
              this.handleChange(event)
            }
          }}
        />
      </label>
    )
  }
}

FileUploadButton.defaultProps = {
  text: 'Choose File',
  pendingText: 'Uploading...',
  labelClass: 'btn-default'
}

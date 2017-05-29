import { Component } from 'react';
import { connect } from 'react-redux';
import { showNotification } from 'rzp/modules/notifications';
import './FileUploadButton.styl';

// Duplicated AsyncButton logic here.
// TODO: Should make `react-async-button` quite composable in the upstream
@connect(null, { showNotification })
export default class FileUploadButton extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      asyncState: null,
    };
  }

  componentWillUnmount() {
    this.isUnmounted = true;
  }

  resetState() {
    this.setState({
      asyncState: null,
    });
  }

  handleChange(...args) {
    const eventHandler = this.props.onChange;
    if (typeof eventHandler === 'function') {
      this.setState({
        asyncState: 'pending',
      });

      const returnFn = eventHandler.apply(null, args);
      if (returnFn && typeof returnFn.then === 'function') {
        returnFn
          .then(() => {
            if (this.isUnmounted) {
              return;
            }
            this.setState({
              asyncState: 'fulfilled',
            });
          })
          .catch(error => {
            if (this.isUnmounted) {
              return;
            }
            this.setState({
              asyncState: 'rejected',
            });
            throw error;
          });
      } else {
        this.resetState();
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
      showNotification,
      ...attributes
    } = this.props;

    const { asyncState } = this.state;
    const isPending = asyncState === 'pending';
    const isFulfilled = asyncState === 'fulfilled';
    const isRejected = asyncState === 'rejected';
    const isDisabled = disabled || isPending;

    let buttonText;
    if (isPending) {
      buttonText = pendingText;
    } else if (isFulfilled) {
      buttonText = fulFilledText;
    } else if (isRejected) {
      buttonText = rejectedText;
    }
    buttonText = buttonText || text;

    return (
      <label class={`fileupload-btn btn ${labelClass}`} disabled={isDisabled}>
        <i class="icon icon-folder" />
        <span>{buttonText}</span>

        <input
          {...attributes}
          type="file"
          onChange={event => {
            if (event.target.files.length) {
              if (maxSize && event.target.files[0].size > maxSize) {
                this.props.showNotification({
                  type: 'error',
                  message: 'Max file size allowed is 1 MB',
                });
              } else {
                this.handleChange(event);
              }
            }
          }}
        />
      </label>
    );
  }
}

FileUploadButton.defaultProps = {
  text: 'Choose File',
  pendingText: 'Uploading...',
  labelClass: 'btn-default',
};

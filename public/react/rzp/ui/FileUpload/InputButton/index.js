import { Component } from 'react';
import FileUploadButton from 'rzp/ui/FileUpload/Button';
import './FileUploadInputButton.styl';

export default class FileUploadInputButton extends Component {
  componentDidMount() {
    if (this.props.uploadedFileName) {
      this.setFileName(this.props.uploadedFileName);
    }
  }

  setFileName(value) {
    this.textInput.value = value;
  }

  render() {
    let { onChange, text, uploadedFileName, ...otherProps } = this.props;

    text = uploadedFileName ? 'Change File' : text;

    return (
      <div class="fileupload-input-group input-group">
        <input
          class={`form-control ${uploadedFileName ? 'file-uploaded' : ''}`}
          ref={input => {
            this.textInput = input;
          }}
          disabled={true}
        />

        <span class="input-group-addon">
          <FileUploadButton
            text={text}
            onChange={event => {
              let files = event.target.files || [];
              if (files.length) {
                this.setFileName(files[0].name);
              }
              return onChange(event);
            }}
            {...otherProps}
          />
        </span>
      </div>
    );
  }
}

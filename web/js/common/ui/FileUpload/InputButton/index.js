import React, { Component } from 'react';
import FileUploadButton from 'common/ui/FileUpload/Button';

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
      <div className="fileupload-input-group input-group">
        <input
          className={`form-control ${uploadedFileName ? 'file-uploaded' : ''}`}
          ref={input => {
            this.textInput = input;
          }}
          disabled={true}
        />

        <span className="input-group-addon">
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

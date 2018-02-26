import React, { Component } from 'react';
import { readableFileSize } from 'rzp/utils/rzp-utils';

import Staged from './Staged';

export default class FileUpload extends Component {
  static defaultProps = {
    multi: false,
    acceptedTypes: [],
    name: 'file-upload',
    onBiggerFileSize: () => {},
  };

  state = {
    files: [],
  };

  updateFile = file => {
    // check if file type is allowed
    if (this.isFileAllowed(file)) {
      if (this.props.onDrop) {
        this.props.onDrop(file);
      } else {
        this.setState({ files: [...this.state.files, file] });
      }
    }
  };

  getFiles = () => {
    return this.state.files;
  };

  isFileOfRightSize = file => {
    return file.size <= this.props.maxSize;
  };

  isFileTypeAllowed = file => {
    const type = file.type;
    const acceptedTypes = this.props.accept;
    return acceptedTypes.length === 0 || acceptedTypes.indexOf(type) > -1;
  };

  handleBiggerFile = fileSize => {
    this.props.onBiggerFileSize(fileSize);
  };

  isFileAllowed = file => {
    if (this.isFileOfRightSize(file)) {
      return this.isFileTypeAllowed(file);
    } else {
      this.handleBiggerFile(file.size);
      return false;
    }
  };

  getAcceptedFileTypesInfo = () => {
    let { accept } = this.props;
    let infotext = 'Only ';

    if (accept.length > 1) {
      infotext += `${accept.slice(0, -1).join(', ')} and ${accept.slice(-1)}`;
    } else {
      infotext += `${accept[0]}`;
    }

    infotext += ' files are allowed';

    return infotext;
  };

  handleCloseClick = fileIndex => () => {
    this.setState({
      files: this.state.files.filter((_, index) => index !== fileIndex),
    });
  };

  handleDrop = event => {
    event.preventDefault();
    var { dataTransfer } = event;
    const files = dataTransfer.items || dataTransfer.files;
    for (let index in files) {
      let file;
      if (dataTransfer.items) {
        if (files[index].kind === 'file') {
          file = files[index].getAsFile();
        } else {
          continue;
        }
      } else {
        file = files[index];
      }

      this.updateFile(file);
    }
  };

  handleDragOver = event => {
    event.preventDefault();
  };

  handleFileInputChange = event => {
    event.preventDefault();
    this.updateFile(event.currentTarget.files[0]);
  };

  render() {
    const { children, multi, maxSize, name, accept } = this.props;
    return (
      <div class="file upload">
        {!multi &&
          !this.state.files.length && (
            <div
              id="dropZone"
              onDrop={this.handleDrop}
              onDragOver={this.handleDragOver}
              onClick={this.handleClick}
              class="drop-zone"
            >
              <div class="content">
                {children || (
                  <React.Fragment>
                    <p class="content-primary m-t">
                      <span>Drop files here or </span>
                      <label for={`fileInput-${this.props.name}`}>
                        <span class="text-primary upload-label btn-link">
                          Click to Upload
                        </span>
                      </label>
                      <span>&nbsp;({readableFileSize(maxSize)} Max)</span>
                      <input
                        type="file"
                        id={`fileInput-${name}`}
                        onChange={this.handleFileInputChange}
                        accept={accept.map(fileType => fileTypesMap[fileType])}
                      />
                    </p>
                    <p class="content-secondary">
                      {this.getAcceptedFileTypesInfo()}
                    </p>
                  </React.Fragment>
                )}
              </div>
            </div>
          )}
        {this.state.files.map((file, index) => (
          <div
            class="staged-files"
            key={`${file.name}.${Math.random()}.${Math.random()}`}
          >
            <Staged file={file} onCloseClick={this.handleCloseClick(index)} />
          </div>
        ))}
      </div>
    );
  }
}

const fileTypesMap = {
  csv: 'text/csv',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', //new excel format
  pdf: 'application/pdf',
  xls: 'application/vnd.ms-excel', //Old microsoft excel sheets.
};

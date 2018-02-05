import React, { Component } from 'react';

import Staged from './Staged';

export default class FileUpload extends Component {
  state = {
    files: [],
  };

  updateFile = file => {
    if (this.props.onDrop) {
      this.props.onDrop(file);
    } else {
      this.setState({ files: [...this.state.files, file] });
    }
  };

  getFiles = () => {
    return this.state.files;
  };

  isFileAllowed = file => {
    const type = file.type.substr(file.type.indexOf('/') + 1);
    const acceptedTypes = this.props.accept;
    return acceptedTypes.length === 0 || acceptedTypes.indexOf(type) > -1;
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

      // check if file type is allowed
      if (this.isFileAllowed(file)) {
        this.updateFile(file);
      } else {
        continue;
      }
    }
  };

  handleDragOver = event => {
    event.preventDefault();
  };

  handleClick = () => {
    // TODO: handle click on upload component
  };

  render() {
    const { children } = this.props;
    return (
      <div class="file upload">
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
                <span>Drop files here or </span>
                <span class="text-primary">Click to Upload</span>
              </React.Fragment>
            )}
          </div>
        </div>
        {this.state.files.map(file => (
          <div
            class="staged-files"
            key={`${file.name}.${Math.random()}.${Math.random()}`}
          >
            <Staged file={file} />
          </div>
        ))}
      </div>
    );
  }
}

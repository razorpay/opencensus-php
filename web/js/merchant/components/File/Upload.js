import React, { Component } from 'react';

import { readableFileSize } from 'rzp/utils/rzp-utils';
import { classList } from 'common/util';

import Staged from './Staged';

export default class FileUpload extends Component {
  static defaultProps = {
    multi: false,
    acceptedTypes: [],
    uploadProgress: 0,
    name: 'file-upload',
    onBiggerFileSize: () => {},
    onFileChange: () => {},
    onCloseClick: () => {},
  };

  uniqFileId = null; // TODO: Considers only single file upload. Convert to array for multi file support.

  state = {
    files: [],
  };

  updateFile = file => {
    // check if file type is allowed
    if (this.isFileAllowed(file)) {
      if (this.props.onDrop) {
        this.props.onDrop(file);
      } else {
        this.setState({ files: [...this.state.files, file] }, _ =>
          this.onFileChange(file)
        );
      }
    }
  };

  progressTracker = progressEvent => {
    this.setState({ uploadProgress: progressEvent.loaded });
  };

  // TODO: Considers single file upload. Covert to array for multi file support
  onFileChange(file) {
    this.setState({ stagedFileStatus: 'process' });

    this.props.onFileChange
      .call(this, file, this.progressTracker)
      .then(data => {
        this.setState({ stagedFileStatus: 'uploaded' });
      })
      .catch(err => {
        this.setState({ stagedFileStatus: 'error' });
      });
  }

  getFiles = () => {
    return this.state.files;
  };

  // Checks filze size in bits
  isFileOfRightSize = file => {
    const maxSize = this.props.maxSize || MAX_API_LIMIT; // Max size is always 25MB
    return file.size <= maxSize;
  };

  isFileTypeAllowed = file => {
    const type = file.type;
    if (unsafeFileTypes.indexOf(type) < -1) {
      return false; // Don't allow unsafe file types in any case
    }

    if (!this.props.accept) {
      return true; // Allow all file types if not specified
    }

    const acceptedTypes = this.props.accept.map(
      fileType => fileTypesMap[fileType]
    );
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

    if (!accept) {
      return '';
    }

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
    this.setState(
      {
        files: this.state.files.filter((_, index) => index !== fileIndex),
      },
      _ => this.props.onCloseClick && this.props.onCloseClick()
    );
  };

  handleDrop = event => {
    event.preventDefault();
    const { dataTransfer } = event;
    const files = dataTransfer.items || dataTransfer.files;
    this.setState({ isFileDraggedInside: false }); // Reset the state

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

  toggleDragWithFile = e => {
    e.preventDefault();
    this.setState({ isFileDraggedInside: !this.state.isFileDraggedInside });
  };

  handleFileInputChange = event => {
    event.preventDefault();
    this.updateFile(event.currentTarget.files[0]);
  };

  render() {
    const { children, multi, maxSize, name, accept } = this.props;

    if (this.state.files) {
      this.uniqFileId = this.uniqFileId || `${name + new Date().getTime()}`;
    }

    return (
      <div class="Dropzone" onDragLeave={this.toggleDragWithFile}>
        {!multi &&
          !this.state.files.length && (
            <label
              class={classList(
                'Dropzone-cavity',
                this.state.isFileDraggedInside && 'Dropzone-cavity--highlight'
              )}
              for={`fileInput-${name}`}
              onDrop={this.handleDrop}
              onDragOver={this.handleDragOver}
              onDragEnter={this.toggleDragWithFile}
              onClick={this.handleClick}
            >
              <div class="Dropzone-content">
                {children || (
                  <React.Fragment>
                    <img
                      class="Dropzone-file-icon"
                      src={'img/files/file-placeholder.svg'}
                      alt=""
                    />
                    <p class="Dropzone-content-desc--primary">
                      Drop files here or{' '}
                      <b class="text-primary">Click to Upload</b>
                      {maxSize && (
                        <span>
                          <br />({readableFileSize(maxSize)} Max)
                        </span>
                      )}
                    </p>
                    <input
                      type="file"
                      id={`fileInput-${name}`}
                      onChange={this.handleFileInputChange}
                      accept={
                        accept && accept.map(fileType => fileTypesMap[fileType])
                      }
                      hidden
                    />
                    {do {
                      const acceptedFileTypes = this.getAcceptedFileTypesInfo();

                      if (acceptedFileTypes) {
                        <p class="Dropzone-content-desc--secondary">
                          {acceptedFileTypes}
                        </p>;
                      }
                    }}
                  </React.Fragment>
                )}
              </div>
            </label>
          )}
        {this.state.files &&
          this.state.files[0] && (
            <div class="Dropzone-cavity Dropzone-cavity--staged">
              <Staged
                file={this.state.files[0]}
                uniqFileId={this.uniqFileId}
                onCloseClick={this.handleCloseClick(0)}
                uploadedBytes={this.state.uploadProgress}
                stagedFileStatus={this.state.stagedFileStatus}
                showFileSize={maxSize}
              />
            </div>
          )}
      </div>
    );
  }
}

// To prevent accidental drop on window while on /activation route.
function handleUnsafeDrop(e) {
  e = e || event;
  e.preventDefault();
}

// To prevent accidental drop, Add this in componentDidMount
export function addDropShield(selector) {
  const el = document.querySelector(selector);

  el && el.addEventListener('drop', handleUnsafeDrop, false);
  el && el.addEventListener('dragover', handleUnsafeDrop, false);
}

// Remove the events in componentWillUnMount
export function removeDropShield(selector) {
  const el = document.querySelector(selector);

  el && el.removeEventListener('drop', handleUnsafeDrop);
  el && el.removeEventListener('dragover', handleUnsafeDrop);
}

const MAX_API_LIMIT = 25 * 1024 * 1024; // Max 25MB limit from api

const fileTypesMap = {
  csv: 'text/csv',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', //new excel format
  pdf: 'application/pdf',
  xls: 'application/vnd.ms-excel', //Old microsoft excel sheets.
};

// File type = docs are not safe to upload in general
const unsafeFileTypes = ['doc', 'docx'];

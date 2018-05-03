import React, { Component } from 'react';

import { readableFileSize } from 'rzp/utils/rzp-utils';
import { classList } from 'common/util';

import Staged from './Staged';

export default class FileUpload extends Component {
  static defaultProps = {
    multi: false,
    acceptedTypes: [],
    uploadedBytes: 0,
    name: 'file-upload',
    onBiggerFileSize: () => {},
    onFileChange: () => {},
    onCloseClick: () => {},
  };

  uniqFileId = null; // TODO: Considers only single file upload. Convert to array for multi file support.

  constructor(props) {
    super(props);

    this.state = {
      files: [],
      isDocPreUploaded: props.defaultValue,
    };
  }

  updateFile = file => {
    // check if file type is allowed
    if (this.isFileAllowed(file)) {
      this.props.onDrop && this.props.onDrop(file);

      this.setState({ files: [...this.state.files, file] }, _ =>
        this.onFileChange(file)
      );
    }
  };

  progressTracker = progressEvent => {
    this.setState({ uploadedBytes: progressEvent.loaded });
  };

  // TODO: Considers single file upload. Covert to array for multi file support
  onFileChange(file) {
    this.setState({ stagedFileStatus: 'process' });

    this.props.onFileChange
      .call(this, file, this.progressTracker)
      .then(data => {
        if (data.errors) {
          this.setState({ stagedFileStatus: 'error' });
        } else {
          this.setState({ stagedFileStatus: 'success' });
        }
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

    // Get patterns of each accepted file types
    const acceptedTypes = this.props.accept.map(
      fileType => fileTypesMap[fileType]
    );

    // Uploaded file type matches given pattern
    const isValidFilePattern = acceptedTypes.find(aT => {
      let pattern = new RegExp(aT);
      return pattern.test(type);
    });

    return acceptedTypes.length === 0 || isValidFilePattern;
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

    let infotext = 'Upload ';

    if (accept.length > 1) {
      infotext += `${accept.slice(0, -1).join(', ')} or ${accept.slice(-1)}`;
    } else {
      infotext += `${accept[0]}`;
    }

    infotext += ' file';

    return infotext;
  };

  handleCloseClick = fileIndex => () => {
    if (this.props.disabled) {
      return;
    }

    if (this.state.isDocPreUploaded) {
      this.setState({ isDocPreUploaded: false });

      return;
    }
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
    const {
      children,
      multi,
      maxSize,
      name,
      accept,
      disabled,
      showAcceptInfo = true,
    } = this.props;
    let { isDocPreUploaded } = this.state;

    if (this.state.files) {
      this.uniqFileId = this.uniqFileId || `${name + new Date().getTime()}`;
    }

    return (
      <div
        class="Dropzone"
        onDragLeave={isDocPreUploaded ? undefined : this.toggleDragWithFile}
      >
        {!multi &&
          !isDocPreUploaded &&
          !this.state.files.length && (
            <label
              class={classList(
                'Dropzone-cavity',
                this.state.isFileDraggedInside && 'Dropzone-cavity--highlight'
              )}
              for={`fileInput-${name}`}
              onDrop={isDocPreUploaded ? undefined : this.handleDrop}
              onDragOver={isDocPreUploaded ? undefined : this.handleDragOver}
              onDragEnter={
                isDocPreUploaded ? undefined : this.toggleDragWithFile
              }
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
                      Drop file here or{' '}
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
                      disabled={disabled}
                      hidden
                    />
                    {do {
                      const acceptedFileTypes = this.getAcceptedFileTypesInfo();

                      if (acceptedFileTypes && showAcceptInfo) {
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
        {!!(isDocPreUploaded || this.state.files.length) && (
          <div
            class={classList(
              'Dropzone-cavity',
              'Dropzone-cavity--staged',
              this.state.stagedFileStatus &&
                'Dropzone-cavity--' + this.state.stagedFileStatus,
              disabled && 'Dropzone-cavity--disabled'
            )}
          >
            <Staged
              file={this.state.files.length && this.state.files[0]}
              isDocPreUploaded={isDocPreUploaded}
              uniqFileId={this.uniqFileId}
              onCloseClick={this.handleCloseClick(0)}
              isDisabled={disabled}
              uploadedBytes={this.state.uploadedBytes}
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
  image: 'image/*',
};

// File type = docs are not safe to upload in general
const unsafeFileTypes = ['doc', 'docx'];

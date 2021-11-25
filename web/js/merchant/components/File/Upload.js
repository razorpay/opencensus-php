import React from 'react';
import { readableFileSize, titleCase, classList, isBlank } from 'common/utils/rzp-utils';
import Staged from './Staged';

const fileTypesMap = {
  csv: 'text/csv,application/vnd.ms-excel',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', //new excel format
  pdf: 'application/pdf',
  xls: 'application/vnd.ms-excel', //Old microsoft excel sheets.
  image: 'image/*',
  jpg: 'image/jpeg',
  png: 'image/png',
  xml: 'text/xml',
};

// File type = docs are not safe to upload in general
const unsafeFileTypes = ['doc', 'docx'];

const MAX_API_LIMIT = 25 * 1024 * 1024; // Max 25MB limit from api

/*
 * Common component to handle file upload
 * @props
 *  - {Boolean, default: false} `showStagedFileStatus` - shows the status of staged file in staged component
 *  - {Boolean, default: true} `showAcceptInfo` - shows file types accepted by file upload
 *  - {Boolean, default: true} `showCloseBtn` - shows cross button in staged component on right side
 *  - {Boolean, default: true} `showFileSize` - shows the size of file in staged component
 *
 *  - {Function, optional} `onBiggerFileSize` - callback if file selected a file of more than allowed file size
 *  - {Function, optional} `onCloseClick` - callback called when someone clicks on close button in staged component
 *
 *  - {Function, optional} `onFileChange` - callback called when file is selected for upload
 *                      Note:- if not passed then uploadedBytes, files, stagedFileStatus are taken from props instead of state
 *
 *  - {Array, optional} `acceptedTypes` - array of extensions for files component is allowed to accept
 *                       Note:- Please refer `fileTypesMap` for issues related to mime types
 *
 *  - {String, optional} `fileName` - File name to be shown for pre uploaded files, only works when defaultValue is present
 *
 *  - {String, optional} `downloadUrl` - Download url for pre uploaded files, only works when defaultValue and fileName is present
 */
export default class FileUpload extends React.Component {
  static defaultProps = {
    size: 'small',
    multi: false,
    acceptedTypes: [],
    uploadedBytes: 0,
    showCloseBtn: true,
    showStagedFileStatus: false,
    showAcceptInfo: true,
    showFileSize: true,
    onBiggerFileSize: () => {},
    onCloseClick: () => {},
    renderStagedChildren: () => null,
    hideLoader: false,
    stagedFileStatus: '',
  };

  constructor(props) {
    super(props);

    this.state = {
      files: [],
      isDocPreUploaded: !!props.defaultValue,
    };
    this.fileInputElement = React.createRef();
  }

  componentDidUpdate(prevProps) {
    if (prevProps.defaultValue !== this.props.defaultValue) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({
        isDocPreUploaded: !!this.props.defaultValue,
      });
    }
  }

  updateFile = (file) => {
    /*
      added a check to verifiy if any file is dropped or selected to the UI componenet
      as because there might be a case when on reselection if we cancel this updateFile method is triggered
     */
    if (file) {
      // check if file type is allowed
      if (this.isFileAllowed(file)) {
        if (this.props.onDrop) this.props.onDrop(file);

        this.setState(
          (prevState) => {
            return { files: [...prevState.files, file] };
          },
          () => {
            if (this.props.onFileChange) this.onFileChange(file);
          },
        );
      }
    }
  };

  progressTracker = (progressEvent) => {
    this.setState({ uploadedBytes: progressEvent.loaded });
  };

  // TODO: Considers single file upload. Covert to array for multi file support
  onFileChange(file) {
    this.setState({ stagedFileStatus: 'process' });

    const promise = this.props.onFileChange.call(this, file, this.progressTracker);

    if (promise && promise.then) {
      promise.then((data) => {
        if (data && data.errors) {
          // In some cases data was undefined while it was success. Mostly for slow connection.
          this.setState({ stagedFileStatus: 'error' });
        } else {
          this.setState({ stagedFileStatus: 'success' });
        }
      });
    } else {
      this.setState({ stagedFileStatus: null }); // Reset if not promise
    }
  }

  getFiles = () => {
    return this.state.files;
  };

  // Checks filze size in bits
  isFileOfRightSize = (file) => {
    const maxSize = this.props.maxSize || MAX_API_LIMIT; // Max size is always 25MB
    return file.size <= maxSize;
  };

  isFileTypeAllowed = (file) => {
    let type = file.type;

    //- windows sends empty file.type if it is not set in user registry
    //- so manually add file type from the map
    if (isBlank(type)) {
      const probableTypes = fileTypesMap[file.name.split('.').pop()];
      type = probableTypes.split(',')[0];
    }

    //- if it is still empty return false for other types which are not required
    if (isBlank(type)) {
      return false;
    }

    if (unsafeFileTypes.indexOf(type) < -1) {
      return false; // Don't allow unsafe file types in any case
    }

    if (!this.props.accept) {
      return true; // Allow all file types if not specified
    }

    // Get patterns of each accepted file types
    const acceptedTypes = this.props.accept.map((fileType) => fileTypesMap[fileType]);

    // Uploaded file type matches given pattern
    const isValidFilePattern = acceptedTypes.some((aT) => {
      const types = aT.split(',');

      for (const t of types) {
        const pattern = new RegExp(t.trim());
        if (pattern.test(type)) {
          return true;
        }
      }
      return false;
    });

    return acceptedTypes.length === 0 || isValidFilePattern;
  };

  handleBiggerFile = (fileSize) => {
    this.props.onBiggerFileSize(fileSize);
  };

  isFileAllowed = (file) => {
    if (this.isFileOfRightSize(file)) {
      return this.isFileTypeAllowed(file);
    } else {
      this.handleBiggerFile(file.size);
      return false;
    }
  };

  getAcceptedFileTypesInfo = () => {
    let accept = this.props.acceptFileInfo || this.props.accept;

    if (!accept) {
      return '';
    }

    let infotext = 'Upload ';

    accept = accept.map((fileType) => `.${fileType}`);

    if (accept.length > 1) {
      infotext += `${accept.slice(0, -1).join(', ')} or ${accept.slice(-1)}`;
    } else {
      infotext += `${accept[0]}`;
    }

    infotext += ' file';

    return infotext;
  };

  handleCloseClick = (fileIndex) => (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (this.props.disabled) {
      return;
    }

    if (this.state.isDocPreUploaded) {
      this.setState(
        { isDocPreUploaded: false },
        () => this.props.onCloseClick && this.props.onCloseClick(fileIndex),
      );

      return;
    }
    this.setState(
      (prevState) => {
        return {
          files: prevState.files.filter((_, index) => index !== fileIndex),
        };
      },
      () => {
        // resetting the input value attribute so that user can reupload the same file
        if (this.fileInputElement && this.fileInputElement.current) {
          this.fileInputElement.current.value = '';
        }
        if (this.props.onCloseClick) this.props.onCloseClick(fileIndex);
      },
    );
  };

  handleDrop = (event) => {
    event.preventDefault();
    const { dataTransfer } = event;
    const files = dataTransfer.items || dataTransfer.files;
    this.setState({ isFileDraggedInside: false }); // Reset the state

    try {
      if (this.props.onFileDrop) this.props.onFileDrop();
    } catch (err) {
      // empty catch block
    }

    // eslint-disable-next-line guard-for-in
    for (const index in files) {
      let file;
      if (dataTransfer.items) {
        if (files[index].kind === 'file') {
          file = files[index].getAsFile();
        } else {
          // eslint-disable-next-line no-continue
          continue; // disabling because no clue what this logic does
        }
      } else {
        file = files[index];
      }

      this.updateFile(file);
    }
  };

  handleDragOver = (e) => {
    e.preventDefault();
  };

  toggleDragWithFile = (e) => {
    e.preventDefault();
    // this.setState({ isFileDraggedInside: !this.state.isFileDraggedInside });
    this.setState((prevState) => {
      return {
        isFileDraggedInside: !prevState.isFileDraggedInside,
      };
    });
  };

  handleFileInputChange = (event) => {
    event.preventDefault();
    this.updateFile(event.currentTarget.files[0]);
  };

  showAcceptedFileTypes = (showAcceptInfo) => {
    const acceptedFileTypes = this.getAcceptedFileTypesInfo();

    if (acceptedFileTypes && showAcceptInfo) {
      return (
        <p class="Dropzone-content-desc--secondary text-muted small-text">{acceptedFileTypes}</p>
      );
    } else {
      return null;
    }
  };

  render() {
    const {
      children,
      multi,
      maxSize,
      name, // name will refer to input fied hence it should be unique,
      accept,
      disabled,
      onFileChange,
      showStagedFileStatus,
      showAcceptInfo,
      renderStagedChildren,
      showFileSize,
      size,
      fileName,
      downloadUrl,
      dropZoneCavityClassName,
      imgFilePreviewUrl,
      removeFileButtonLabel,
      hideLoader,
      customClassName = '', // for adding custom css over the fileupload component
    } = this.props;
    const { isDocPreUploaded } = this.state;

    let stagedFileStatus, uploadedBytes;

    if (onFileChange) {
      stagedFileStatus = this.state.stagedFileStatus;
      uploadedBytes = this.state.uploadedBytes;
    } else {
      stagedFileStatus = this.props.stagedFileStatus;
      uploadedBytes = this.props.uploadedBytes;
    }

    let files = this.props.files || this.state.files;
    // if doc is pre-uploaded inserting one dummy file object to be provided to Staged
    files = isDocPreUploaded ? [{}] : files;

    return (
      <div
        class={classList('Dropzone', customClassName)}
        id={`Dropzone-${name}`}
        onDragLeave={isDocPreUploaded ? undefined : this.toggleDragWithFile}
      >
        {!isDocPreUploaded && (multi || !files.length) && (
          <label
            class={classList(
              'Dropzone-cavity',
              this.state.isFileDraggedInside && 'Dropzone-cavity--highlight',
            )}
            onClick={() => {
              window.rzpAnalytics({
                eventCategory: `Batch ${titleCase(this.props.batchType)}`,
                eventAction: 'Upload file -  upload modal',
                eventLabel: `Click to upload file`,
              });
            }}
            for={`fileInput-${name}`}
            onDrop={isDocPreUploaded ? undefined : this.handleDrop}
            onDragOver={isDocPreUploaded ? undefined : this.handleDragOver}
            onDragEnter={isDocPreUploaded ? undefined : this.toggleDragWithFile}
          >
            <div class={`Dropzone-content ${size}`}>
              {!children ? (
                <React.Fragment>
                  <img
                    class="Dropzone-file-icon"
                    src="/dist/css/assets/files/file-placeholder.svg"
                    alt=""
                  />
                  <div class="Dropzone-content-desc">
                    <p class="Dropzone-content-desc--primary upload-file-heading">
                      Drop file here or <b class="text-primary">Click to Upload</b>{' '}
                      {maxSize && (
                        <React.Fragment>({readableFileSize(maxSize)} Max)</React.Fragment>
                      )}
                    </p>
                    {this.showAcceptedFileTypes(showAcceptInfo)}
                  </div>
                  <input
                    type="file"
                    id={`fileInput-${name}`}
                    onChange={this.handleFileInputChange}
                    accept={accept && accept.map((fileType) => fileTypesMap[fileType])}
                    disabled={disabled}
                    ref={this.fileInputElement}
                    hidden
                  />
                </React.Fragment>
              ) : (
                <React.Fragment>
                  {children}
                  <input
                    type="file"
                    id={`fileInput-${name}`}
                    onChange={this.handleFileInputChange}
                    accept={accept && accept.map((fileType) => fileTypesMap[fileType])}
                    disabled={disabled}
                    ref={this.fileInputElement}
                    hidden
                  />
                </React.Fragment>
              )}
            </div>
          </label>
        )}
        {!!(isDocPreUploaded || files.length) && (
          <div
            class={classList(
              'Dropzone-cavity',
              'Dropzone-cavity--staged',
              stagedFileStatus && `Dropzone-cavity--${stagedFileStatus}`,
              disabled && 'Dropzone-cavity--disabled',
              dropZoneCavityClassName,
            )}
          >
            {files.map((file, index) => (
              <Staged
                file={file}
                key={index}
                fileName={fileName}
                downloadUrl={downloadUrl}
                isDocPreUploaded={isDocPreUploaded}
                onCloseClick={this.props.showCloseBtn && this.handleCloseClick(index)}
                isDisabled={disabled}
                uploadedBytes={uploadedBytes}
                stagedFileStatus={stagedFileStatus}
                showFileSize={showFileSize && maxSize}
                showStagedFileStatus={showStagedFileStatus}
                name={`name-${index}`}
                size={size}
                preUploadedImgFileUrl={imgFilePreviewUrl}
                removeFileButtonLabel={removeFileButtonLabel}
                hideLoader={
                  hideLoader && (index !== files.length - 1 || stagedFileStatus !== 'process')
                }
              >
                {renderStagedChildren(index)}
              </Staged>
            ))}
          </div>
        )}
      </div>
    );
  }
}

// To prevent accidental drop on window while on /activation route.
function handleUnsafeDrop(e) {
  // eslint-disable-next-line no-restricted-globals
  e = e || event;
  e.preventDefault();
}

// To prevent accidental drop, Add this in componentDidMount
export function addDropShield(selector) {
  const el = document.querySelector(selector);

  if (el) el.addEventListener('drop', handleUnsafeDrop, false);
  if (el) el.addEventListener('dragover', handleUnsafeDrop, false);
}

// Remove the events in componentWillUnMount
export function removeDropShield(selector) {
  const el = document.querySelector(selector);

  if (el) el.removeEventListener('drop', handleUnsafeDrop);
  if (el) el.removeEventListener('dragover', handleUnsafeDrop);
}

import { readableFileSize } from 'common/utils/rzp-utils';
import React from 'react';

const avlblFileTypeIcons = ['pdf', 'jpg', 'png', 'csv', 'xlsx'];

const getFileTypeIcon = (fileName) => {
  let fileType = fileName.split('.');
  fileType = fileType[fileType.length - 1];

  return avlblFileTypeIcons.indexOf(fileType) > -1 ? fileType : 'misc';
};

// If same name file is uploaded to another FileUpload component, name will help React to distinguish
export default class Staged extends React.Component {
  UNSAFE_componentWillReceiveProps(nextProps) {
    if (
      nextProps.uploadedBytes !== this.props.uploadedBytes &&
      document.getElementById(`${this.props.name}--progress`)
    ) {
      document.getElementById(`${this.props.name}--progress`).style.transform = 'none'; // Halt previous transform
    }
  }

  getProgress() {
    const { uploadedBytes, stagedFileStatus: currentStatus, file } = this.props;

    // Check 1: If no process started, then loader be at same place
    if (!uploadedBytes) {
      this.lastPercProgress = -100;
      return { progress: this.lastPercProgress, duration: 0 }; // No Progress
    }

    let PercProgress = uploadedBytes / file.size;
    PercProgress = PercProgress >= 1 ? 1 : PercProgress; // Due to packet size, uploadedBytes could be >= file.size

    // Check 2: Axios returns start of upload as (uploadedBytes = file.size) , or eq packet size. But it's no progress
    if (PercProgress === 1 && !this.lastPercProgress) {
      return { progress: this.lastPercProgress, duration: 0 }; // No Progress (Same as Check 1)
    } else if (currentStatus === 'success') {
      // Check 3: If file uploaded response came from server
      PercProgress = 1;
    }

    const progress = -70 + 70 * PercProgress; // At t0, translateX = -100%. At t1 of start, we start from translateX = -70%;
    const duration = (Math.abs(progress) * 5) / 100; // 100% translate in 5s and rest in proportions

    this.lastPercProgress = PercProgress;

    return { progress, duration };
  }

  render() {
    const {
      file,
      stagedFileStatus: currentStatus,
      showFileSize,
      name,
      size,
      fileName,
      downloadUrl,
      isDisabled,
      onCloseClick,
      showStagedFileStatus,
      isDocPreUploaded,
      preUploadedImgFileUrl,
      removeFileButtonLabel,
      hideLoader = false,
      showOnlyFileSize,
    } = this.props;

    const loader = this.getProgress();

    return (
      <div className={`Dropzone-content staged-content ${size}`} key={name}>
        {!isDocPreUploaded && (
          <img
            className="Dropzone-file-icon"
            src={require(`assets/files/file-type-${getFileTypeIcon(file ? file.name : '')}.svg`)}
            alt=""
          />
        )}
        {isDocPreUploaded && fileName && <i className="i i-check-circle pre-uploaded" />}
        <div className="Dropzone-content-desc">
          {isDocPreUploaded ? (
            fileName ? (
              <p className="Dropzone-content-desc--primary text-muted">
                {downloadUrl ? (
                  <a href={downloadUrl} target="_blank" rel="noreferrer noopener">
                    {fileName}
                  </a>
                ) : (
                  fileName
                )}
              </p>
            ) : (
              <p className="Dropzone-content-desc--primary text-success">
                {preUploadedImgFileUrl ? (
                  <img src={preUploadedImgFileUrl} height="48" />
                ) : (
                  <>
                    <i className="i i-check" />
                    File Already Uploaded
                  </>
                )}
              </p>
            )
          ) : (
            <React.Fragment>
              <p className="Dropzone-content-desc--primary staged-desc text-muted">
                {file.name} {showFileSize && readableFileSize(file.size)}
              </p>
              {showOnlyFileSize && (
                <p className="Dropzone-content-desc--primary text-muted">
                  {readableFileSize(file.size)}
                </p>
              )}
              {showStagedFileStatus && (
                // eslint-disable-next-line no-use-before-define
                <p className="text-muted text-small">{stagedStatusMsgMap[currentStatus]}</p>
              )}
            </React.Fragment>
          )}
          <div>{this.props.children}</div>
        </div>
        {!isDisabled &&
          onCloseClick &&
          currentStatus !== 'process' &&
          (removeFileButtonLabel ? (
            <span
              className="btn-link Dropzone-close"
              data-testid="btn-dropzone-close"
              onClick={onCloseClick}
            >
              {removeFileButtonLabel}
            </span>
          ) : (
            <span
              className="icon i-close Dropzone-close"
              data-testid="btn-dropzone-close"
              onClick={onCloseClick}
            />
          ))}
        {hideLoader ? null : (
          <div className="Loader">
            <div
              className="Loader-progress"
              id={`${name}--progress`}
              style={{
                transform: `translateX(${loader.progress}%)`,
                transitionDuration: `${loader.duration}s`,
              }}
            />
          </div>
        )}
      </div>
    );
  }
}

const stagedStatusMsgMap = {
  process: 'Processing File...',
  error: 'Processing Failed.',
};

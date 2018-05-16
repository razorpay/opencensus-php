import { readableFileSize } from 'rzp/utils/rzp-utils';

const avlblFileTypeIcons = ['pdf', 'jpg', 'png', 'csv', 'xlsx'];

const getFileTypeIcon = fileName => {
  let fileType = fileName.split('.');
  fileType = fileType[fileType.length - 1];

  return avlblFileTypeIcons.indexOf(fileType) > -1 ? fileType : 'misc';
};

// If same name file is uploaded to another FileUpload component, name will help React to distinguish
export default class Staged extends React.Component {
  componentWillReceiveProps(nextProps) {
    if (
      nextProps.uploadedBytes !== this.props.uploadedBytes &&
      document.getElementById(this.props.name + '--progress')
    ) {
      document.getElementById(this.props.name + '--progress').style.transform =
        'none'; // Halt previous transform
    }
  }

  getProgress() {
    const { uploadedBytes, stagedFileStatus: currentStatus, file } = this.props;

    // Check 1: If no process started, then loader be at same place
    if (!uploadedBytes) {
      this.lastPercProgress = -100;
      return { progress: this.lastPercProgress, duration: 0 }; // No Progress
    }

    let duration;
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
    duration = Math.abs(progress) * 5 / 100; // 100% translate in 5s and rest in proportions

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
      isDisabled,
      onCloseClick,
      showStagedFileStatus,
      isDocPreUploaded: defaultFile,
    } = this.props;

    const loader = this.getProgress();
    const isDocPreUploaded = !file && defaultFile; // if data already has file id

    return (
      <div class={`Dropzone-content ${size}`} key={name}>
        {!isDocPreUploaded && (
          <img
            class="Dropzone-file-icon"
            src={`img/files/file-type-${getFileTypeIcon(
              file ? file.name : ''
            )}.svg`}
            alt=""
          />
        )}
        <div class="Dropzone-content-desc">
          {isDocPreUploaded ? (
            <p class="Dropzone-content-desc--primary text-success">
              <i class="i i-check" />
              File Already Uploaded
            </p>
          ) : (
            <React.Fragment>
              <p class="Dropzone-content-desc--primary text-muted">
                {file.name} {showFileSize && readableFileSize(file.size)}
              </p>
              {showStagedFileStatus && (
                <p class="text-muted text-small">
                  {stagedStatusMsgMap[currentStatus]}
                </p>
              )}
            </React.Fragment>
          )}
        </div>
        <div>{this.props.children}</div>
        {!isDisabled &&
          onCloseClick && (
            <span class="icon i-close Dropzone-close" onClick={onCloseClick} />
          )}

        <div class="Loader">
          <div
            class="Loader-progress"
            id={name + '--progress'}
            style={{
              transform: 'translateX(' + loader.progress + '%)',
              transitionDuration: loader.duration + 's',
            }}
          />
        </div>
      </div>
    );
  }
}

const stagedStatusMsgMap = {
  process: 'Processing File...',
  error: 'Processing Failed.',
};

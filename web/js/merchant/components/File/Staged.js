import { readableFileSize } from 'rzp/utils/rzp-utils';

const avlblFileTypeIcons = ['pdf', 'jpg', 'png', 'csv', 'xlsx'];

const getFileTypeIcon = fileName => {
  const fileType = fileName.split('.')[1];
  return avlblFileTypeIcons.indexOf(fileType) > -1 ? fileType : 'misc';
};

export default props => {
  const { file, progress = 0, currentStatus, onCloseClick = () => {} } = props;
  return (
    <div class={`staged-file ${currentStatus || ''}`} key={`${file.name}`}>
      <div class="file-icon">
        <div>
          <span class={`file-type-${getFileTypeIcon(file.name)}`} />
        </div>
      </div>
      <div class="file-details">
        <div>
          <div>
            <strong class="text-muted">
              {file.name} ({readableFileSize(file.size)})
            </strong>
          </div>
          <div class="text-muted">
            <span>{stagedStatusMsgMap[currentStatus] || ''}</span>
          </div>
        </div>
        {props.children}
      </div>
      {currentStatus !== 'process' && (
        <div class="close-icon">
          <div>
            <span class="icon i-close" onClick={onCloseClick} />
          </div>
        </div>
      )}
      {currentStatus === 'process' && <div class="loader" />}
    </div>
  );
};

const stagedStatusMsgMap = {
  process: 'Uploading File...',
  error: 'Processing Failed.',
};

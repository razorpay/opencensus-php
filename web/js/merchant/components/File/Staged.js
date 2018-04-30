import { readableFileSize } from 'rzp/utils/rzp-utils';
import { titleCase } from 'common/util';

const avlblFileTypeIcons = ['pdf', 'jpg', 'png', 'csv', 'xlsx'];

const getFileTypeIcon = fileName => {
  const fileType = fileName.split('.')[1];
  return avlblFileTypeIcons.indexOf(fileType) > -1 ? fileType : 'misc';
};

const getFileAttr = attr => ({
  file = {},
  [`file${titleCase(attr)}`]: attrVal,
}) => file[attr] || attrVal;

export default props => {
  const { file, progress = 0, currentStatus, onCloseClick } = props;
  const getFileName = getFileAttr('name');
  const getFileSize = getFileAttr('size');
  return (
    <div class={`staged-file ${currentStatus || ''}`}>
      <div class="file-icon">
        <div>
          <span class={`file-type-${getFileTypeIcon(getFileName(props))}`} />
        </div>
      </div>
      <div class="file-details">
        <div>
          <div>
            <strong class="text-muted">
              {getFileName(props)} ({readableFileSize(getFileSize(props))})
            </strong>
          </div>
          <div class="text-muted">
            <span>{stagedStatusMsgMap[currentStatus] || ''}</span>
          </div>
        </div>
        {props.children}
      </div>
      {onCloseClick && (
        <div class="close-icon">
          <div>
            <span class="icon i-close" onClick={onCloseClick} />
          </div>
        </div>
      )}
      {!!progress && (
        <div class="upload-status-bar">
          <div style={{ width: `${progress}%` }} class="status" />
        </div>
      )}
      {currentStatus === 'process' && !progress && <div class="loader" />}
    </div>
  );
};

const stagedStatusMsgMap = {
  process: 'Uploading File...',
  error: 'Processing Failed.',
};

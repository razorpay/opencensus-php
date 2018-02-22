const sizes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

const readableFileSize = bytes => {
  if (!bytes) return `0 bytes`;
  var e = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, e)).toFixed(2)} ${sizes[e]}`;
};

const avlblFileTypeIcons = ['pdf', 'jpg', 'png', 'csv'];

const getFileTypeIcon = fileName => {
  const fileType = fileName.split('.')[1];
  return avlblFileTypeIcons.indexOf(fileType) > -1 ? fileType : 'misc';
};

export default props => {
  const { file, progress = 0, onCloseClick = () => {} } = props;
  return (
    <div class="staged-file" key={`${file.name}`}>
      <div class="file-icon">
        <div>
          <span class={`icon i-file-type-${getFileTypeIcon(file.name)}`} />
        </div>
      </div>
      <div class="file-details">
        <div>
          <span class="text-muted">
            {file.name} ({readableFileSize(file.size)})
          </span>
        </div>
        {props.children}
      </div>
      <div class="close-icon">
        <div>
          <span class="icon i-close" onClick={onCloseClick} />
        </div>
      </div>
      <div class="upload-status-bar">
        <div class="status" style={{ width: `${progress}%` }} />
      </div>
    </div>
  );
};

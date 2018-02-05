const sizes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

const readableFileSize = bytes => {
  var e = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, e)).toFixed(2)} ${sizes[e]}`;
};

export default props => {
  const { file } = props;
  return (
    <div class="staged-file" key={`${file.name}`}>
      <div class="file-icon pull-left" />
      <div class="file-details pull-right">
        <div>
          <span class="text-muted">
            {file.name} ({readableFileSize(file.size)})
          </span>
        </div>
      </div>
      {props.children}
    </div>
  );
};

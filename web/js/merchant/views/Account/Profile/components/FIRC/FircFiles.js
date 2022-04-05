import { downloadFiles } from './service';

const FircFiles = (props) => {
  const { files, month, year } = props;

  return (
    <div className="files-container">
      {files.length > 0 && (
        <div>
          <div className="count-label">
            {files.length}
            {files.length === 1 ? ' file ' : ' files '}
            found
          </div>
          <div className="scroll-list">
            {files.map((file) => (
              <div key={file?.id} className="file">
                <div>
                  <i className="i i-file-sheet file-icon" />
                  <span>{file?.name}</span>
                </div>
                <div>
                  <i
                    className="i-download-blue download-icon"
                    onClick={() => {
                      downloadFiles({ month, year, document_id: file?.id });
                    }}
                  />
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default FircFiles;

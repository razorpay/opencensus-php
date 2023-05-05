import React, { useState } from 'react';
import Input from 'common/new-ui/Input';
// eslint-disable-next-line import/no-extraneous-dependencies
import { v4 as uuid } from 'uuid';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import { connect } from 'react-redux';

const MultiFileUpload = ({
  name,
  label,
  onFileChange,
  removeFileType = () => {},
  required,
  onFileRemove,
  defaultFiles,
  showNotification,
  ...rest
}) => {
  const preUploadedFiles =
    defaultFiles && defaultFiles.length ? defaultFiles : [{ id: `${name}-${uuid()}` }];

  const [files, setFiles] = useState(preUploadedFiles);

  const addAnotherFile = () => {
    // Max 3 files can be uploaded per file type
    if (files.length < 3) {
      setFiles((f) => [...f, { id: `${name}-${uuid()}` }]);
    }
  };

  const removeAdditionalFile = (fileId) => {
    const updatedFiles = files.filter((f) => f.id !== fileId);
    // If there's no file present, remove file type
    if (!required && updatedFiles.length === 0) {
      removeFileType(name, fileId);
    }
    setFiles(updatedFiles);
    onFileRemove(fileId, name); // delete from server
  };

  return (
    <div class="file">
      {files.map((file, idx) => {
        return (
          <DismissableInput
            key={file.id}
            name={file.id}
            label={idx === 0 ? label : ''}
            onFileChange={(file, progressTracker) => {
              // Update file info which is uploaded
              onFileChange(file, progressTracker)?.then((uploadedDocInfo) => {
                if (uploadedDocInfo?.id) {
                  setFiles((currentFiles) =>
                    currentFiles.map((currentFile, currentFileIdx) => {
                      if (idx === currentFileIdx) {
                        return {
                          ...uploadedDocInfo,
                        };
                      }
                      return currentFile;
                    }),
                  );
                }
              });
            }}
            onFileRemove={() => {
              if (required) {
                const updatedFiles = files.filter((f) => f.id !== file.id);
                if (updatedFiles.length === 0) {
                  updatedFiles.push({ id: `${name}-${uuid()}` }); // adding an empty file upload component for required file types
                }
                setFiles(updatedFiles);
              } else if (files.length > 1) {
                const updatedFiles = files.filter((f) => f.id !== file.id);
                setFiles(updatedFiles); // removing additional file
              } else {
                removeFileType(name, file.id); // removing entire file type
              }
              return onFileRemove(file.id, name);
            }}
            required={required && idx === 0}
            defaultValue={file.display_name}
            removeFileType={() => removeAdditionalFile(file.id)}
            fileName={
              <span
                class="btn-link"
                onClick={() => {
                  merchantFetch(`documents/${file.id}`)
                    .then((res) => {
                      if (res?.data?.url) {
                        window.open(res.data.url);
                      }
                    })
                    .catch((err) => {
                      showNotification({
                        type: 'error',
                        message: err?.errors || err,
                      });
                    });
                }}
              >
                {file.display_name}
              </span>
            }
            showRemoveButton={!file.display_name} // don't show remove button if file is uploaded
            {...rest}
          />
        );
      })}

      {files.length < 3 && !rest.disabled && (
        <span onClick={addAnotherFile} class="add-another">
          + Add another file
        </span>
      )}
    </div>
  );
};

const DismissableInput = ({
  label,
  name,
  removeFileType,
  required,
  onFileChange,
  onFileRemove,
  showRemoveButton,
  ...rest
}) => {
  const [showRemove, setShowRemove] = useState(showRemoveButton);

  const handleFileChange = (file, progressTracker) => {
    setShowRemove(false);
    return onFileChange(file, progressTracker);
  };

  const handleCloseClick = () => {
    setShowRemove(true);
    // Delete file from server
    onFileRemove();
  };

  const handleRemoveFileType = () => {
    removeFileType(name);
  };

  return (
    <div class="remove-wrapper">
      <Input.File
        name={name}
        label={label}
        // showCloseBtn={false}
        required={required}
        maxSize={5243000} // 5MB
        showFileSize={false}
        onChange={handleFileChange}
        onCloseClick={handleCloseClick}
        {...rest}
      />
      {!required && showRemove && (
        <span
          class="remove icon i-close"
          data-testid="btn-file-remove"
          onClick={handleRemoveFileType}
        />
      )}
    </div>
  );
};

export default connect(null, { showNotification: displayNotification })(MultiFileUpload);

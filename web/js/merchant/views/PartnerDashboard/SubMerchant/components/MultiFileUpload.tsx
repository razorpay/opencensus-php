import React, { useState } from 'react';
import Input from 'common/new-ui/Input';
// eslint-disable-next-line import/no-extraneous-dependencies
import { v4 as uuid } from 'uuid';
import { Box, CloseIcon, Text } from '@razorpay/blade/components';
import styled from 'styled-components';

const CloseIconContainer = styled.span(({ theme }) => ({
  position: 'absolute',
  top: '50%',
  right: theme.spacing[3],
  padding: theme.spacing[2],
  cursor: 'pointer',
}));

const DismissableInput = ({
  label,
  name,
  onFileChange,
  onFileRemove,
  showRemoveButton,
}): JSX.Element => {
  const [showRemove, setShowRemove] = useState(showRemoveButton);
  const handleFileChange = (file, progressTracker) => {
    setShowRemove(false);
    return onFileChange(file, progressTracker);
  };

  const handleCloseClick = () => {
    setShowRemove(true);
    onFileRemove();
  };

  const handleRemoveFileType = () => {
    onFileRemove();
  };
  return (
    <Box position="relative">
      <Input.File
        name={name}
        label={label}
        maxSize={52430000} // 50MB
        showFileSize={false}
        onChange={handleFileChange}
        onCloseClick={handleCloseClick}
        multi={false}
        _accept={['pdf']}
      />
      {showRemove ? (
        <CloseIconContainer onClick={handleRemoveFileType} data-testid="btn-file-remove">
          <CloseIcon color="interactive.icon.gray.normal" size="medium" />
        </CloseIconContainer>
      ) : null}
    </Box>
  );
};

const AddButtonContainer = styled.span`
  &:hover {
    cursor: pointer;
  }
`;

type FilesArray = {
  id: string | undefined;
  display_name?: string | undefined;
  error?: string | undefined;
}[];

const MultiFileUpload = ({ name, label, onFileChange, onFileRemove }): JSX.Element => {
  const [files, setFiles] = useState<FilesArray>([{ id: `${name}-${uuid()}` }]);

  const addAnotherFile = () => {
    setFiles((f) => [...f, { id: `${name}-${uuid()}` }]);
  };

  const removeFile = (file) => {
    const updatedFiles = files.filter((f) => f.id !== file.id);
    if (updatedFiles.length === 0) {
      updatedFiles.push({ id: `${name}-${uuid()}` });
    }
    setFiles(updatedFiles);

    return onFileRemove(file.id);
  };
  return (
    <Box display="flex" flexDirection="column" rowGap="12px">
      {files.map((file, idx) => {
        return (
          <DismissableInput
            key={file.id}
            name={file.id}
            label={idx === 0 ? label : ''}
            onFileChange={(file, progressTracker) => {
              // Update file info which is uploaded
              onFileChange(file, progressTracker)?.then((uploadedDocResponse) => {
                if (uploadedDocResponse?.store_id) {
                  setFiles((currentFiles) =>
                    currentFiles.map((currentFile, currentFileIdx) => {
                      if (idx === currentFileIdx) {
                        return {
                          ...currentFile,
                          ...uploadedDocResponse,
                        };
                      }
                      return currentFile;
                    }),
                  );
                }
              });
            }}
            onFileRemove={() => {
              removeFile(file);
            }}
            showRemoveButton={files.length > 1 && !file.display_name} // don't show remove button if file is uploaded
          />
        );
      })}
      <AddButtonContainer onClick={addAnotherFile}>
        <Text weight="semibold" size="medium" color="interactive.text.primary.subtle">
          + Add another file
        </Text>
      </AddButtonContainer>
    </Box>
  );
};

export default MultiFileUpload;

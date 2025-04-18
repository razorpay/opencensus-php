import React, { useEffect } from 'react';
import {
  Box,
  Heading,
  Text,
  TextInput,
  Button,
  Divider,
  PlusIcon,
  ArrowRightIcon,
  FileUpload,
  IconButton,
  TrashIcon,
  Alert,
} from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';
import { throttle } from 'lodash';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { v4 as uuid } from 'uuid';

import { getPreSignedUrl, uploadFileToPresignedUrl } from 'merchant/views/Reconciliations/api';
import { showNotification } from 'merchant_common/reducers/notifications';

import type { UploadSourceAndPreviewProps } from 'merchant/views/Reconciliations/AiIngestion/types';

const UploadSourceAndPreview: React.FC<UploadSourceAndPreviewProps> = ({
  sourcesData,
  processName,
  isEditingProcessName,
  setSourcesData,
  showNotification,
  setAiIngestionStages,
  setIsEditingProcessName,
}) => {
  const {
    mutate: getPreSignedUrlMutation,
    data: preSignedUrlData,
    isSuccess: isSuccessForPreSignedUrl,
    isError: isErrorForPreSignedUrl,
  } = useMutation({
    mutationFn: ({ sourceId }: { sourceId: string }) => getPreSignedUrl({ sourceId }),
  });

  const {
    mutate: uploadFileMutation,
    data: uploadFileData,
    isError: isErrorForUploadFile,
    isSuccess: isSuccessForUploadFile,
  } = useMutation({
    mutationFn: ({ s3Url, file, sourceId }: { s3Url: string; file: any; sourceId: string }) =>
      uploadFileToPresignedUrl({ s3Url, file, sourceId }),
  });

  const handleSourceNameChange = throttle(
    ({ value, id }: { value: string | undefined; id: string }) => {
      setSourcesData((prevSource) => {
        const sourceIndex = prevSource.findIndex((source) => source.id === id);
        if (sourceIndex < 0) {
          return prevSource;
        }
        if (prevSource[sourceIndex].name !== value) {
          const updatedSource = [...prevSource];
          updatedSource[sourceIndex] = { ...updatedSource[sourceIndex], name: value || '' };
          return updatedSource;
        }
        return prevSource;
      });
    },
    500,
  );

  const handleAddFile = ({ file, id }: { file: File; id: string }) => {
    const allowedFileTypes = ['.txt', '.csv', '.xls', '.xlsx'];
    const allowedFileSize = 5 * 1024 * 1024;
    const fileExtension = file.name.split('.').pop()?.toLowerCase();
    if (!fileExtension || !allowedFileTypes.includes(`.${fileExtension}`)) {
      showNotification({
        type: 'error',
        message: 'File type not allowed. Allowed types are .txt, .csv, .xls, .xlsx',
      });
      return;
    }
    if (file.size > allowedFileSize) {
      showNotification({ type: 'error', message: 'File size exceeds the limit of 5MB' });
      return;
    }
    const sourceIndex = sourcesData.findIndex((source) => source.id === id);
    setSourcesData((prevSource) => {
      const updatedSource = [...prevSource];

      if (sourceIndex < 0) {
        return updatedSource;
      }
      updatedSource[sourceIndex].fileData = file;
      return updatedSource;
    });
    if (sourcesData[sourceIndex] && sourcesData[sourceIndex].fileUploadUrl) {
      uploadFileMutation({
        s3Url: sourcesData[sourceIndex].fileUploadUrl || '',
        file,
        sourceId: sourcesData[sourceIndex].id,
      });
    }
  };

  const handleDeleteFile = ({ id }: { id: string }) => {
    setSourcesData((prevSource) => {
      const updatedSource = [...prevSource];
      const sourceIndex = updatedSource.findIndex((source) => source.id === id);
      if (sourceIndex < 0) {
        return updatedSource;
      }
      updatedSource[sourceIndex].fileData = null;
      updatedSource[sourceIndex].isUploaded = false;
      return updatedSource;
    });
  };

  const addSourceFileUpload = () => {
    if (sourcesData.length >= 2) {
      showNotification({ type: 'error', message: 'You can upload only 2 source files' });
      return;
    }
    setSourcesData((prevSources) => {
      const updatedSources = [...prevSources];
      const lastIndex = updatedSources.length;
      const newSource = {
        id: uuid(),
        name: `Source ${String.fromCharCode(65 + lastIndex)}`,
        fileUploadUrl: null,
        fileUploadPath: null,
        fileData: null,
        isUploaded: false,
      };
      updatedSources.push(newSource);
      return updatedSources;
    });
  };

  const deleteSourceFileUpload = ({ id }: { id: string }) => {
    setSourcesData((prevSources) => {
      const updatedSources = prevSources.filter((source) => source.id !== id);
      return updatedSources;
    });
  };

  const goToMapping = () => {
    if (sourcesData.length < 2) {
      showNotification({
        type: 'error',
        message: 'Alteast 2 source files are required to proceed to mapping',
      });
      return;
    }

    if (!processName) {
      showNotification({
        type: 'error',
        message: 'Please enter the process name.',
      });

      setIsEditingProcessName(true);
      return;
    }

    if (isEditingProcessName) {
      showNotification({
        type: 'error',
        message:
          'Kindly save the process name first before proceeding to the mapping configuration.',
      });
      return;
    }

    setAiIngestionStages((prevStages) => ({
      ...prevStages,
      'Add Sources': true,
    }));
  };

  useEffect(() => {
    setSourcesData([
      {
        id: uuid(),
        name: 'Source A',
        fileUploadUrl: null,
        fileUploadPath: null,
        fileData: null,
        isUploaded: false,
      },
    ]);
  }, []);

  useEffect(() => {
    if (sourcesData.length > 0) {
      const lastSource = sourcesData[sourcesData.length - 1];
      if (lastSource.id) {
        getPreSignedUrlMutation({ sourceId: lastSource.id });
      }
    }
  }, [sourcesData.length]);

  useEffect(() => {
    if (isSuccessForPreSignedUrl) {
      const sourceId = preSignedUrlData.data.source_id;
      const sourceIndex = sourcesData.findIndex((source) => source.id === sourceId);
      setSourcesData((prevSource) => {
        const updatedSource = [...prevSource];
        if (sourceIndex < 0) {
          return updatedSource;
        }
        updatedSource[sourceIndex] = {
          ...updatedSource[sourceIndex],
          fileUploadUrl: preSignedUrlData.data.upload_url,
          fileUploadPath: preSignedUrlData.data.upload_path,
        };
        return updatedSource;
      });
    }
  }, [isSuccessForPreSignedUrl]);

  useEffect(() => {
    if (isErrorForPreSignedUrl) {
      showNotification({
        type: 'error',
        message: 'Something went wrong while fetching the pre-signed URL',
      });
    }
  }, [isErrorForPreSignedUrl]);

  useEffect(() => {
    if (isSuccessForUploadFile) {
      const sourceId = uploadFileData.data.source_id;
      if (sourceId) {
        const sourceIndex = sourcesData.findIndex((source) => source.id === sourceId);
        setSourcesData((prevSource) => {
          const updatedSource = [...prevSource];
          if (sourceIndex < 0) {
            return updatedSource;
          }
          updatedSource[sourceIndex] = {
            ...updatedSource[sourceIndex],
            isUploaded: true,
          };
          return updatedSource;
        });
      }
    }
  }, [isSuccessForUploadFile]);

  useEffect(() => {
    if (isErrorForUploadFile) {
      showNotification({
        type: 'error',
        message: 'Something went wrong while uploading the file',
      });
    }
  }, [isErrorForUploadFile]);

  return (
    <Box display="flex" flexDirection="column" gap="spacing.7" paddingY="spacing.6">
      <Box display="flex" justifyContent="space-between" alignItems="end">
        <Heading weight="semibold" size="medium" color="surface.text.gray.normal">
          Upload your source file
        </Heading>
        <Box display="flex" justifyContent="" alignItems="end" gap="spacing.3">
          <Button
            color="primary"
            variant="secondary"
            size="medium"
            icon={PlusIcon}
            iconPosition="left"
            onClick={() => addSourceFileUpload()}
          >
            Add Source
          </Button>
          <Button
            color="primary"
            variant="primary"
            size="medium"
            icon={ArrowRightIcon}
            iconPosition="right"
            isDisabled={
              !(sourcesData.length >= 2 && sourcesData.every((source) => source.isUploaded))
            }
            onClick={() => goToMapping()}
          >
            Next
          </Button>
        </Box>
      </Box>
      <Box
        display="grid"
        gridTemplateColumns={sourcesData.length > 0 ? 'repeat(2, 1fr)' : 'repeat(1, 1fr )'}
        gap="spacing.4"
      >
        {sourcesData.length ? (
          sourcesData.map((source) => (
            <Box
              key={source.id}
              display="flex"
              flexDirection="column"
              gap="spacing.4"
              backgroundColor="surface.background.gray.moderate"
              padding="spacing.7"
              borderRadius="large"
            >
              <Box
                display="grid"
                gridTemplateColumns="1.8fr 0.2fr"
                // justifyContent="space-between"
                alignItems="center"
                gap="spacing.1"
              >
                <TextInput
                  label="Add source name"
                  helpText="You can change it later in the reconciliation process options"
                  size="medium"
                  value={source.name}
                  onChange={({ value }) => handleSourceNameChange({ value, id: source.id })}
                  isRequired={true}
                  necessityIndicator="required"
                />
                <IconButton
                  icon={() => <TrashIcon color="feedback.icon.notice.intense" />}
                  size="medium"
                  onClick={() => deleteSourceFileUpload({ id: source.id })}
                  accessibilityLabel="Delete source"
                />
              </Box>
              <Text weight="regular" color="surface.text.gray.muted">
                Select from existing formats or upload your own file(s)
              </Text>
              <Divider dividerStyle="solid" thickness="thick" />
              <FileUpload
                uploadType="single"
                label="Upload source file"
                helpText="You can upload a single file in .txt, .csv, .xls, or .xlsx format, with a maximum size limit of 5MB."
                maxCount={1}
                maxSize={5 * 1024 * 1024}
                accept=".txt, .csv, .xls, .xlsx"
                isRequired
                necessityIndicator="required"
                onChange={({ fileList }) => handleAddFile({ file: fileList[0], id: source.id })}
                onPreview={() => {}}
                onRemove={() => handleDeleteFile({ id: source.id })}
                onDrop={({ fileList }) => handleAddFile({ file: fileList[0], id: source.id })}
                isDisabled={source.fileUploadUrl === null && source.fileUploadPath === null}
              />
            </Box>
          ))
        ) : (
          <Alert
            color="information"
            title="Alteast 2 source files are required to proceed to mapping. ."
            description="Click on 'Add Source' to upload a source file."
            emphasis="subtle"
            isDismissible={false}
            isFullWidth={true}
          />
        )}
      </Box>
    </Box>
  );
};

export default compose(connect(null, { showNotification }))(UploadSourceAndPreview);

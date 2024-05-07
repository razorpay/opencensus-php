import React, { useRef, useState } from 'react';
import {
  Card,
  CardBody,
  Heading,
  Text,
  Divider,
  Box,
  List,
  ListItem,
  UploadIcon,
  Link,
  Spinner,
  Button,
  ArrowRightIcon,
  ArrowLeftIcon,
} from '@razorpay/blade/components';
import { useLocation, useNavigate } from 'react-router-dom';

import { merchantFetch } from 'merchant/utils/ajax';

import { FileUploadStatus } from './../commonComponents';
import EnterPasswordModal from './EnterPasswordModal';
import ReconInitiatedSuccess from './ReconInitiatedSuccess';
import { extractZip, isExcelEncrypted } from './utils';

const allowedExtensions = ['csv', 'xls', 'xlsx', 'zip', 'txt'];

export default function NewReconciliationRun() {
  const location = useLocation();
  const navigate = useNavigate();
  const inputRef = useRef([]);
  const [isSuccess, setIsSuccess] = useState(false);

  const processDetails = location.state;

  const fileConfigs = processDetails?.merchant_sources;
  const filteredFileConfigs = fileConfigs
    ? fileConfigs.filter((config) => config.allow_upload)
    : [];

  const [filesStatus, setFilesStatus] = useState(new Array(filteredFileConfigs.length).fill({}));
  const [activePassFile, setActivePassFile] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  const updateFileStatusArray = (data, index) => {
    setFilesStatus((prev) => prev.map((item, i) => (i === index ? { ...item, ...data } : item)));
  };

  const uploadValidate = async (file, sourceId, index, password) => {
    updateFileStatusArray({ isUploading: true, fileName: file.name }, index);
    const raw = {
      merchant_source_id: sourceId,
      merchant_process_id: processDetails.id,
      file_name: file.name,
    };
    if (password) {
      raw.password = password;
    }
    let res = await merchantFetch({
      url: `recon-saas/file_detail/get_upload_url`,
      mode: 'live',
      method: 'POST',
      data: raw,
    });
    res = res.data;
    const s3url = res.upload_url;

    // Upload file to s3
    const myHeaders = new Headers();
    myHeaders.append('Content-Type', `binary/octet-stream`);
    const requestOptions = {
      method: 'PUT',
      headers: { 'Content-Type': `binary/octet-stream` },
      body: file,
    };
    const s3UploadRes = await fetch(s3url, requestOptions);
    if (s3UploadRes.status === 200) {
      updateFileStatusArray({ isUploading: false, isUploaded: true, uploadId: res.id }, index);
    } else {
      updateFileStatusArray({ isUploading: false, error: 'Some error occured' }, index);
    }
  };

  const triggerFileUpload = async (file, sourceId, fileIndex) => {
    const ext = file.name.split('.').pop();
    if (!allowedExtensions.includes(ext)) {
      return;
    }
    let isPassProtected = false;
    if (ext === 'zip') {
      const isExtractSuccessful = await extractZip(file);
      if (!isExtractSuccessful) {
        isPassProtected = true;
      }
    } else if (ext === 'xlsx' || ext === 'xls') {
      const isEncrypted = await isExcelEncrypted(file);
      if (isEncrypted) {
        isPassProtected = true;
      }
    }
    if (isPassProtected) {
      setActivePassFile({ file, ext, sourceId, fileIndex, fileName: file.name });
      return;
    }
    uploadValidate(file, sourceId, fileIndex);
  };

  const handleChange = (event, fileIndex) => {
    event.preventDefault();
    const file = event.target.files[0];
    const sourceId = event.target.getAttribute('data-sourceid');
    const ext = file.name.split('.').pop();
    if (!allowedExtensions.includes(ext.toLowerCase())) {
      return;
    }
    triggerFileUpload(event.target.files[0], sourceId, fileIndex);
  };

  const handleDrop = (e, sourceId, fileIndex) => {
    e.preventDefault();
    e.stopPropagation();
    const files = e.dataTransfer.files;
    const file = files[0];
    triggerFileUpload(file, sourceId, fileIndex);
  };

  const handlePassConfirm = async () => {
    if (activePassFile?.ext === 'zip') {
      // try to unzip with provided password
      const isExtractSuccessful = await extractZip(activePassFile?.file, activePassFile?.password);
      if (!isExtractSuccessful) {
        setActivePassFile((prev) => ({ ...prev, error: 'Incorrect password' }));
        return;
      }
    }
    setActivePassFile(null);
    const { file, sourceId, fileIndex, password } = activePassFile;
    uploadValidate(file, sourceId, fileIndex, password);
  };

  const markSuccess = (uploadId) =>
    merchantFetch({
      url: `recon-saas/file_detail/upload_success`,
      mode: 'live',
      method: 'PATCH',
      data: { id: uploadId },
    });

  const startReconRun = async () => {
    setIsLoading(true);
    const filesToMarkSuccess = filesStatus.filter((status) => status.isUploaded);
    const res = await Promise.all(filesToMarkSuccess.map((file) => markSuccess(file.uploadId)));
    if (res.every((status) => status.status_code === 200)) {
      setIsSuccess(true);
    }
    setIsLoading(false);
  };

  return (
    <Box paddingTop="spacing.1">
      <Box />
      {isSuccess ? (
        <ReconInitiatedSuccess />
      ) : (
        <Card margin="spacing.6">
          <CardBody>
            <Heading size="large">New Reconciliation</Heading>
            <Box marginBottom="spacing.6" />
            <Divider marginBottom="spacing.6" />
            <Box width="480px">
              <Box display="flex" justifyContent="space-between">
                <Box>
                  <Text marginBottom="spacing.6" size="large">
                    Add transaction and bank records
                  </Text>
                </Box>
              </Box>
              <Text size="small" color="surface.text.gray.muted">
                You’ll need to provide the following records:
              </Text>
              <List size="small">
                {filteredFileConfigs.map((config) => (
                  <ListItem key={config.name}>{config.name}</ListItem>
                ))}
              </List>

              {filteredFileConfigs.map((config, index) => (
                <Box
                  key={config.id}
                  marginTop="spacing.6"
                  textAlign="center"
                  backgroundColor="surface.background.primary.subtle"
                  padding="spacing.6"
                  borderRadius="medium"
                  onDrop={(e) => handleDrop(e, config.id, index)}
                  onDragOver={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                  }}
                >
                  {filesStatus[index]?.isUploading ? (
                    <Box display="flex" justifyContent="center">
                      <Spinner />
                    </Box>
                  ) : (
                    <>
                      <UploadIcon color="interactive.icon.primary.normal" />
                      <Text size="large">
                        <Link onClick={() => inputRef.current[index].click()}>Browse</Link> or drag
                        & drop file here
                      </Text>
                      <Text size="small" color="surface.text.gray.muted">
                        {config.name}
                      </Text>
                      <input
                        type="file"
                        class="hide"
                        ref={(element) => (inputRef.current[index] = element)}
                        onChange={(e) => handleChange(e, index)}
                        accept={allowedExtensions.join(', ')}
                        data-sourceid={config.id}
                      />
                    </>
                  )}
                </Box>
              ))}

              <Box marginTop="spacing.4">
                {filesStatus.map((fileData, index) => (
                  <FileUploadStatus key={`${fileData?.fileName}-${index}`} fileData={fileData} />
                ))}
              </Box>
              <EnterPasswordModal
                activePassFile={activePassFile}
                setActivePassFile={setActivePassFile}
                handleSubmit={handlePassConfirm}
              />
              <Box
                display="flex"
                justifyContent="flex-end"
                alignItems="center"
                marginTop="spacing.6"
              >
                <Button
                  marginRight="spacing.4"
                  variant="secondary"
                  icon={ArrowLeftIcon}
                  onClick={() => navigate(-1)}
                >
                  Back
                </Button>
                <Button
                  isDisabled={!filesStatus.some((status) => status?.isUploaded)}
                  onClick={startReconRun}
                  icon={ArrowRightIcon}
                  iconPosition="right"
                  isLoading={isLoading}
                >
                  Start New Reconciliation
                </Button>
              </Box>
            </Box>
          </CardBody>
        </Card>
      )}
    </Box>
  );
}

import React, { useEffect, useRef, useState } from 'react';
import {
  Card,
  CardBody,
  Heading,
  Text,
  Divider,
  Box,
  Button,
  List,
  ListItem,
  UploadIcon,
  Link,
  Modal,
  ModalHeader,
  ModalBody,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  ModalFooter,
  InfoIcon,
  Spinner,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { compose } from 'redux';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { merchantFetch } from 'merchant/utils/ajax';
import { FileUploadStatus } from 'merchant/views/Reconciliations/commonComponents';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { showNotification as showNotificationProp } from 'merchant_common/reducers/notifications';

import EnterPasswordModal from './EnterPasswordModal';
import { extractZip, isExcelEncrypted, getReadableFromKey } from './utils';

const allowedExtensions = ['csv', 'xls', 'xlsx', 'zip', 'txt'];

function NewReconciliation({ fileConfigs, reconType, handleCtaClick, showNotification }) {
  const inputRefs = useRef([]);
  const [isOpen, setIsOpen] = useState(null);
  const filteredFileConfigs = fileConfigs.filter((config) => config.show_upload);
  const [filesUploadData, setFilesUploadData] = useState(
    new Array(filteredFileConfigs.length).fill({ isUploading: false, isUploaded: false }),
  );
  const [activePassFile, setActivePassFile] = useState(null);

  const updateFileUploadData = (data, index) => {
    setFilesUploadData((prev) =>
      prev.map((item, i) => (i === index ? { ...item, ...data } : item)),
    );
  };
  const navigate = useNavigate();

  const updateSchema = async (sourceMappings) => {
    const obj = {
      source_mappings: sourceMappings,
      master_process_id: reconType.master_process_id,
    };
    const res = await merchantFetch({
      url: `recon-saas/merchant/update_schema`,
      mode: 'live',
      method: 'POST',
      data: obj,
    });
    if (res?.status_code === 200) {
      handleCtaClick();
      analyticsTrackWithUserInfo({
        screen: ReconScreens.NewConfiguration,
        objectName: 'recon config creation',
        actionName: 'success',
        properties: {
          masterProcessId: reconType.master_process_id,
        },
      });
    } else {
      showNotification({
        type: 'error',
        message: 'An error occurred while creating config.',
      });
    }
  };

  if (filteredFileConfigs.length === 0) {
    updateSchema([]);
  }

  useEffect(() => {
    if (filesUploadData.length > 0 && filesUploadData.every((file) => file.isUploaded)) {
      const sourceMappings = filesUploadData.map((file) => {
        return {
          master_source_id: file.masterSourceId,
          mapping: file.autoMatch ? file.mappingData.mapping : file.userEditedMapping,
        };
      });
      updateSchema(sourceMappings);
    }
  }, [filesUploadData]);

  const startPolling = async (fileId, masterSourceId, masterProcessId, fileIndex, tries) => {
    const res = await merchantFetch({
      url: `recon-saas/file_detail/sample/polling/${fileId}`,
      mode: 'live',
      method: 'get',
    });
    const result = res.data;
    if (result.status !== 'FILE_NOT_VALIDATED') {
      updateFileUploadData(
        {
          mappingData: result,
          userEditedMapping: result.mapping,
          masterSourceId,
          masterProcessId,
        },
        fileIndex,
      );
      if (result.status === 'MATCHED') {
        updateFileUploadData({ isUploaded: true, autoMatch: true, isUploading: false }, fileIndex);
      } else {
        setIsOpen(fileIndex);
      }
      return;
    }
    if (tries > 0) {
      setTimeout(() => {
        startPolling(fileId, masterSourceId, masterProcessId, fileIndex, tries - 1);
      }, 4000);
    }
  };

  const uploadValidate = async (file, fileMeta, fileIndex, password) => {
    updateFileUploadData({ isUploading: true, fileName: file.name }, fileIndex);
    const raw = {
      master_source_id: fileMeta.masterSourceId,
      master_process_id: reconType.master_process_id,
      name: file.name,
    };
    if (password) {
      raw.password = password;
    }
    let res = await merchantFetch({
      url: `recon-saas/file_detail/sample`,
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
      let markSuccess = await merchantFetch({
        url: `recon-saas/file_detail/sample/upload_success/${res.id}`,
        mode: 'live',
        method: 'get',
      });
      markSuccess = markSuccess.data;
      if (markSuccess.message === 'success') {
        startPolling(res.id, fileMeta.masterSourceId, reconType.master_process_id, fileIndex, 20);
      } else {
        updateFileUploadData({ isUploading: false, error: true }, fileIndex);
      }
    }
  };

  const triggerFileUpload = async (file, fileMeta, fileIndex) => {
    const ext = file?.name?.split('.').pop();
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
      setActivePassFile({ file, ext, fileMeta, fileIndex, fileName: file.name });
      return;
    }
    uploadValidate(file, fileMeta, fileIndex);
  };

  const handleChange = (event) => {
    event.preventDefault();
    const { target } = event;
    const masterSourceId = target.getAttribute('data-mastersourceid');
    const sourceName = target.getAttribute('data-sourcename');
    const fileIndex = Number(target.getAttribute('data-fileindex'));
    const fileMeta = {
      masterSourceId,
      sourceName,
    };
    triggerFileUpload(target.files[0], fileMeta, fileIndex);
  };

  const handleDrop = (e, masterSourceId, sourceName, fileIndex) => {
    e.preventDefault();
    e.stopPropagation();
    const files = e.dataTransfer.files;
    const file = files[0];
    const fileMeta = {
      masterSourceId,
      sourceName,
    };
    triggerFileUpload(file, fileMeta, fileIndex);
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
    const { file, fileMeta, fileIndex, password } = activePassFile;
    uploadValidate(file, fileMeta, fileIndex, password);
  };

  return (
    <Card margin="spacing.6">
      <CardBody>
        <Heading size="large">Upload Sample</Heading>
        <Box marginBottom="spacing.6" />
        <Divider marginBottom="spacing.6" />
        <Box width="480px">
          <Box display="flex" justifyContent="space-between" marginBottom="spacing.4">
            <Box>
              <Text marginBottom="spacing.2" size="large">
                Add other records
              </Text>
            </Box>
          </Box>
          <List size="small">
            {filteredFileConfigs.map((config) => (
              <ListItem key={config.source_name}>{getReadableFromKey(config.source_name)}</ListItem>
            ))}
          </List>
          {filteredFileConfigs.map((config, index) => (
            <Box
              key={config.master_source_id}
              marginTop="spacing.6"
              textAlign="center"
              backgroundColor="surface.background.primary.subtle"
              padding="spacing.6"
              borderRadius="medium"
              onDrop={(e) => handleDrop(e, config.master_source_id, config.source_name, index)}
              onDragOver={(e) => {
                e.preventDefault();
                e.stopPropagation();
              }}
            >
              {filesUploadData[index]?.isUploading ? (
                <Box display="flex" justifyContent="center">
                  <Spinner />
                </Box>
              ) : (
                <>
                  <UploadIcon color="interactive.icon.primary.normal" />
                  <Text weight="regular" size="large">
                    <Link href="#" onClick={() => inputRefs.current[index].click()}>
                      Browse
                    </Link>{' '}
                    or drag & drop file here
                  </Text>
                  <Text size="small" color="surface.text.gray.muted">
                    {getReadableFromKey(config.source_name)}
                  </Text>
                  <input
                    type="file"
                    className="hide"
                    ref={(element) => (inputRefs.current[index] = element)}
                    onChange={handleChange}
                    accept={allowedExtensions.join(', ')}
                    data-mastersourceid={config.master_source_id}
                    data-fileindex={index}
                    data-sourcename={config.source_name}
                  />
                </>
              )}
            </Box>
          ))}
          <Box marginTop="spacing.4">
            {filesUploadData.map((fileData, index) => (
              <FileUploadStatus key={`${fileData?.fileName}-${index}`} fileData={fileData} />
            ))}
          </Box>
          <Box display="flex" justifyContent="flex-end" marginTop="spacing.6">
            <Button
              variant="secondary"
              marginRight="spacing.4"
              onClick={() => navigate('/reconciliations/create-config/3')}
            >
              Back
            </Button>
          </Box>
          <Modal isOpen={isOpen !== null} onDismiss={() => setIsOpen(null)} size="small">
            <ModalHeader
              title="Please map columns names to required data"
              subtitle="Unable to match columns in your transactions record"
            />
            <ModalBody padding="spacing.0">
              {filesUploadData[isOpen]?.mappingData?.required_schema ? (
                <Box padding="spacing.6" backgroundColor="surface.background.gray.subtle">
                  <Box display="flex" alignItems="center" justifyContent="space-between">
                    <Box>
                      <Text weight="semibold">Required Data</Text>
                    </Box>
                    <Box width="200px">
                      <Text weight="semibold">Column Name</Text>
                    </Box>
                  </Box>
                  {filesUploadData[isOpen]?.mappingData?.required_schema?.map((field) => (
                    <Box
                      key={field.display_name || field.name}
                      display="flex"
                      alignItems="center"
                      justifyContent="space-between"
                    >
                      <Box display="flex" alignItems="center">
                        {field.display_name || field.name}
                        <InfoIcon
                          marginLeft="spacing.2"
                          color="interactive.icon.staticBlack.default"
                        />
                      </Box>
                      <Box width="200px">
                        <Dropdown selectionType="single">
                          <SelectInput
                            placeholder="Select Column"
                            name={field.name}
                            onChange={({ name, values }) => {
                              const data = filesUploadData[isOpen]?.userEditedMapping || {};
                              updateFileUploadData(
                                { userEditedMapping: { ...data, [name]: values[0] } },
                                isOpen,
                              );
                            }}
                            value={filesUploadData[isOpen]?.userEditedMapping?.[field.name] || ''}
                          />
                          <DropdownOverlay>
                            <ActionList>
                              {filesUploadData[isOpen]?.mappingData?.file_schema?.map((value) => (
                                <ActionListItem key={value} title={value} value={value} />
                              ))}
                            </ActionList>
                          </DropdownOverlay>
                        </Dropdown>
                      </Box>
                    </Box>
                  ))}
                </Box>
              ) : null}
            </ModalBody>
            <ModalFooter>
              <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
                <Button variant="secondary" onClick={() => setIsOpen(null)}>
                  Back
                </Button>
                <Button
                  onClick={() => {
                    const index = isOpen;
                    // Marking uploaded so that useEffect can be triggered
                    updateFileUploadData({ isUploaded: true, isUploading: false }, index);
                    setIsOpen(null);
                  }}
                >
                  Proceed
                </Button>
              </Box>
            </ModalFooter>
          </Modal>
          <EnterPasswordModal
            activePassFile={activePassFile}
            setActivePassFile={setActivePassFile}
            handleSubmit={handlePassConfirm}
          />
        </Box>
      </CardBody>
    </Card>
  );
}

export default compose(
  connect(null, {
    showNotification: showNotificationProp,
  }),
)(NewReconciliation);

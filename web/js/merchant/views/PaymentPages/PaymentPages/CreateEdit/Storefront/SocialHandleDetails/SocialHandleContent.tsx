import React, { memo } from 'react';
import {
  Text,
  Box,
  IconButton,
  Button,
  ChevronRightIcon,
  Modal,
  ModalHeader,
  ModalBody,
  TextInput,
  ModalFooter,
} from '@razorpay/blade/components';
import { PLATFORM_NAMES, SOCIAL_HANDLES } from 'merchant/views/PaymentPages/PaymentPages/constants';
import {
  SocialHandle,
  SocialHandleModalProps,
  AddDetailsFooterButtonsProps,
  AddDetailsContentProps,
} from '../types';
import UploadCustomLogo from './UploadCustomLogo';

export const AddDetailsFooterButtons = memo(
  ({
    onCancel,
    onSave,
    inputVal,
    isSaveDisabled = false,
    isSaving = false,
    uploadedFile,
  }: AddDetailsFooterButtonsProps) => {
    return (
      <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
        <Button
          variant="tertiary"
          onClick={onCancel}
          accessibilityLabel="cancel social handle"
        >
          Cancel
        </Button>
        <Button
          isDisabled={isSaveDisabled}
          variant="primary"
          onClick={() => onSave(inputVal, uploadedFile)}
          isLoading={isSaving}
          accessibilityLabel="save social handle"
        >
          Confirm
        </Button>
      </Box>
    );
  },
);

export const AddDetailsContent: React.FC<AddDetailsContentProps> = ({
  selectedHandle,
  uploadedFile,
  setUploadedFile,
  uploadedLogo,
  setUploadedLogo,
  inputVal,
  handleInputChange,
  isMobile,
}) => {
  const isCustomedWebsite = selectedHandle?.name === PLATFORM_NAMES.CUSTOM;

  return (
    <Box
      display="flex"
      gap="spacing.5"
      flexDirection="column"
      height={isMobile ? (isCustomedWebsite ? '280px' : '120px') : 'auto'}
    >
      {isCustomedWebsite && (
        <UploadCustomLogo
          uploadedFile={uploadedFile}
          setUploadedFile={setUploadedFile}
          uploadedLogo={uploadedLogo}
          setUploadedLogo={setUploadedLogo}
        />
      )}

      <TextInput
        label={isCustomedWebsite ? 'Add a link' : ''}
        placeholder={selectedHandle.inputPlaceholder}
        type="text"
        onChange={handleInputChange}
        value={inputVal}
        name={selectedHandle.name}
        testID={`social-handle-input-${selectedHandle.name}`}
        autoFocus={true}
      />
    </Box>
  );
};

export const SocialHandleItem = memo(
  ({ item, onSelect }: { item: SocialHandle; onSelect: (handle: SocialHandle) => void }) => (
    <Box
      testID={`social-handle-item-${item.name}`}
      borderWidth="thin"
      borderColor="surface.border.gray.muted"
      borderRadius="medium"
      display="flex"
      justifyContent="space-between"
      alignItems="center"
      height="58px"
      maxWidth="352px"
      paddingX="spacing.5"
      paddingY="spacing.3"
    >
      <Box display="flex" gap="spacing.2" alignItems="center">
        <img src={item.src} alt={item.label} width={24} height={24} />
        <Text size="large" variant="body" weight="semibold">
          {item.label}
        </Text>
      </Box>
      <IconButton
        onClick={() => onSelect(item)}
        accessibilityLabel={`select ${item.name}`}
        size="large"
        icon={ChevronRightIcon}
      />
    </Box>
  ),
);

export const SocialHandleModal = memo(
  ({
    inputVal,
    saveSocialHandle,
    selectedHandle,
    updateInputValue,
    cancelSocialHandleOperation,
    uploadedFile,
    setUploadedFile,
    isSaving,
    uploadedLogo,
    setUploadedLogo,
    isSaveDisabled,
    setSelectedHandle,
  }: SocialHandleModalProps) => {
    return (
      <Modal isOpen={true} onDismiss={cancelSocialHandleOperation} size="medium">
        <ModalHeader
          title={selectedHandle ? selectedHandle.inputLabel : 'Select a social account'}
        />
        <ModalBody>
          {selectedHandle ? (
            <AddDetailsContent
              selectedHandle={selectedHandle}
              uploadedFile={uploadedFile}
              setUploadedFile={setUploadedFile}
              uploadedLogo={uploadedLogo}
              setUploadedLogo={setUploadedLogo}
              inputVal={inputVal}
              handleInputChange={updateInputValue}
              isMobile={false}
            />
          ) : (
            <Box display="grid" gridTemplateColumns="repeat(2, 1fr)" gap="spacing.7">
              {SOCIAL_HANDLES.map((item) => (
                <SocialHandleItem
                  key={item.name}
                  item={item}
                  onSelect={(item) => {
                    console.log('====CLCLLCLCLC');
                    setSelectedHandle(item);
                  }}
                />
              ))}
            </Box>
          )}
        </ModalBody>

        {selectedHandle && (
          <ModalFooter>
            <AddDetailsFooterButtons
              onCancel={cancelSocialHandleOperation}
              onSave={saveSocialHandle}
              inputVal={inputVal}
              isSaving={isSaving}
              isSaveDisabled={isSaveDisabled}
              uploadedFile={uploadedFile}
            />
          </ModalFooter>
        )}
      </Modal>
    );
  },
);

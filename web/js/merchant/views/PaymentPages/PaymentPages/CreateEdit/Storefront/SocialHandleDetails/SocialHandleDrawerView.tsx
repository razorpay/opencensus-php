import React, { useEffect, useState, useCallback, memo } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  CloseIcon,
  Text,
  Box,
  IconButton,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
  Button,
  ChevronRightIcon,
  Modal,
  ModalHeader,
  ModalBody,
  TextInput,
  ModalFooter,
  BottomSheetFooter,
  FileUpload,
} from '@razorpay/blade/components';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import SocialHandleList from './SocialHandleList';
import { SOCIAL_HANDLES } from 'merchant/views/PaymentPages/PaymentPages/constants';
import {
  SocialHandleDrawerViewProps,
  SocialHandle,
  SocialHandleModalProps,
  ModalFooterButtonsProps,
} from '../types';
import LineItems from '../LineItems';
import UploadCustomLogo from './UploadCustomLogo';
import { zIndicesMap } from '@libs/web-nexus/common/constant';

const ModalFooterButtons = memo(
  ({ onCancel, onSave, isSaveDisabled = false, isSaving = false }: ModalFooterButtonsProps) => {
    return (
      <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
        <Button variant="tertiary" onClick={onCancel}>
          Cancel
        </Button>
        <Button isDisabled={isSaveDisabled} variant="primary" onClick={onSave} isLoading={isSaving}>
          Confirm
        </Button>
      </Box>
    );
  },
);

const SocialHandleItem = memo(
  ({ item, onSelect }: { item: SocialHandle; onSelect: (handle: SocialHandle) => void }) => (
    <Box
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
        accessibilityLabel={`select-${item.name}`}
        size="large"
        icon={ChevronRightIcon}
      />
    </Box>
  ),
);

const SocialHandleItemMobile = memo(
  ({ item, onSelect }: { item: SocialHandle; onSelect: (handle: SocialHandle) => void }) => (
    <Box
      borderRadius="medium"
      display="flex"
      justifyContent="space-between"
      alignItems="center"
      height="44px"
    >
      <Box display="flex" gap="spacing.2" alignItems="center">
        <img src={item.src} alt={item.label} width={24} height={24} />
        <Text size="large" variant="body" weight="semibold">
          {item.label}
        </Text>
      </Box>
      <IconButton
        onClick={() => onSelect(item)}
        accessibilityLabel={`select-${item.name}`}
        size="large"
        icon={ChevronRightIcon}
      />
    </Box>
  ),
);

const RenderSelectSocialHandlesDesktop = memo(
  ({
    inputVal,
    saveSocialHandle,
    selectedHandle,
    selectSocialHandle,
    updateInputValue,
    cancelSocialHandleOperation,
    uploadedFile,
    setUploadedFile,
    isSaving,
    uploadedLogo,
    setUploadedLogo,
  }: SocialHandleModalProps) => {
    return (
      <Modal isOpen={true} onDismiss={cancelSocialHandleOperation} size="medium">
        <ModalHeader
          title={selectedHandle ? selectedHandle.inputLabel : 'Select a social account'}
        />
        <ModalBody>
          {selectedHandle ? (
            <Box display="flex" gap="spacing.5" flexDirection="column">
              {selectedHandle.name === 'custom' && (
                <UploadCustomLogo
                  uploadedFile={uploadedFile}
                  setUploadedFile={setUploadedFile}
                  uploadedLogo={uploadedLogo}
                  setUploadedLogo={setUploadedLogo}
                />
              )}
              <TextInput
                label={selectedHandle.name === 'custom' ? 'Add a link' : ''}
                placeholder={selectedHandle.inputPlaceholder}
                type="text"
                onChange={updateInputValue}
                value={inputVal}
                name={selectedHandle.name}
              />
            </Box>
          ) : (
            <Box display="grid" gridTemplateColumns="repeat(2, 1fr)" gap="spacing.7">
              {SOCIAL_HANDLES.map((item) => (
                <SocialHandleItem
                  key={item.name}
                  item={item}
                  onSelect={(item) => selectSocialHandle(item)}
                />
              ))}
            </Box>
          )}
        </ModalBody>

        {selectedHandle && (
          <ModalFooter>
            <ModalFooterButtons
              onCancel={cancelSocialHandleOperation}
              onSave={saveSocialHandle}
              isSaving={isSaving}
            />
          </ModalFooter>
        )}
      </Modal>
    );
  },
);

const SocialHandleDrawerView: React.FC<SocialHandleDrawerViewProps> = ({
  isMobile,
  handleClose,
  showSelectModal,
  inputVal,
  selectedHandle,
  openDeleteModal,
  socialHandles,
  hasReachedHandleLimit,
  updateInputValue,
  saveSocialHandle,
  cancelDeleteSocialHandle,
  editSocialHandle,
  showDeleteConfirmation,
  confirmDeleteSocialHandle,
  cancelSocialHandleOperation,
  openSocialHandleSelector,
  reorderSocialHandle,
  selectSocialHandle,
  uploadedFile,
  setUploadedFile,
  isSaving,
  uploadedLogo,
  setUploadedLogo,
}) => {
  return (
    <>
      {isMobile ? (
        <BottomSheet
          isOpen={true}
          onDismiss={selectedHandle || showSelectModal ? cancelSocialHandleOperation : handleClose}
          zIndex={zIndicesMap.modal}
          snapPoints={[0.9, 0.9, 0.9]}
        >
          <BottomSheetHeader
            title={selectedHandle ? selectedHandle.inputLabel : 'Select a social account'}
          />
          <BottomSheetBody>
            {selectedHandle ? (
              <Box height="120px">
                <TextInput
                  label=""
                  placeholder={selectedHandle.inputPlaceholder}
                  type="text"
                  onChange={updateInputValue}
                  value={inputVal}
                  name={selectedHandle.name}
                />
              </Box>
            ) : showSelectModal ? (
              <>
                {SOCIAL_HANDLES.map((item) => (
                  <SocialHandleItemMobile
                    key={item.name}
                    item={item}
                    onSelect={selectSocialHandle}
                  />
                ))}
              </>
            ) : (
              <>
                <Box
                  paddingBottom={
                    isMobile && socialHandles.length === 0 ? 'spacing.11' : 'spacing.4'
                  }
                >
                  <LineItems
                    title="Select a social account"
                    subTitle={
                      <Text size="small" color="surface.text.gray.subtle">
                        You can add a maximum of 4 social accounts
                      </Text>
                    }
                    rightChildren={
                      <Button
                        isDisabled={hasReachedHandleLimit}
                        variant="tertiary"
                        color="primary"
                        size="xsmall"
                        icon={ChevronRightIcon}
                        onClick={openSocialHandleSelector}
                      />
                    }
                  />
                </Box>
                {socialHandles.length > 0 && (
                  <SocialHandleList
                    isMobile={isMobile}
                    reorderSocialHandle={reorderSocialHandle}
                    socialHandleList={socialHandles}
                    editSocialHandle={editSocialHandle}
                    showDeleteConfirmation={showDeleteConfirmation}
                  />
                )}
              </>
            )}
          </BottomSheetBody>
          <BottomSheetFooter>
            {selectedHandle && (
              <ModalFooterButtons
                isSaving={isSaving}
                onCancel={cancelSocialHandleOperation}
                onSave={saveSocialHandle}
                isSaveDisabled={!inputVal?.length}
              />
            )}
          </BottomSheetFooter>
        </BottomSheet>
      ) : (
        <PaymentPagesDrawer
          showCloseBtn={false}
          maskClosable={false}
          onClose={handleClose}
          top="0px"
          isStorefront={true}
        >
          <Box
            display="flex"
            justifyContent="space-between"
            alignItems="center"
            marginBottom="spacing.5"
          >
            <Box display="flex" flexDirection="column" gap="spacing.1">
              <Text color="surface.text.gray.normal" size="medium" variant="body" weight="semibold">
                Social handles
              </Text>
              <Text color="surface.text.gray.subtle" size="small" variant="body" weight="regular">
                Build trust with your customers
              </Text>
            </Box>
            <IconButton
              onClick={handleClose}
              accessibilityLabel="close-icon"
              size="large"
              icon={CloseIcon}
            />
          </Box>
          <Box paddingBottom={isMobile ? 'spacing.11' : 'spacing.4'}>
            <LineItems
              title="Select a social account"
              subTitle={
                <Text size="small" color="surface.text.gray.subtle">
                  You can add a maximum of 4 social accounts
                </Text>
              }
              rightChildren={
                <Button
                  isDisabled={hasReachedHandleLimit}
                  variant="tertiary"
                  color="primary"
                  size="xsmall"
                  icon={ChevronRightIcon}
                  onClick={openSocialHandleSelector}
                />
              }
            />
          </Box>
          {socialHandles.length > 0 && (
            <SocialHandleList
              isMobile={isMobile}
              reorderSocialHandle={reorderSocialHandle}
              socialHandleList={socialHandles}
              editSocialHandle={editSocialHandle}
              showDeleteConfirmation={showDeleteConfirmation}
            />
          )}
          {showSelectModal && (
            <RenderSelectSocialHandlesDesktop
              inputVal={inputVal}
              saveSocialHandle={saveSocialHandle}
              selectedHandle={selectedHandle}
              selectSocialHandle={selectSocialHandle}
              updateInputValue={updateInputValue}
              cancelSocialHandleOperation={cancelSocialHandleOperation}
              uploadedFile={uploadedFile}
              setUploadedFile={setUploadedFile}
              isSaving={isSaving}
              uploadedLogo={uploadedLogo}
              setUploadedLogo={setUploadedLogo}
            />
          )}
        </PaymentPagesDrawer>
      )}

      {openDeleteModal &&
        (isMobile ? (
          <BottomSheet
            isOpen={true}
            onDismiss={cancelDeleteSocialHandle}
            zIndex={zIndicesMap.modal}
            snapPoints={[0.9, 0.9, 0.9]}
          >
            <BottomSheetHeader
              title="Confirm Deletion of Social handle"
              subtitle="Please confirm if you would like to proceed with deleting the social handle."
            />
            <BottomSheetFooter>
              <Box
                display="flex"
                gap="spacing.3"
                justifyContent="flex-end"
                width="100%"
                flexDirection="column"
              >
                <Button variant="tertiary" onClick={cancelDeleteSocialHandle}>
                  Cancel
                </Button>
                <Button onClick={confirmDeleteSocialHandle}>Confirm</Button>
              </Box>
              <ModalFooterButtons
                onCancel={cancelDeleteSocialHandle}
                onSave={confirmDeleteSocialHandle}
              />
            </BottomSheetFooter>
          </BottomSheet>
        ) : (
          <Modal isOpen={true} onDismiss={cancelDeleteSocialHandle} size="small">
            <ModalHeader
              title="You want to remove social handle?"
              subtitle="Your added handle will be deleted."
            />
            <ModalFooter>
              <ModalFooterButtons onCancel={cancelDeleteSocialHandle} onSave={confirmDeleteSocialHandle} />
            </ModalFooter>
          </Modal>
        ))}
    </>
  );
};

export default SocialHandleDrawerView;

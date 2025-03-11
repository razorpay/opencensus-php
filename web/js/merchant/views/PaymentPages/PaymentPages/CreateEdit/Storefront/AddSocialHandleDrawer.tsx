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
} from '@razorpay/blade/components';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import {
  addSocialHandle,
  deleteSocialHandle,
  reorderSocialHandle,
  updatedSocialHandle,
} from 'merchant/reducers/paymentPages/storefront';
import { showNotification } from 'merchant_common/reducers/notifications';
import LineItems from './LineItems';
import SocialHandleList from './SocialHandleList';
import {
  MAX_SOCIAL_HANDLE_ALLOWED,
  SOCIAL_HANDLES,
} from 'merchant/views/PaymentPages/PaymentPages/constants';
import { SocialMediaHandle, SocialMediaHandles } from 'merchant/reducers/paymentPages/types';
import {
  ModalFooterButtonsProps,
  SocialHandle,
  SocialHandleDrawerProps,
  SocialHandleModalProps,
} from './types';
import { zIndicesMap } from '@libs/web-nexus/common/constant';

const getNextPosition = (socialHandles: SocialMediaHandles): number => {
  if (socialHandles.length === 0) return 0;
  return Math.max(...socialHandles.map((handle) => handle?.position ?? 0)) + 1;
};

const ModalFooterButtons = memo(
  ({ onCancel, onSave, isSaveDisabled = false }: ModalFooterButtonsProps) => {
    return (
      <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
        <Button variant="tertiary" onClick={onCancel}>
          Cancel
        </Button>
        <Button isDisabled={isSaveDisabled} variant="primary" onClick={onSave}>
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
    onSave,
    selectedHandle,
    setSelectedHandle,
    handleInputChange,
    onCancel,
  }: SocialHandleModalProps) => {
    const handleSelect = useCallback(
      (handle: SocialHandle) => {
        setSelectedHandle(handle);
      },
      [setSelectedHandle],
    );

    return (
      <Modal isOpen={true} onDismiss={onCancel} size="medium">
        <ModalHeader
          title={selectedHandle ? selectedHandle.inputLabel : 'Select a social account'}
        />
        <ModalBody>
          {selectedHandle ? (
            <TextInput
              label=""
              placeholder={selectedHandle.inputPlaceholder}
              type="text"
              onChange={handleInputChange}
              value={inputVal}
              name={selectedHandle.name}
            />
          ) : (
            <Box display="grid" gridTemplateColumns="repeat(2, 1fr)" gap="spacing.7">
              {SOCIAL_HANDLES.map((item) => (
                <SocialHandleItem key={item.name} item={item} onSelect={handleSelect} />
              ))}
            </Box>
          )}
        </ModalBody>
        {selectedHandle && (
          <ModalFooter>
            <ModalFooterButtons
              onCancel={onCancel}
              onSave={onSave}
              isSaveDisabled={!inputVal?.length}
            />
          </ModalFooter>
        )}
      </Modal>
    );
  },
);

const SocialHandleDrawer: React.FC<SocialHandleDrawerProps> = ({
  handleClose,
  isMobile,
  addSocialHandle,
  storefront,
  reorderSocialHandle,
  updateSocialHandle,
  deleteSocialHandle,
}) => {
  const [showSelectModal, setShowSelectModal] = useState<boolean>(false);
  const [inputVal, setInputVal] = useState<string>('');
  const [selectedHandle, setSelectedHandle] = useState<SocialHandle | null>(null);
  const [isEditing, setIsEditing] = useState<boolean>(false);
  const [openDeleteModal, setOpenDeleteModal] = useState<boolean>(false);
  const [selectedPlatform, setSelectedPlatform] = useState<string>('');

  const socialHandles = storefront?.entity?.social_handles || [];
  const hasReachedHandleLimit = socialHandles.length >= MAX_SOCIAL_HANDLE_ALLOWED;

  useEffect(() => {
    if (!selectedHandle) return;
    const profile = socialHandles.find((profile) => profile?.platform === selectedHandle.name);
    setInputVal(profile?.profile_url ?? '');
  }, [selectedHandle, socialHandles]);

  const handleInputChange = (e: any) => {
    setInputVal(e.value);
  };

  const handleSelect = (handle: SocialHandle) => {
    setSelectedHandle(handle);
  };

  const onSave = useCallback(() => {
    if (!selectedHandle) return;

    const platform = selectedHandle.name;
    const payload: SocialMediaHandle = { platform, profile_url: inputVal };
    const isUpdating = isEditing || socialHandles.some((profile) => profile.platform === platform);

    if (isUpdating) {
      updateSocialHandle(payload);
      showNotification({
        type: 'success',
        message: `${platform.toUpperCase()} handle has been updated to your storefront`,
      });
    } else {
      const position = getNextPosition(socialHandles);
      addSocialHandle({ ...payload, position });
      showNotification({
        type: 'success',
        message: `${platform.toUpperCase()} handle has been added to your storefront`,
      });
    }

    setSelectedHandle(null);
    setShowSelectModal(false);
    setIsEditing(false);
  }, [selectedHandle, inputVal, isEditing, socialHandles, updateSocialHandle, addSocialHandle]);

  const onCancel = useCallback(() => {
    if (isEditing) {
      setSelectedHandle(null);
      setShowSelectModal(false);
      setIsEditing(false);
    } else {
      selectedHandle ? setSelectedHandle(null) : setShowSelectModal(false);
    }
  }, [isEditing, selectedHandle]);

  const handleEditClick = (platform: string) => {
    setIsEditing(true);
    const handle = SOCIAL_HANDLES.find((handle) => handle.name === platform);
    if (handle) {
      setShowSelectModal(true);
      setSelectedHandle(handle);
    }
  };

  const handleDeleteClick = (platform: string) => {
    setSelectedPlatform(platform);
    setOpenDeleteModal(true);
  };

  const handleConfirmDelete = () => {
    setOpenDeleteModal(false);
    deleteSocialHandle({ platform: selectedPlatform });
  };

  const handleConfirmCancel = () => {
    setOpenDeleteModal(false);
  };

  const handleSelectModalOpen = useCallback(() => {
    if (hasReachedHandleLimit) {
      showNotification({
        type: 'error',
        message: "You've reached the handles limit. Delete existing handles to add new ones.",
      });
      return;
    }
    setShowSelectModal(true);
  }, [hasReachedHandleLimit]);

  return (
    <>
      {isMobile ? (
        <BottomSheet
          isOpen={true}
          onDismiss={selectedHandle || showSelectModal ? onCancel : handleClose}
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
                  onChange={handleInputChange}
                  value={inputVal}
                  name={selectedHandle.name}
                />
              </Box>
            ) : showSelectModal ? (
              <>
                {SOCIAL_HANDLES.map((item) => (
                  <SocialHandleItemMobile key={item.name} item={item} onSelect={handleSelect} />
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
                        onClick={handleSelectModalOpen}
                      />
                    }
                  />
                </Box>
                {socialHandles.length > 0 && (
                  <SocialHandleList
                    isMobile={isMobile}
                    reorderSocialHandle={reorderSocialHandle}
                    socialHandleList={socialHandles}
                    handleEditClick={handleEditClick}
                    handleDeleteClick={handleDeleteClick}
                  />
                )}
              </>
            )}
          </BottomSheetBody>
          <BottomSheetFooter>
            {selectedHandle && (
              <ModalFooterButtons
                onCancel={onCancel}
                onSave={onSave}
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
                  onClick={handleSelectModalOpen}
                />
              }
            />
          </Box>
          {socialHandles.length > 0 && (
            <SocialHandleList
              isMobile={isMobile}
              reorderSocialHandle={reorderSocialHandle}
              socialHandleList={socialHandles}
              handleEditClick={handleEditClick}
              handleDeleteClick={handleDeleteClick}
            />
          )}
          {showSelectModal && (
            <RenderSelectSocialHandlesDesktop
              inputVal={inputVal}
              onSave={onSave}
              selectedHandle={selectedHandle}
              setSelectedHandle={setSelectedHandle}
              handleInputChange={handleInputChange}
              onCancel={onCancel}
            />
          )}
        </PaymentPagesDrawer>
      )}

      {/* Delete confirmation modal */}
      {openDeleteModal &&
        (isMobile ? (
          <BottomSheet
            isOpen={true}
            onDismiss={handleConfirmCancel}
            zIndex={zIndicesMap.modal}
            snapPoints={[0.9, 0.9, 0.9]}
          >
            <BottomSheetHeader
              title="Confirm Deletion of Social handle"
              subtitle="Please confirm if you would like to proceed with deleting the social handle."
            />
            <BottomSheetFooter>
              <ModalFooterButtons onCancel={handleConfirmCancel} onSave={handleConfirmDelete} />
            </BottomSheetFooter>
          </BottomSheet>
        ) : (
          <Modal isOpen={true} onDismiss={handleConfirmCancel} size="small">
            <ModalHeader
              title="You want to remove social handle?"
              subtitle="Your added handle will be deleted."
            />
            <ModalFooter>
              <ModalFooterButtons onCancel={handleConfirmCancel} onSave={handleConfirmDelete} />
            </ModalFooter>
          </Modal>
        ))}
    </>
  );
};

const mapStateToProps = (state: any) => ({
  storefront: state.paymentPageStorefront,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch: any) => ({
  addSocialHandle: bindActionCreators(addSocialHandle, dispatch),
  reorderSocialHandle: bindActionCreators(reorderSocialHandle, dispatch),
  updateSocialHandle: bindActionCreators(updatedSocialHandle, dispatch),
  deleteSocialHandle: bindActionCreators(deleteSocialHandle, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(SocialHandleDrawer);

import React, { useEffect, useMemo, useState } from 'react';
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
  ModalFooter,
  BottomSheetFooter,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import SocialHandleList from './SocialHandleList';
import {
  MAX_SOCIAL_HANDLE_ALLOWED,
  PLATFORM_NAMES,
  SOCIAL_HANDLES,
} from 'merchant/views/PaymentPages/PaymentPages/constants';
import { SocialHandleDrawerViewProps } from '../types';
import LineItems from '../LineItems';
import {
  AddDetailsContent,
  AddDetailsFooterButtons,
  SocialHandleModal,
} from './SocialHandleContent';
import {
  deleteSocialHandle,
  reorderSocialHandle,
} from '@dashboards/payments/reducers/paymentPages/storefront';
import { zIndicesMap } from '@libs/web-nexus/common/constant';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';

const SocialHandleDrawerView: React.FC<SocialHandleDrawerViewProps> = ({
  storefront,
  isMobile,
  handleClose,
  showSelectModal,
  selectedHandle,
  setSelectedHandle,
  saveSocialHandle,
  cancelSocialHandleOperation,
  editSocialHandle,
  openSocialHandleSelector,
  reorderSocialHandle,
  isSaving,
  deleteSocialHandle,
}) => {
  const [openDeleteModal, setOpenDeleteModal] = useState<boolean>(false);
  const [selectedPlatform, setSelectedPlatform] = useState<string>('');
  const [inputVal, setInputVal] = useState<string>('');
  const [uploadedFile, setUploadedFile] = useState<File | null>(null);
  const [uploadedLogo, setUploadedLogo] = useState<string>('');
  const socialHandles = storefront?.entity?.social_handles || [];
  const hasReachedHandleLimit = socialHandles.length >= MAX_SOCIAL_HANDLE_ALLOWED;

  useEffect(() => {
    if (!selectedHandle) return;
    const profile = socialHandles.find((profile) => profile.platform === selectedHandle.name);
    setInputVal(profile?.profile_url ?? '');
    if (profile?.logo_url && profile?.platform === PLATFORM_NAMES.CUSTOM) {
      setUploadedLogo(profile.logo_url);
    }
  }, [selectedHandle, socialHandles]);

  const updateInputValue = (e: any) => {
    setInputVal(e.value);
    track.addSocialLinkClicked({
      storefrontId: storefront?.id,
      isNewStorefront: Boolean(!storefront?.id),
      socialHandle: {
        name: selectedHandle?.name,
        link: e.value,
      },
    });
  };

  const showDeleteConfirmation = (platform: string) => {
    setSelectedPlatform(platform);
    setOpenDeleteModal(true);
  };

  const confirmDeleteSocialHandle = () => {
    deleteSocialHandle({ platform: selectedPlatform });
    setSelectedPlatform('');
    setOpenDeleteModal(false);
    track.deleteSocialLinkClicked(selectedPlatform, {
      storefrontId: storefront?.id,
      isNewStorefront: Boolean(!storefront?.id),
    });
  };

  const cancelDeleteSocialHandle = () => {
    setOpenDeleteModal(false);
  };

  const isSaveDisabled = useMemo(() => {
    const profile = socialHandles.find((profile) => profile.platform === selectedHandle?.name);
    const isCustomWebsite = selectedHandle?.name === PLATFORM_NAMES.CUSTOM;

    if (isCustomWebsite) {
      const isLogoValid = uploadedLogo !== '' || (uploadedLogo === '' && uploadedFile !== null);
      const isUrlChange = inputVal === profile?.profile_url && uploadedLogo === profile?.logo_url;

      return !inputVal?.length || !isLogoValid || isSaving || isUrlChange;
    } else {
      const isUrlChange = inputVal === profile?.profile_url;

      return !inputVal?.length || isSaving || isUrlChange;
    }
  }, [socialHandles, selectedHandle, inputVal, uploadedLogo, isSaving, uploadedFile]);
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
              <AddDetailsContent
                selectedHandle={selectedHandle}
                uploadedFile={uploadedFile}
                setUploadedFile={setUploadedFile}
                uploadedLogo={uploadedLogo}
                setUploadedLogo={setUploadedLogo}
                inputVal={inputVal}
                handleInputChange={updateInputValue}
                isMobile={isMobile}
                storefrontId={storefront?.id}
              />
            ) : showSelectModal ? (
              <>
                <ActionList>
                  {SOCIAL_HANDLES.map((item) => (
                    <ActionListItem
                      key={item.name}
                      leading={<img src={item.src} alt={item.label} width={24} height={24} />}
                      title={item.label}
                      value={item.name}
                      onClick={() => {
                        setSelectedHandle(item);
                        track.socialHandleClicked(item?.name, {
                          storefrontId: storefront?.id,
                          isNewStoreFront: Boolean(!storefront?.id),
                        });
                      }}
                    />
                  ))}
                </ActionList>
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
                        accessibilityLabel={`add social handle modal opener ${
                          hasReachedHandleLimit ? 'disabled' : ''
                        }`}
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
              <AddDetailsFooterButtons
                onCancel={cancelSocialHandleOperation}
                onSave={saveSocialHandle}
                inputVal={inputVal}
                isSaving={isSaving}
                isSaveDisabled={isSaveDisabled}
                uploadedFile={uploadedFile}
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
              accessibilityLabel="close icon"
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
                  accessibilityLabel={`add social handle modal opener ${
                    hasReachedHandleLimit ? 'disabled' : ''
                  }`}
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
            <SocialHandleModal
              inputVal={inputVal}
              saveSocialHandle={saveSocialHandle}
              selectedHandle={selectedHandle}
              updateInputValue={updateInputValue}
              cancelSocialHandleOperation={cancelSocialHandleOperation}
              uploadedFile={uploadedFile}
              setUploadedFile={setUploadedFile}
              isSaving={isSaving}
              uploadedLogo={uploadedLogo}
              setUploadedLogo={setUploadedLogo}
              isSaveDisabled={isSaveDisabled}
              setSelectedHandle={setSelectedHandle}
              storefrontId={storefront?.id}
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
              <AddDetailsFooterButtons
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
              <AddDetailsFooterButtons
                onCancel={cancelDeleteSocialHandle}
                onSave={confirmDeleteSocialHandle}
              />
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
  deleteSocialHandle: bindActionCreators(deleteSocialHandle, dispatch),
  reorderSocialHandle: bindActionCreators(reorderSocialHandle, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(SocialHandleDrawerView);

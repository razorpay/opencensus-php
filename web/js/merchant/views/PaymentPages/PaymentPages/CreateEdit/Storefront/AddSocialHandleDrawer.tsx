import React, { useEffect, useState, useCallback } from 'react';
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
import { MAX_SOCIAL_HANDLE_ALLOWED } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { SocialHandle, SocialHandleDrawerProps, SocialHandleModalProps } from './types';

const SocialHandleDrawer: React.FC<SocialHandleDrawerProps> = ({
  handleClose,
  isMobile,
  storefront,
}) => {
  const [showSelectModal, setShowSelectModal] = useState<boolean>(false);
  const [inputVal, setInputVal] = useState<string>('');
  const [selectedHandle, setSelectedHandle] = useState<SocialHandle | null>(null);
  const [isEditing, setIsEditing] = useState<boolean>(false);
  const socialHandles = storefront?.entity?.social_handles || [];
  const hasReachedHandleLimit = socialHandles.length >= MAX_SOCIAL_HANDLE_ALLOWED;

  useEffect(() => {
    if (!selectedHandle) return;
    const profile = socialHandles.find((profile) => profile?.platform === selectedHandle.name);
    setInputVal(profile?.profile_url ?? '');
  }, [selectedHandle, socialHandles]);

  const onCancel = useCallback(() => {
    if (isEditing) {
      setSelectedHandle(null);
      setShowSelectModal(false);
      setIsEditing(false);
    } else {
      selectedHandle ? setSelectedHandle(null) : setShowSelectModal(false);
    }
  }, [isEditing, selectedHandle]);

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
          zIndex={10000}
          snapPoints={[0.9, 0.9, 0.9]}
        >
          <BottomSheetHeader
            title={selectedHandle ? selectedHandle.inputLabel : 'Select a social account'}
          />
          <BottomSheetBody>
            <Text size="small" color="surface.text.gray.subtle">
              You can add a maximum of 4 social accounts
            </Text>
          </BottomSheetBody>
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
        </PaymentPagesDrawer>
      )}
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

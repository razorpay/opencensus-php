import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  Button,
  Box,
  ModalHeader as BladeModalHeader,
  ModalBody as BladeModalBody,
  ModalFooter as BladeModalFooter,
  Modal as BladeModal,
  BottomSheet,
  Text,
  BottomSheetHeader,
  BottomSheetBody,
  BottomSheetFooter,
} from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import { defaultSnapPoints } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import {
  fetchOauthConnectedApplications as fetchOauthConnectedApplicationsFn,
  revokeOauthApplicationAccess as revokeOauthApplicationAccessFn,
} from 'merchant/reducers/applications';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import { whatsappAccountSetupAnalyticsTrack } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/utils';

const DeleteAccount = ({
  modalState: { isOpen },
  onClose,
  businessProvider,
  revokeOauthApplicationAccess,
  fetchOauthConnectedApplications,
  showNotification,
  updateNotificationFeature,
}): JSX.Element => {
  const [isLoading, setIsLoading] = useState(false);
  const isMobile = useMobile();

  const handleDismiss = (): void => {
    if (isMobile) {
      document.body.style.overflow = 'unset';
    }
    onClose({ action: 'close' });
  };

  const handleContinue = (): void => {
    const { application_id, title } = businessProvider;
    whatsappAccountSetupAnalyticsTrack({
      objectName: 'WA PL Account Delete Confirm',
      actionName: 'Clicked',
      properties: {
        selectedBusinessAccount: title,
      },
    });
    setIsLoading(true);
    revokeOauthApplicationAccess(application_id)
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Business Provider deleted successfully',
        });
        updateNotificationFeature(false);
        fetchOauthConnectedApplications();
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Something went wrong',
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  const { Modal, ModalHeader, ModalBody, ModalFooter } = isMobile
    ? {
        Modal: BottomSheet,
        ModalHeader: BottomSheetHeader,
        ModalBody: BottomSheetBody,
        ModalFooter: BottomSheetFooter,
      }
    : {
        Modal: BladeModal,
        ModalHeader: BladeModalHeader,
        ModalBody: BladeModalBody,
        ModalFooter: BladeModalFooter,
      };

  return (
    <Modal zIndex={1112} isOpen={isOpen} onDismiss={handleDismiss} snapPoints={defaultSnapPoints}>
      <ModalHeader title="Are you sure you want delete?" />
      <ModalBody>
        <Text type="subdued" size="large">
          Are you sure you want to delete your Whatsapp Business Profile?
        </Text>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" width="100%" gap="spacing.5">
          <Button variant="secondary" onClick={handleDismiss}>
            Cancel
          </Button>
          <Button variant="primary" color="negative" onClick={handleContinue} isLoading={isLoading}>
            Yes, Delete
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  applications: state.applications,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchOauthConnectedApplications: fetchOauthConnectedApplicationsFn,
      revokeOauthApplicationAccess: revokeOauthApplicationAccessFn,
      ...NotificationActions,
    },
    dispatch,
  );
};
export default connect(mapStateToProps, mapDispatchToProps)(DeleteAccount);

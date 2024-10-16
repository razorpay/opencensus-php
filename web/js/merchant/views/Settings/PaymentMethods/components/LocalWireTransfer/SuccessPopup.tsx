import React, { useState } from 'react';
import {
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Button,
  PaymentLinksIcon,
  Box,
  Text,
  TextInput,
} from '@razorpay/blade/components';
import GreenCheck from 'assets/cross-border/green-check.png';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';

import copyToClipboard from 'common/utils/copyToClipboard';
import { setPublicPaymentLink } from 'merchant/reducers/b2bExports/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import { trackCopyLinkClicked, trackEditLinkClicked, trackLinkCreation } from './analytics';
import { ACCOUNT_TRANSFER_MAPPING, BASE_PAYMENT_LINK_URL } from './constants';
import { updatePublicPaymentLink } from './services';
import { SuccessPopupProps } from './types';

const SuccessPopup = ({
  isOpen,
  account = 'GBP',
  shouldAllowEdit,
  publicPaymentLink,
  setPublicPaymentLink,
  showNotification,
  onDismiss,
}: SuccessPopupProps) => {
  const [isLinkEditable, setIsLinkEditable] = useState(false);
  const [paymentLinkUrl, setPaymentLinkUrl] = useState(
    BASE_PAYMENT_LINK_URL + publicPaymentLink ?? '',
  );
  const [isSaving, setIsSaving] = useState(false);

  const onEditLinkClick = () => {
    if (isLinkEditable) {
      setPaymentLinkUrl(BASE_PAYMENT_LINK_URL + publicPaymentLink);
      setIsLinkEditable(false);
    } else {
      setIsLinkEditable(true);
      trackEditLinkClicked(account);
    }
  };

  const onCopyLinkClick = async () => {
    try {
      const paymentLinkTag = paymentLinkUrl.replace(BASE_PAYMENT_LINK_URL, '');
      if (isLinkEditable && paymentLinkTag !== publicPaymentLink && paymentLinkTag.length) {
        setIsSaving(true);
        const response = await updatePublicPaymentLink(paymentLinkTag);
        trackLinkCreation(account, true);
        showNotification({ type: 'success', message: 'Link updated successfully' });
        setPublicPaymentLink(response.data.export_id);
        setIsLinkEditable(false);
      }
      if (!isLinkEditable) {
        copyToClipboard(paymentLinkUrl);
        trackCopyLinkClicked();
        showNotification({ type: 'success', message: 'Link copied successfully' });
      }
    } catch (error) {
      showNotification({ type: 'error', message: error });
      trackLinkCreation(account, true, false, error as string);
    } finally {
      setIsSaving(false);
    }
  };

  const onInputChange = (value: string | undefined) => {
    if (value && value !== BASE_PAYMENT_LINK_URL.slice(0, -1)) {
      setPaymentLinkUrl(value);
    }
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss}>
      <ModalHeader title={`Request for ${account} Currency Bank Account`} />
      <ModalBody>
        <Box display="flex" flexDirection="column" alignItems="center">
          <Box height="84px" width="84px" marginBottom="spacing.7">
            <img src={GreenCheck} height="100%" />
          </Box>
          <Text marginBottom="spacing.6">
            Congratulations! You can now accept {account} payments via{' '}
            {ACCOUNT_TRANSFER_MAPPING[account]} bank transfer with your local currency bank account.
          </Text>
          <Text marginBottom="spacing.4">
            You now have a dedicated page with details of {account} and other bank accounts of yours
            which can be shared with anyone:
          </Text>
          <Box width="100%">
            <TextInput
              label=""
              isDisabled={!isLinkEditable}
              value={paymentLinkUrl}
              name="link"
              onChange={(data) => onInputChange(data.value)}
              type="url"
              helpText={
                shouldAllowEdit
                  ? 'You may edit the link name so you and your customer can remember the link'
                  : ''
              }
            />
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end">
          {shouldAllowEdit && (
            <Button variant="secondary" marginRight="spacing.4" onClick={onEditLinkClick}>
              {isLinkEditable ? 'Cancel' : 'Edit link'}
            </Button>
          )}
          <Button
            icon={PaymentLinksIcon}
            iconPosition="left"
            isLoading={isSaving}
            onClick={onCopyLinkClick}
          >
            {isLinkEditable ? 'Save changes' : 'Copy link'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = ({ b2bExportsAccounts }) => ({
  publicPaymentLink: b2bExportsAccounts.publicPaymentLink,
});

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      showNotification,
      setPublicPaymentLink,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SuccessPopup);

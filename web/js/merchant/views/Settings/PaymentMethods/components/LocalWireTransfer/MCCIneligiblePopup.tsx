import React from 'react';
import { Button, Text, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';
import { MCCIneligibleProps } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

/**
 * MCCIneligiblePopup component.
 *
 * @param {MCCIneligibleProps} props - Props for the component.
 * @param {() => void} props.onClose - Function to close the popup.
 * @param {() => void} props.onCloseAction - Function to execute when closing the popup.
 * @return {JSX.Element} The rendered MCCIneligiblePopup component.
 */
const MCCIneligiblePopup = ({ error, onClose }: MCCIneligibleProps): JSX.Element => {
  const handleClose = () => {
    onClose();
  };

  return (
    <Box>
      <ModalHeader title="Ineligible Merchant Category" onCloseClick={handleClose} />
      <Box paddingX="spacing.7" paddingTop="spacing.4" paddingBottom="spacing.7">
        <Text marginBottom="spacing.4">{error}</Text>
        <Button isFullWidth onClick={handleClose}>
          Close
        </Button>
      </Box>
    </Box>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      onOpen: openModal,
      onClose: closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(MCCIneligiblePopup);

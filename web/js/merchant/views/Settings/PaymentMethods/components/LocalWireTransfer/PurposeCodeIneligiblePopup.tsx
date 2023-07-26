import React, { useState } from 'react';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

// Redux
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
///- Redux

// Actions
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { closePurposeCodeIneligibleModal } from 'merchant/reducers/b2bExports/actions';
///- Actions

// Utils
import { PurposeCodeIneligibleProps } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
///- Utils

// Components
import ModalHeader from 'common/ui/ModalHeader';
import { Button } from '@razorpay/blade/components';
///- Components

// Dynamic Component
const FircFormModal = lazy(
  () =>
    import(
      /* webpackChunkName: "FircFormModal" */ 'merchant/views/Account/Profile/components/FIRC/FIRCFormModal'
    ),
);
///- Dynamic Component

/**
 * PurposeCodeIneligiblePopup component.
 *
 * @param {PurposeCodeIneligibleProps} props - Props for the component.
 * @param {() => void} props.onClose - Function to close the popup.
 * @param {() => void} props.onCloseAction - Function to execute when closing the popup.
 * @return {JSX.Element} The rendered PurposeCodeIneligiblePopup component.
 */
const PurposeCodeIneligiblePopup = ({
  code,
  onOpen,
  onClose,
  onCloseAction,
}: PurposeCodeIneligibleProps): JSX.Element => {
  const [isLoading] = useState(false);

  const handleClose = () => {
    onCloseAction();
    onClose();
  };

  const handleUpdateRequest = () => {
    handleClose();

    onOpen({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <FircFormModal editMode={Boolean(code)} code={code} />
        </SuspenseWithLoader>
      ),
    });
  };

  return (
    <div className="b2b-acknowledgement-popup">
      <ModalHeader title="Ineligible Purpose Code" onCloseClick={handleClose} />
      <div className="modal-body">
        <p>
          The purpose code you have provided is incorrect as per your category of business. Update
          your purpose code to settle your transactions.
        </p>
        <Button variant="primary" isLoading={isLoading} isFullWidth onClick={handleUpdateRequest}>
          Update purpose code
        </Button>
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      onOpen: openModal,
      onClose: closeModal,
      onCloseAction: closePurposeCodeIneligibleModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(PurposeCodeIneligiblePopup);

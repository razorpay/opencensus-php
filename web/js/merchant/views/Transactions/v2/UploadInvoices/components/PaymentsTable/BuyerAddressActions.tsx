import React, { lazy } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Link, PlusIcon } from '@razorpay/blade/components';
import { PaymentStatus } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { BuyerAddressActionProps } from './types';

const BuyerAddressModalLazy = lazy(
  () =>
    import(
      /* webpackChunkName: 'BuyerAddressModal' */ 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal'
    ),
);

const BuyerAddressActions = ({
  id,
  status,
  senderDetails,
  openModal,
  closeModal,
  showNotification,
}: BuyerAddressActionProps): JSX.Element | null => {
  const { name, country } = senderDetails ?? {};

  const onAddBuyerAddressClick = () => {
    openModal({
      component: (
        <SuspenseWithLoader>
          <BuyerAddressModalLazy
            paymentId={id}
            onClose={closeModal}
            showNotification={showNotification}
          />
        </SuspenseWithLoader>
      ),
      size: 'xlarge',
    });
  };

  if (status === PaymentStatus.AUTHORIZED && (!name || !country)) {
    return (
      <Link variant="button" icon={PlusIcon} size="medium" onClick={onAddBuyerAddressClick}>
        Add buyer address
      </Link>
    );
  }
  return null;
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
      openModal,
      closeModal,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(BuyerAddressActions);

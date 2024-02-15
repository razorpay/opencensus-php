import React, { useState } from 'react';
import { Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ShowNotificationType, User } from 'common/typings';
import { downloadSubmerchants } from 'merchant/reducers/submerchant';
import { OpenModalT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import ConfirmGenerateReport from 'merchant/views/PartnerDashboard/SubMerchant/components/ConfirmGenerateReport';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

type ExportButtonProps = {
  closeModal: () => void;
  openModal: OpenModalT;
  productType: string;
  showNotification: ShowNotificationType;
  user: User;
};
const ExportButton = ({
  closeModal,
  openModal,
  productType,
  showNotification,
  user,
}: ExportButtonProps) => {
  const [isDownloading, setIsDownloading] = useState(false);

  const onDownload = () => {
    // TODO v2: new analytics
    // trackUserEvent('partnerships.dashboard.affiliate_account.export');
    showNotification({
      type: 'info',
      message: 'Your file will downloaded shortly',
      hidePrevious: true,
    });
    setIsDownloading(true);
    return downloadSubmerchants(user.isPartner('pure_platform'), user.id)
      .then((response) => {
        if (response.error) {
          showNotification({
            type: 'error',
            message: 'Oops!, Unable to export data of submerchants',
            hidePrevious: true,
          });
          return;
        }
        window.location = response.data.signed_url;
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Oops!, Unable to export data of submerchants',
          hidePrevious: true,
        });
      })
      .finally(() => {
        setIsDownloading(false);
      });
  };

  const onGenerateClick = () => {
    onDownload();
    closeModal();
  };

  const confirmAndDownload = () => {
    if (productType !== PRODUCT_TYPE.X) {
      openModal({
        size: 'large',
        component: <ConfirmGenerateReport onDownload={onDownload} closeModal={closeModal} />,
      });
    } else {
      onGenerateClick();
    }
  };
  return (
    <Button variant="tertiary" onClick={confirmAndDownload} isDisabled={isDownloading}>
      {isDownloading ? 'Exporting...' : 'Export All (CSV)'}
    </Button>
  );
};

export default connect(
  (state) => ({ user: state.session.user }),
  (dispatch) =>
    bindActionCreators(
      {
        openModal,
        closeModal,
        showNotification,
      },
      dispatch,
    ),
)(ExportButton);

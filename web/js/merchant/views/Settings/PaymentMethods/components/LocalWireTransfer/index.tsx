import React, { useState, useEffect, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { fetchB2bAccounts } from 'merchant/reducers/b2bExports/actions';
import { fetchPurposeCode } from 'merchant/reducers/profile';
import lazy from 'merchant/routes/LazyLoader';
import InstrumentContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer';
import withBankTransferConfig from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/BankTransferConfig';
import InstrumentRow from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/InstrumentRow';
import { trackTandCPopupOpened } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';
import { VA_USD } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import { LocalWireTransferPropsInterface } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

//Styles
import './LocalWireTransfer.styl';

const AcknowledgementPopup = lazy(
  () =>
    import(
      /* webpackChunkName: "AcknowledgementPopup" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/AcknowledgementPopup'
    ),
);

const PurposeCodeIneligiblePopup = lazy(
  () =>
    import(
      /* webpackChunkName: "PurposeCodeIneligiblePopup" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/PurposeCodeIneligiblePopup'
    ),
);

const LocalWireTransfer: React.FC<LocalWireTransferPropsInterface> = ({
  leafList,
  config,
  apiError,
  isIneligiblePurposeCodeModalOpen,
  purposeCode,
  fetchB2bAccounts,
  fetchPurposeCode,
  showNotification,
  openModal,
  ...data
}) => {
  const { containerStatus, containerError, accounts, shouldShowAction, shouldShowListAction } =
    config;

  const [isOpen, setIsOpen] = useState<boolean | string>(false);

  /**
   * acknowledgement popup is opened to get T&C approval from merchant
   * before account activation
   */
  const onRequest = () => {
    trackTandCPopupOpened(VA_USD);
    openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <AcknowledgementPopup />
        </SuspenseWithLoader>
      ),
    });
  };

  const onOpenPurposeCodeIneligiblePopup = useCallback(() => {
    openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <PurposeCodeIneligiblePopup code={purposeCode} />
        </SuspenseWithLoader>
      ),
    });
  }, [openModal, purposeCode]);

  /**
   * We are calling the fetch purpose code api to check if purpose code is
   * attached with the merchant or not, depending upon which we'll ask merchant
   * to update the purpose code
   */
  useEffect(() => {
    fetchPurposeCode();
    if (!accounts?.length) fetchB2bAccounts();
  }, []);

  //this handles api errors
  useEffect(() => {
    if (apiError) {
      showNotification({
        type: 'error',
        message: apiError,
      });
    }
  }, [apiError, showNotification]);

  useEffect(() => {
    if (isIneligiblePurposeCodeModalOpen && purposeCode) {
      onOpenPurposeCodeIneligiblePopup();
    }
  }, [isIneligiblePurposeCodeModalOpen, onOpenPurposeCodeIneligiblePopup, purposeCode]);

  return (
    <ErrorBoundary rank={Ranks.P1} team={Teams.CROSS_BORDER} resetOnProps>
      <InstrumentContainer
        containerStatus={containerStatus}
        showAction={shouldShowAction}
        showListAction={shouldShowListAction}
        leafList={leafList}
        onInstrumentRequest={onRequest}
        instrumentRow={InstrumentRow}
        accounts={accounts}
        isOpen={isOpen}
        setIsOpen={setIsOpen}
        error={containerError}
        {...data}
      />
    </ErrorBoundary>
  );
};

const mapStateToProps = (state) => ({
  apiError: state.b2bExportsAccounts.error,
  isIneligiblePurposeCodeModalOpen: state.b2bExportsAccounts.isIneligiblePurposeCodeModalOpen,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchB2bAccounts,
      fetchPurposeCode,
      showNotification,
      openModal,
    },
    dispatch,
  );

export default withBankTransferConfig(
  connect(mapStateToProps, mapDispatchToProps)(LocalWireTransfer),
);

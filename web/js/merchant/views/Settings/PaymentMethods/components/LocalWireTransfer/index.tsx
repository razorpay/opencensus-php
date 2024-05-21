import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { fetchB2bAccounts, setPublicPaymentLink } from 'merchant/reducers/b2bExports/actions';
import { fetchPurposeCode } from 'merchant/reducers/profile';
import { videoKycBannerActions } from 'merchant/reducers/videoKYCBanner';
import lazy from 'merchant/routes/LazyLoader';
import InstrumentContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer';
import withBankTransferConfig from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/BankTransferConfig';
import InstrumentRow from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/InstrumentRow';
import { trackTandCPopupOpened } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';
import {
  VA_USD,
  DISABLE_REQUEST_TOOLTIP,
  INIT_POPUP_DETAILS,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import { LocalWireTransferPropsInterface } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

//Styles
import './LocalWireTransfer.styl';
import { fetchPublicPaymentLink } from './services';
import { getPublicPaymentLinkForContainer } from './utils';

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

const SuccessPopup = lazy(
  () =>
    import(
      /* webpackChunkName: "SuccessPopup" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/SuccessPopup'
    ),
);

const LocalWireTransfer: React.FC<LocalWireTransferPropsInterface> = ({
  leafList,
  config,
  apiError,
  isIneligiblePurposeCodeModalOpen,
  purposeCode,
  fetchB2bAccounts,
  setPublicPaymentLink,
  fetchPurposeCode,
  showNotification,
  openModal,
  onMoneySaverAccountsActivated,
  ...data
}) => {
  const {
    containerStatus,
    containerError,
    accounts,
    publicPaymentLink,
    shouldShowAction,
    shouldShowListAction,
    isRequestButtonDisabled,
  } = config;

  const [isOpen, setIsOpen] = useState<boolean | string>(false);
  const [successPopupDetails, setSuccessPopupDetails] = useState(INIT_POPUP_DETAILS);

  const paymentLinkForContainer = useMemo(
    () => getPublicPaymentLinkForContainer(leafList, accounts, publicPaymentLink),
    [publicPaymentLink, accounts, leafList],
  );

  const onAccountCreationSuccess = (account, url) => {
    setSuccessPopupDetails({ isOpen: true, account, url });
  };

  const onSuccessDismiss = () => {
    setSuccessPopupDetails(INIT_POPUP_DETAILS);
  };

  /**
   * acknowledgement popup is opened to get T&C approval from merchant
   * before account activation
   */
  const onRequest = (data: { vaCurrency: string }) => {
    trackTandCPopupOpened(VA_USD);
    openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <AcknowledgementPopup account={data.vaCurrency} onSuccess={onAccountCreationSuccess} />
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

  const memoizedOnMoneySaverAccountsActivated = useCallback(
    (hasAccounts) => {
      onMoneySaverAccountsActivated(hasAccounts);
    },
    [onMoneySaverAccountsActivated],
  );

  const initRequest = async () => {
    fetchPurposeCode();
    if (!accounts?.length) {
      const response = await fetchB2bAccounts();
      if (response.data?.accounts?.length) {
        const response = await fetchPublicPaymentLink();
        if (response.data.export_id) setPublicPaymentLink(response.data.export_id);
      }
    }
  };

  /**
   * We are calling the fetch purpose code api to check if purpose code is
   * attached with the merchant or not, depending upon which we'll ask merchant
   * to update the purpose code
   */
  useEffect(() => {
    initRequest();
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

  useEffect(() => {
    if (!config?.isFetching) {
      memoizedOnMoneySaverAccountsActivated(accounts?.length > 0);
    }
  }, [config.isFetching, accounts, memoizedOnMoneySaverAccountsActivated]);

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
        isRequestButtonDisabled={isRequestButtonDisabled}
        requestTooltipText={isRequestButtonDisabled ? DISABLE_REQUEST_TOOLTIP : ''}
        publicPaymentLink={paymentLinkForContainer}
        {...data}
      />
      {successPopupDetails.isOpen && (
        <SuccessPopup
          isOpen={successPopupDetails.isOpen}
          shouldAllowEdit={!!successPopupDetails.url}
          account={successPopupDetails.account}
          onDismiss={onSuccessDismiss}
        />
      )}
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
      setPublicPaymentLink,
      fetchPurposeCode,
      showNotification,
      openModal,
      onMoneySaverAccountsActivated: videoKycBannerActions.setMoneySaverAccountsActivated,
    },
    dispatch,
  );

export default withBankTransferConfig(
  connect(mapStateToProps, mapDispatchToProps)(LocalWireTransfer),
);

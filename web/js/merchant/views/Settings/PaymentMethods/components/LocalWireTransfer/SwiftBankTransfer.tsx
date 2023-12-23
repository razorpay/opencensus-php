import React, { useMemo, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//components
import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import InstrumentContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer';
import withBankTransferConfig from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/BankTransferConfig';
import InstrumentRow from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/InstrumentRow';
import { trackTandCPopupOpened } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';
import {
  VA_USD,
  VA_SWIFT,
  DISABLE_REQUEST_TOOLTIP,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
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

const SwiftBankTransfer = ({ leafList, config, showNotification, openModal, ...data }) => {
  const {
    containerStatus,
    containerError,
    accounts,
    shouldShowAction,
    shouldShowListAction,
    isRequestButtonDisabled,
  } = config;

  const [isOpen, setIsOpen] = useState<boolean>(false);

  const isUSDAccountActivated = useMemo(() => {
    return Boolean(accounts?.find((account) => account?.va_currency === VA_USD));
  }, [accounts]);

  const onRequest = () => {
    trackTandCPopupOpened(VA_SWIFT);
    openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <AcknowledgementPopup account={VA_SWIFT} showTnC={!isUSDAccountActivated} />
        </SuspenseWithLoader>
      ),
    });
  };

  return (
    <ErrorBoundary rank={Ranks.P1} team={Teams.CROSS_BORDER} resetOnProps>
      <InstrumentContainer
        leafList={leafList}
        containerStatus={containerStatus}
        showAction={shouldShowAction}
        onInstrumentRequest={onRequest}
        instrumentRow={InstrumentRow}
        accounts={accounts}
        showListAction={shouldShowListAction}
        isOpen={isOpen}
        setIsOpen={setIsOpen}
        error={containerError}
        isRequestButtonDisabled={isRequestButtonDisabled}
        requestTooltipText={isRequestButtonDisabled ? DISABLE_REQUEST_TOOLTIP : ''}
        {...data}
      />
    </ErrorBoundary>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification,
      openModal,
    },
    dispatch,
  );

export default withBankTransferConfig(
  connect(null, mapDispatchToProps)(SwiftBankTransfer),
  VA_SWIFT,
);

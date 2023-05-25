import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//redux actions
import { showNotification } from 'merchant_common/reducers/notifications';
import { activateAccount } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services';

//constants
import { VA_SWIFT } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';

//components
import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import InstrumentContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer';
import InstrumentRow from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/InstrumentRow';
import withBankTransferConfig from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/BankTransferConfig';

//Styles
import './LocalWireTransfer.styl';

const SwiftBankTransfer = ({ leafList, config, showNotification, activateAccount, ...data }) => {
  const { containerStatus, containerError, accounts, shouldShowAction, shouldShowListAction } =
    config;

  const [isOpen, setIsOpen] = useState<boolean>(false);

  /**
   * acknowledgement popup is opened to get T&C approval from merchant
   * before account activation
   */
  const onRequest = async () => {
    try {
      const response = await activateAccount(VA_SWIFT, 0, 'intBankTransfer');
      if (response?.success) {
        showNotification({
          type: 'success',
          message: 'Accounts have been successfully created!',
        });
      }
    } catch ({ errors }) {
      showNotification({
        type: 'error',
        message: errors,
      });
    }
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
        {...data}
      />
    </ErrorBoundary>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification,
      activateAccount,
    },
    dispatch,
  );

export default withBankTransferConfig(
  connect(null, mapDispatchToProps)(SwiftBankTransfer),
  VA_SWIFT,
);

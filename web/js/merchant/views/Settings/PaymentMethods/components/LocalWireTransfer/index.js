import { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//redux actions
import { fetchB2bAccounts, setFeatureFlag } from 'merchant/reducers/b2bExports/actions';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchFeatureStatus } from 'merchant/reducers/config';

//analytics
import { trackTandCPopupOpened } from './analytics';

//components
import AcknowledgementPopup from './AcknowledgementPopup';
import InstrumentContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer';
import InstrumentRow from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/InstrumentRow';

//Constants
import { ACTIVATED, GREYED } from 'merchant/views/Settings/PaymentMethods/constants';

//Styles
import './LocalWireTransfer.styl';

const ACCOUNT_SUCCESS = 'Accept payments from International clients from US via ACH';

const LocalWireTransfer = ({
  leafList,
  accounts,
  isAccountCreated,
  fetchB2bAccounts,
  fetchFeatureStatus,
  setFeatureFlag,
  showNotification,
  user,
  openModal,
  error: apiError,
  ...data
}) => {
  const [isOpen, setIsOpen] = useState(false);

  /**
   * this function is called on initial load to check if accounts
   * have been created or not. Feature flag is set if accounts are there.
   */
  const initialize = async () => {
    const response = await fetchFeatureStatus(user?.id, 'enable_b2b_export');
    if (response?.data?.status) {
      setFeatureFlag({ isAccountCreated: true });
      fetchB2bAccounts();
    } else {
      setFeatureFlag({ isAccountCreated: false });
    }
  };

  /**
   * acknowledgement popup is opened to get T&C approval from merchant
   * before account activation
   */
  const onRequest = () => {
    trackTandCPopupOpened();
    openModal({
      size: 'medium',
      component: <AcknowledgementPopup />,
    });
  };

  useEffect(() => {
    !isAccountCreated && initialize();
  }, []);

  //this handles api errors
  useEffect(() => {
    if (apiError) {
      showNotification({
        type: 'error',
        message: apiError?.errors,
      });
    }
  }, [apiError]);

  return (
    <InstrumentContainer
      leafList={{
        ...leafList,
        listDescription: isAccountCreated ? ACCOUNT_SUCCESS : leafList.listDescription,
      }}
      buttonText="Request"
      containerStatus={isAccountCreated ? ACTIVATED : GREYED}
      onButtonClick={onRequest}
      instrumentRow={InstrumentRow}
      accounts={accounts}
      showListAction={isAccountCreated}
      isOpen={isOpen}
      setIsOpen={setIsOpen}
      {...data}
    />
  );
};

const mapStateToProps = (state) => ({
  accounts: state.b2bExportsAccounts.data,
  isLoading: state.b2bExportsAccounts.isLoading,
  isAccountCreated: state.b2bExportsAccounts.featureFlags?.isAccountCreated,
  error: state.b2bExportsAccounts.error,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchB2bAccounts,
      fetchFeatureStatus,
      setFeatureFlag,
      showNotification,
      openModal,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(LocalWireTransfer);

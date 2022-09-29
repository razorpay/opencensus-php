import { useState, useEffect, useMemo } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//redux actions
import { fetchB2bAccounts, setFeatureFlag } from 'merchant/reducers/b2bExports/actions';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchFeatureStatus } from 'merchant/reducers/config';

//analytics
import { trackAccountCopied, trackTandCPopupOpened } from './analytics';

//components
import AcknowledgementPopup from './AcknowledgementPopup';
import InstrumentContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer';
import Instrument from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/Instrument';
import LoaderDots from 'common/ui/LoaderDots';
import CustomClipboard from 'common/ui/Clipboard/Custom';

//Constants
import { ACTIVATED, GREYED } from 'merchant/views/Settings/PaymentMethods/constants';

//Styles
import './LocalWireTransfer.styl';

const DETAIL_FIELDS = [
  { label: 'Routing Code', key: 'Routing Code' },
  { label: 'Routing Type', key: 'Routing Type' },
  { label: 'Account Number', key: 'Account Number' },
  { label: 'Beneficiary Name', key: 'Beneficiary Name' },
  { label: 'Beneficiary Bank Name', key: 'Bank Name' },
  { label: 'Beneficiary Address', key: 'Bank Address' },
];
const ACCOUNT_SUCCESS = 'Accept payments from International clients from US via ACH';

const Toggle = ({ isOpen, isLoading, currency, onToggleClick }) => {
  const toggleText = isOpen === currency ? 'Hide' : 'Account';
  return (
    <div className="toggle-container">
      <p className="toggle-text" onClick={onToggleClick}>
        {isLoading ? 'Fetching' : toggleText} details
      </p>
      {isLoading ? (
        <LoaderDots />
      ) : (
        <span className="icon-wrapper">
          <i className="i i-chevron-right" />
        </span>
      )}
    </div>
  );
};

//Custom instrument row to pass to the instrument container
//through props
const InstrumentRow = (props) => {
  const { accounts, data, isOpen, isLoading, setIsOpen } = props;

  //finds the current account from list of accounts
  const accountDetails = useMemo(
    () => accounts.find((account) => account?.va_currency === data.vaCurrency),
    [accounts],
  );

  //dropdown handler
  const onToggleClick = () => {
    if (!isLoading) {
      setIsOpen(isOpen === data.vaCurrency ? false : data.vaCurrency);
    }
  };

  //formats data for clipboard component
  const textFormatter = () => {
    return DETAIL_FIELDS.reduce((str, field) => {
      return `${str}\n${field?.label} = ${
        accountDetails?.[field?.key?.replace(' ', '_')?.toLowerCase()] ?? '--'
      }`;
    }, '');
  };

  //return null if there are no accounts and current account details are not there
  if (!accountDetails && accounts?.length) return null;

  return (
    <div className={`local-wire-transfer-instrument${isOpen === data.vaCurrency ? ' active' : ''}`}>
      <Instrument
        rightButton={() => (
          <Toggle
            isOpen={isOpen}
            isLoading={isLoading}
            currency={data?.vaCurrency}
            onToggleClick={onToggleClick}
          />
        )}
        {...props}
      />
      <div className="detail-list">
        <div className="list-item">
          <p className="info">{data?.message}</p>
          <CustomClipboard value={textFormatter()} onCopy={trackAccountCopied}>
            <button className="btn btn-primary">Copy Details</button>
          </CustomClipboard>
        </div>
        {DETAIL_FIELDS.map((field, index) => (
          <div className="list-item" key={index}>
            <p className="left-field">{field?.label}</p>
            <p className="right-field">
              {accountDetails?.[field?.key?.replace(' ', '_')?.toLowerCase()] ?? '--'}
            </p>
          </div>
        ))}
      </div>
    </div>
  );
};

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

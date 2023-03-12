import { useEffect, useMemo } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//redux actions
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchAccountBalance } from 'merchant/reducers/b2bExports/actions';

//analytics
import { trackAccountCopied } from './analytics';

//components
import ShowWhen from 'merchant/components/ShowWhen';
import Instrument from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/Instrument';
import LoaderDots from 'common/ui/LoaderDots';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { Button } from '@razorpay/blade/components';

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
  const {
    accounts,
    data,
    isOpen,
    isLoading,
    setIsOpen,
    accountBalance,
    fetchAccountBalance,
    showNotification,
  } = props;

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

  useEffect(() => {
    if (accountBalance.error) {
      showNotification({
        type: 'error',
        message: accountBalance.errorMessage,
      });
    }
  }, [accountBalance.error]);

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
        <ShowWhen featureEnabled="enable_global_account">
          {accountBalance.data?.[data?.vaCurrency] ? (
            <div className="list-item">
              <div>
                <p className="info">Available Balance</p>
                <p>$ {accountBalance.data[data?.vaCurrency]?.amount}</p>
              </div>
              <div className="list-cta">
                <Button
                  variant="tertiary"
                  isLoading={accountBalance.isLoading}
                  onClick={() => fetchAccountBalance(data?.vaCurrency)}
                  size="small"
                  isFullWidth
                >
                  Refresh
                </Button>
              </div>
            </div>
          ) : (
            <div className="list-item">
              <p className="info">Check the available balance in your bank account.</p>
              <div className="list-cta">
                <Button
                  variant="primary"
                  isLoading={accountBalance.isLoading}
                  size="small"
                  isFullWidth
                  onClick={() => fetchAccountBalance(data?.vaCurrency)}
                >
                  Check Balance
                </Button>
              </div>
            </div>
          )}
        </ShowWhen>
        <div className="list-item">
          <p className="info">{data?.message}</p>
          <CustomClipboard value={textFormatter()} onCopy={trackAccountCopied}>
            <div className="list-cta">
              <Button isFullWidth variant="primary" size="small">
                Copy Details
              </Button>
            </div>
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

const mapStateToProps = (state) => ({
  accountBalance: state.b2bExportsAccountBalance,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchAccountBalance,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(InstrumentRow);

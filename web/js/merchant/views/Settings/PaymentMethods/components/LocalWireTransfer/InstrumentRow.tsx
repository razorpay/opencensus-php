import React, { useMemo } from 'react';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

//analytics
import { trackAccountCopied } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';

//types
import { InstrumentRowPropsInterface } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';

//constants
import {
  DETAIL_FIELDS,
  DEACTIVATED,
  VA_USD,
  RAZORPAY_SUPPORT_LINK,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';

//components
import ShowWhen from 'merchant/components/ShowWhen';
import { Button } from '@razorpay/blade/components';
import Instrument from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/Instrument';
import Toggle from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/Toggle';
import ErrorContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/ErrorContainer';
import CustomClipboard from 'common/ui/Clipboard/Custom'; // eslint-disable-line

const AccountBalance = lazy(
  () =>
    import(
      /* webpackChunkName: "AccountBalance" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/AccountBalance'
    ),
);

const InstrumentRow: React.FC<InstrumentRowPropsInterface> = (props) => {
  const { accounts, data, isOpen, setIsOpen } = props;

  //finds the current account from list of accounts
  const accountDetails = useMemo(
    () => accounts?.find((account) => account?.va_currency === data?.vaCurrency),
    [accounts],
  );

  //formats data for clipboard component
  const clipboardText = useMemo((): string => {
    return DETAIL_FIELDS.reduce((str, field) => {
      return `${str}\n${field?.label} = ${
        accountDetails?.[field?.key?.replace(' ', '_')?.toLowerCase()] ?? '--'
      }`;
    }, '');
  }, [accountDetails]);

  const isDeactivated = accountDetails?.status === DEACTIVATED;

  //dropdown handler
  const onToggleClick = () => {
    setIsOpen(isOpen === data.vaCurrency ? false : data.vaCurrency);
  };

  const getRightButton = () => {
    if (accountDetails && !isDeactivated) {
      return () => (
        <Toggle isOpen={isOpen} currency={data?.vaCurrency} onToggleClick={onToggleClick} />
      );
    }
    return false;
  };

  return (
    <div
      className={`local-wire-transfer-instrument${isOpen === data?.vaCurrency ? ' active' : ''}`}
    >
      <Instrument
        {...props}
        rightButton={getRightButton()}
        data={{
          ...data,
          status: isDeactivated ? DEACTIVATED : data.status,
        }}
      />
      {isDeactivated && (
        <ErrorContainer
          message={`Your ${accountDetails?.va_currency} Bank account has been deactivated`}
          action={
            <p>
              To activate your account, please{' '}
              <a target="_blank" href={RAZORPAY_SUPPORT_LINK} rel="noreferrer noopener">
                contact our support team
              </a>
            </p>
          }
        />
      )}
      {accountDetails && isOpen === data?.vaCurrency && (
        <div className="detail-list" data-testid="detail-list">
          <ShowWhen
            featureEnabled="enable_global_account"
            additionalCondition={() => data.vaCurrency === VA_USD}
          >
            <SuspenseWithLoader>
              <AccountBalance vaCurrency={data.vaCurrency} />
            </SuspenseWithLoader>
          </ShowWhen>
          <div className="list-item">
            <p className="info">{data?.message}</p>
            <CustomClipboard value={clipboardText} onCopy={trackAccountCopied}>
              <div className="list-cta">
                <Button variant="primary" size="small" isFullWidth>
                  Copy Details
                </Button>
              </div>
            </CustomClipboard>
          </div>
          {DETAIL_FIELDS.map((field, index) => (
            <div className="list-item" key={index}>
              <p className="left-field">{field.label}</p>
              <p className="right-field">
                {accountDetails?.[field.key.replace(' ', '_').toLowerCase()] ?? '--'}
              </p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
export default InstrumentRow;

import React from 'react';
import lazy from 'merchant/routes/LazyLoader';
import { isPlatformTransaction } from 'merchant/views/Transactions/v1/Payments/Utils/platformUtils';
import { useStore } from '@federated/apps/shell/commonStore';
import { Amount } from '@razorpay/blade/components';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';
import Definition from 'common/ui/Definition';
import { isOrgFeatureExist } from 'merchant/models/User';

export const PaymentFeeBreakdown = ({ payment, transfers }) => {
  const {
    merchantCurrency = 'INR',
    isRZPOrg,
    businessName = 'Razorpay',
  } = useStore((state) => ({
    merchantCurrency: state.session.user?.merchant?.currency,
    isRZPOrg: state.session.user?.isOrgRZP,
    businessName: state.session.org?.business_name,
  }));
  const showPlatformFee = isPlatformTransaction(transfers);

  const PlatformFeeDetails = showPlatformFee
    ? lazy(() => import('merchant/views/Transactions/v1/Payments/components/PlatformFeeDetails'))
    : null;

  const chargedFeeLabelText = () => {
    const hideRazorpayTextLink = isOrgFeatureExist('hide_razorpay_text_link');

    if (hideRazorpayTextLink) {
      return '';
    }
    return businessName;
  };

  /* This function handles specific flow for international payments
  other flows won't be affected
  1. fee bearer is customer
  2. currency is not INR
  3. payment is in authorized state
  4. fee_currency_amount is not null */
  const getPaymentFees = () => {
    let fee = payment?.fee ?? 0;
    if (
      payment?.fee_bearer === 'customer' &&
      payment?.currency !== 'INR' &&
      payment?.fee_currency_amount &&
      payment?.status === 'authorized'
    ) {
      fee = payment.fee_currency_amount;
    }
    return fee;
  };

  return showPlatformFee ? (
    <PlatformFeeDetails transfers={transfers} payment={payment} />
  ) : (
    <Definition>
      <Amount
        value={i18nifyConvertToMajorUnit(getPaymentFees(), merchantCurrency)}
        currency={merchantCurrency}
      />
      <span>
        {chargedFeeLabelText()} Fee -&nbsp;
        <Amount
          value={i18nifyConvertToMajorUnit(getPaymentFees() - payment.tax, merchantCurrency)}
          currency={merchantCurrency}
        />
      </span>
      <span>
        {/* Todo : should be changed for SG merchants */}
        {isRZPOrg ? 'GST' : 'Tax'} -{' '}
        <Amount
          value={i18nifyConvertToMajorUnit(payment.tax, merchantCurrency)}
          currency={merchantCurrency}
        />
      </span>
    </Definition>
  );
};

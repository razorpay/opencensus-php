import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import BankTransferDetails from './BankTransferDetails';

import CardIcon from 'assets/transactions/card.svg';
import UpiIcon from 'assets/transactions/upi.svg';
import TurboUpiIcon from 'assets/transactions/turbo-upi.svg';
import EmiIcon from 'assets/transactions/emi.svg';
import NetbankingIcon from 'assets/transactions/netbanking.svg';
import WalletIcon from 'assets/transactions/wallet.svg';
import { titleCase } from 'common/utils/rzp-utils';

import { IPaymentDetails } from './types';
import { useSplitzService } from 'common/splitz';
import { isPaymentV2RevampEnabled } from 'merchant/views/Transactions/v2/common/utils';

interface IPaymentMethod {
  payment: any;
  method: IPaymentDetails['method'];
  card: IPaymentDetails['card'];
  bank: IPaymentDetails['bank'];
  vpa: IPaymentDetails['vpa'];
  wallet: IPaymentDetails['wallet'];
}

function PaymentMethod({ payment, method, card, bank, vpa, wallet }: IPaymentMethod): JSX.Element {
  const splitz = useSplitzService();

  const { abExperiments } = splitz || { abExperiments: { toggle_payments_v2_revamp: undefined } };
  const isTxnV2ParityFeaturesEnabled = isPaymentV2RevampEnabled(abExperiments);

  const getPaymentMethod = () => {
    if (method === 'card') {
      return (
        <>
          {card?.international ? 'International' : 'Domestic'} {titleCase(card?.type)} card{' '}
          <span style={{ marginLeft: '8px' }}>
            (<img src={CardIcon} alt="card-icon" style={{ marginLeft: '4px' }} />
            xx{card?.last4})
          </span>
        </>
      );
    }

    if (method === 'emi') {
      const emiPlan = payment.emi_plan;
      return (
        <>
          EMI ({`  `}
          <img src={EmiIcon} alt="emi-icon" /> {` `}
          xx{card?.last4}, {emiPlan.duration} months | {emiPlan.rate / 100}% interest)
        </>
      );
    }

    if (method === 'netbanking') {
      const bankName = bank && bank !== 'netbanking' ? bank.toUpperCase() : bank;
      return (
        <>
          Net banking
          <span style={{ marginLeft: '8px' }}>
            (<img src={NetbankingIcon} alt="netbanking-icon" style={{ marginLeft: '4px' }} />{' '}
            {bankName && `${bankName} bank`})
          </span>
        </>
      );
    }

    if (method === 'upi') {
      // Turbo UPI
      if (payment?.upi_metadata?.flow === 'in_app') {
        return (
          <>
            <img src={TurboUpiIcon} alt="turbo-upi-icon" data-testid="turbo-upi" />
          </>
        );
      } else {
        return (
          <>
            UPI
            <span style={{ marginLeft: '8px' }}>
              ( <img src={UpiIcon} alt="upi-icon" /> {vpa && `${vpa}`})
            </span>
          </>
        );
      }
    }

    if (method === 'wallet') {
      const walletName = titleCase(wallet);
      return (
        <>
          Wallet
          <span style={{ marginLeft: '8px' }}>
            ( <img src={WalletIcon} alt="wallet-icon" style={{ marginLeft: '4px' }} /> {walletName}{' '}
            )
          </span>
        </>
      );
    }

    if (method === 'bank_transfer' && isTxnV2ParityFeaturesEnabled) {
      return (
        <>
          <BankTransferDetails paymentID={payment?.id} />
        </>
      );
    }

    if (method === 'app') {
      return <>Application</>;
    }

    if (method === 'cod') {
      return <>Cash on Delivery</>;
    }

    if (method === 'cardless_emi') {
      return <>Cardless EMI</>;
    }

    return <>{titleCase(method)}</>;
  };

  const authCode = payment?.acquirer_data?.auth_code;
  return (
    <Box display="flex" flexDirection="column">
      <Text variant="body" size="medium" weight="regular" color="surface.text.gray.normal">
        {getPaymentMethod()}
      </Text>
      {authCode ? (
        <Text
          marginTop="spacing.1"
          variant="body"
          size="medium"
          weight="regular"
          color="surface.text.gray.normal"
        >
          Auth Code: {authCode}
        </Text>
      ) : null}
    </Box>
  );
}

export default PaymentMethod;

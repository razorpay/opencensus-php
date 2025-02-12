import React from 'react';
import { Box, Text, Amount } from '@razorpay/blade/components';

import BankTransferDetails from './BankTransferDetails';

import CardIcon from 'assets/transactions/card.svg';
import TurboUpiIcon from 'assets/transactions/turbo-upi.svg';
import EmiIcon from 'assets/transactions/emi.svg';
import NetbankingIcon from 'assets/transactions/netbanking.svg';
import WalletIcon from 'assets/transactions/wallet.svg';
import { getEMI, titleCase } from 'common/utils/rzp-utils';

import { IPaymentDetails } from './types';
import { cardNetworkLogoMap } from './constants';
import { StyledPaymentMethodLogo } from './styled';
import UPITransferDetails from './UPITransferDetails';
import { CARD_SUB_TYPE_MAP } from 'merchant/views/Transactions/constants';

interface IPaymentMethod {
  payment: any;
  method: IPaymentDetails['method'];
  card: IPaymentDetails['card'];
  bank: IPaymentDetails['bank'];
  vpa: IPaymentDetails['vpa'];
  wallet: IPaymentDetails['wallet'];
  upi: IPaymentDetails['upi'];
}

function PaymentMethod({
  payment,
  method,
  card,
  bank,
  vpa,
  wallet,
  upi,
}: IPaymentMethod): JSX.Element {
  const shouldShowBankNameForFpx = method === 'fpx';

  const getPaymentMethod = () => {
    if (method === 'card') {
      const { name = '', widthToken = 9 } = card?.network
        ? cardNetworkLogoMap[card.network] || {}
        : {};
      return (
        <>
          {card?.sub_type ? CARD_SUB_TYPE_MAP[card.sub_type] : null}{' '}
          {card?.international ? 'International' : 'Domestic'} {titleCase(card?.type)} card{' '}
          <span style={{ marginLeft: '8px' }}>
            (<img src={CardIcon} alt="card-icon" style={{ marginLeft: '4px' }} />
            xx{card?.last4})
          </span>
          <Box display="flex" alignItems="center">
            <Text>{card?.issuer ? `${card.issuer}, ` : ''}</Text>
            <Text>{card?.network ? `${card.network} ` : ''}</Text>
            {name ? <StyledPaymentMethodLogo src={`/img/${name}`} widthToken={widthToken} /> : null}
          </Box>
          <Text>Name on card: {card?.name || '--'}</Text>
          {card?.id ? <Text>Card ID: {card.id}</Text> : null}
        </>
      );
    }

    if (method === 'emi') {
      const emiPlan = payment.emi_plan || {};
      const { duration, rate } = emiPlan;
      const emiAmount = duration && rate ? getEMI(payment.amount, duration, rate / 100) : 0;
      return (
        <>
          <Text>
            EMI on {card?.sub_type ? CARD_SUB_TYPE_MAP[card.sub_type] : null}{' '}
            {card?.international ? 'International' : 'Domestic'} {titleCase(card?.type)} card{' '}
          </Text>
          <Text>
            ({`  `}
            <img src={EmiIcon} alt="emi-icon" /> {` `}xx{card?.last4}, {emiPlan.duration} months |{' '}
            {emiPlan.rate / 100}% interest)
            {emiAmount && (
              <Amount
                size="medium"
                value={emiAmount / 100}
                currency={payment.currency || 'INR'}
                isAffixSubtle={false}
              />
            )}
          </Text>
          <Text>Name on card: {card?.name || '--'}</Text>
          {card?.id ? <Text>Card ID: {card.id}</Text> : null}
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
        return <UPITransferDetails paymentID={payment.id} vpa={vpa} upi={upi} />;
      }
    }

    if (method === 'wallet') {
      const walletName = titleCase(wallet);
      return (
        <Box display="flex" alignItems="center">
          Wallet ( <img src={WalletIcon} alt="wallet-icon" style={{ marginLeft: '4px' }} />{' '}
          {walletName} )
        </Box>
      );
    }

    if (method === 'bank_transfer') {
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

    if (shouldShowBankNameForFpx) {
      const bankName = bank ? bank.toUpperCase() : '';
      return `${bankName ? bankName : null} Fpx`;
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

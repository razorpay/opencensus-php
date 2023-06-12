import paymentsIcon from 'assets/reports/payments.svg';
import paymentLinksIcon from 'assets/reports/paymentlinks.svg';
import paymentsPageIcon from 'assets/reports/paymentsPage.svg';
import refundsIcon from 'assets/reports/refunds.svg';
import settlementsIcon from 'assets/reports/settlements.svg';
import ordersIcon from 'assets/reports/orders.svg';
import defaultIcon from 'assets/reports/default.svg';
import qrIcon from 'assets/reports/qr.svg';
import transactionIcon from 'assets/reports/transactions.svg';
import settlementOnDemandIcon from 'assets/reports/ondemandsettlement.svg';
import { OverviewLinksFnReturnType, OverviewLinksParams } from './types';

// based on exp val
export const availableLinks = ({
  isSchedulesEnabled,
}: OverviewLinksParams): OverviewLinksFnReturnType => {
  const links: OverviewLinksFnReturnType = [];

  if (isSchedulesEnabled) {
    links.push({
      type: 'schedule',
      label: 'Schedule Report',
    });
  }

  return [
    ...links,
    {
      type: 'download',
      label: 'Download Report',
    },
  ];
};

export const reportTypeIconsMap = {
  payments: paymentsIcon,
  scrooge_refunds: refundsIcon,
  settlements: settlementsIcon,
  orders: ordersIcon,
  rawsql: defaultIcon,
  settlement_ondemands: settlementOnDemandIcon,
  transactions: transactionIcon,
  qr_code: qrIcon,
  qr_codes: qrIcon,
  transfers: paymentsPageIcon,
  refunds: refundsIcon,
  disputes: defaultIcon,
  paymentlinksv2: paymentLinksIcon,
};

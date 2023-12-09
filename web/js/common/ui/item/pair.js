import { NavLink } from 'react-router-dom';

import { getAmount, getTime } from 'common/ui/item';
import { makeIdLink } from 'common/ui/item/id';
import { getIntervalCycle, subString, titleCase } from 'common/utils/rzp-utils';
import GatewayDataInfo from 'merchant/components/GatewayDataInfo';
import MaskedContact from 'merchant/components/Mask/Contact';
import MaskedEmail from 'merchant/components/Mask/Email';
import { RefundStatusLabel, OfferStatusLabel } from 'merchant/components/StatusLabel';
import { roles, agentRole, RBLRoles, RegistrationLinkRoles } from 'merchant/helpers/data';

import * as id from './id';

import * as items from './index';

const allRoles = {
  ...roles,
  ...agentRole,
  ...RBLRoles,
  ...RegistrationLinkRoles,
};

export const withClick =
  (onClick) =>
  ({ value, ...rest }) => {
    return {
      value: <span onClick={onClick}>{value}</span>,
      ...rest,
    };
  };

const textRightClass = 'text-right';

export const amount = {
  title: 'Amount',
  value: items.amount,
  columnClass: 'text-center',
};
export const amountRefunded = {
  title: 'Amount Refunded',
  value: items.amountRefunded,
  columnClass: textRightClass,
};
export const amountTransferred = {
  title: 'Amount Transferred',
  value: items.amountTransferred,
  columnClass: textRightClass,
};

export const customer = {
  title: 'Customer',
  value: (item) => (
    <div>
      <span class="contact">{item.contact}</span>
      <br />
      <span class="email">{item.email}</span>
    </div>
  ),
};

export const email = { title: 'Email', value: (item) => <MaskedEmail email={item.email} /> };
export const contact = {
  title: 'Contact',
  value: (item) => <MaskedContact contact={item.contact} />,
};
export const currency = { title: 'Currency', value: (item) => item.currency };
export const status = { title: 'Status', value: items.status };
export const public_status = {
  title: 'Status',
  value: (item) => <RefundStatusLabel status={item.public_status} />,
};
export const paidCount = {
  title: 'Paid Count',
  value: (item) => item.paid_count,
};
export const paidOn = { title: 'Paid On', value: items.createdAt };
export const createdAt = { title: 'Created At', value: items.createdAt };
export const createdAtShort = {
  title: createdAt.title,
  value: items.createdAtShort,
};
export const attempts = { title: 'Attempts', value: (item) => item.attempts };
export const receipt = { title: 'Receipt', value: (item) => item.receipt };
export const totalCount = { title: 'Count', value: (item) => item.total_count };

export const paymentId = { title: 'Payment Id', value: id.payment };
export const orderId = { title: 'Order Id', value: id.order };
export const rzpOrderId = { title: 'Razorpay Order Id', value: id.order };
export const refundId = { title: 'Refund Id', value: id.refund };
export const refundMode = {
  title: 'Mode',
  value: (item) => item.mode,
};
export const refundSpeed = {
  title: 'Speed',
  value: (item) => item.speed,
};
export const refundStatus = {
  title: 'Status',
  value: (item) => <RefundStatusLabel status={item.status} />,
};

export const enchancedRefundStatus = (showStatusInfo) => {
  return {
    title: 'Status',
    value: ({ status, gateway_data }) => {
      return (
        <div className="refund-status--label">
          <RefundStatusLabel status={status} />
          {showStatusInfo && <GatewayDataInfo gatewayData={gateway_data} />}
        </div>
      );
    },
  };
};

export const customerRefundId = {
  title: 'Refund Id',
  value: (item) => item.customer_refund_id,
};
export const settlementId = { title: 'Settlemt Id', value: id.settlement };
export const transferId = { title: 'Transfer Id', value: id.transfer };
export const reversalId = { title: 'Reversal Id', value: id.reversal };
export const source = { title: 'Source', value: id.source };
export const recipient = { title: 'Recipient Id', value: id.recipient };
export const batchId = { title: 'Batch Id', value: id.batch };
export const batchIdLink = { title: 'Batch Id', value: id.batchLink };
export const disputeId = { title: 'Dispute Id', value: id.dispute };
export const tokenId = { title: 'Token Id', value: id.token };
export const creditId = { title: 'Credit Id', value: id.credit };
export const submerchant = { title: 'Account Name', value: id.submerchant };
export const submerchantId = { title: 'Account ID', value: id.submerchantId };
export const earningId = { title: 'Earning ID', value: id.commission };
export const subventionId = { title: 'Subvention Id', value: id.commission };

export const creditsDescription = { title: 'Description', value: (item) => item.campaign };
export const creditID = { title: 'Credit Id', value: (item) => item.id };
export const credits = {
  title: 'Amount',
  value: getAmount('value'),
};

export const mapValues = (values) => (title) => {
  return { title, value: (item) => values[item.id] };
};

// this is notes order_id mixed
export const paymentOrder = (orders) => mapValues(orders)(orderId.title);

// Razorpay order_id
export const rzpPaymentOrder = (orders) => mapValues(orders)(rzpOrderId.title);

// Virtual Accounts
export const virtualAccountId = {
  title: 'Customer Identifier Id',
  value: makeIdLink('virtual_account'),
};
export const accountDescription = {
  title: 'Account Description',
  value: (item) => item.description,
};
export const amountPaid = {
  title: 'Amount Paid',
  columnClass: textRightClass,
  value: getAmount('amount_paid'),
};

// Subscriptions
export const subscriptionId = {
  title: 'Subscription Id',
  value: makeIdLink('subscription'),
};

export const customerId = {
  title: 'Customer Id',
  value: (item) => item.customer_id,
};

export const nextDueOn = {
  title: 'Next Due on',
  value: getTime('charge_at', 'MMM DD YYYY'),
};

// Plans
export const planId = {
  title: 'Plan Id',
  value: makeIdLink('plan'),
};

export const planName = {
  title: 'Plan Name',
  value: (item) => item.item.name,
};

export const planAmount = {
  title: 'Amount/Unit',
  value: getAmount('item.amount'),
  columnClass: textRightClass,
};

export const planBillingCycle = {
  title: 'Billing Cycle',
  value: (item) => getIntervalCycle(item.interval, item.period),
};

//Batch

export const batchName = {
  title: 'Batch Name',
  value: (item) => subString(item.name, 50),
};

export const role = {
  title: 'Role',
  value: (item) => (allRoles[item.role] || {}).label,
};

// Payment Button
export const buttonTitle = {
  title: 'Title',
  value: (item) => (
    <NavLink to={`/paymentbuttons/${item.id}/payments#paymentbuttons`}>{item.title}</NavLink>
  ),
};

export const subscriptionButtonTitle = {
  title: 'Title',
  value: (item) => (
    <NavLink to={`/subscription_buttons/${item.id}/payments#subscription_buttons`}>
      {item.title}
    </NavLink>
  ),
};

export const itemName = {
  title: 'Item Name',
  value: (item) =>
    item.payment_page_items.map((payment_page_item) => (
      <div key={payment_page_item.item.id} class="item-ellipsis">
        {payment_page_item.item.name}
      </div>
    )),
};
export const unitsSold = {
  title: 'Units Sold',
  value: (item) =>
    item.payment_page_items.map((payment_page_item) => (
      <div key={payment_page_item.id} class="item-ellipsis">
        {payment_page_item.quantity_sold}
      </div>
    )),
};

// Offers
export const offerId = {
  title: 'Offer Id',
  value: makeIdLink('offer'),
};
export const OfferIdWithoutLink = {
  title: 'Offer Id',
  value: (item) => item[`${item.entity === 'offer' ? '' : `offer_`}id`],
};
export const offerTitle = {
  title: 'Title',
  value: (item) => item.name,
};
export const startOn = {
  title: 'Start On',
  value: getTime('starts_at', 'DD MMM YYYY, hh:mm a'),
};
export const endsOn = {
  title: 'End On',
  value: getTime('ends_at', 'DD MMM YYYY, hh:mm a'),
};
export const offerStatus = {
  title: 'Status',
  value: (item) => <OfferStatusLabel status={item.active ? 'enabled' : 'disabled'} />,
};

export const promotionType = {
  title: 'Promotion Type',
  value: (item) => {
    if (item.emi_subvention && item.percent_rate) return 'Low Cost EMI';
    if (item.emi_subvention) return 'No Cost EMI';
    if (item.product_type === 'subscription') return 'Subscription';

    return 'Discounts & Cash Back';
  },
};

export const paymentMethod = {
  title: 'Payment Method',
  value: (item) => item.payment_method,
};

export const qrCodeId = {
  title: 'QR Code ID',
  value: id.qrCode,
};

export const description = {
  title: 'Description',
  value: (item) => item.description || '-',
};

export const qrUsage = {
  title: 'QR Usage',
  value: (item) => <div class="qr_usage">{titleCase(item.usage) || '-'}</div>,
};

export const amountReceived = {
  title: 'Amount Received',
  value: getAmount('payments_amount_received'),
};

export const storeProductId = {
  title: 'Product Id',
  value: id.storeProduct,
};

export const paymentReceiverType = {
  title: 'Receiver Type',
  value: (item) => {
    return item?.notes?.receiver_type === 'offline' ? 'Offline' : 'Online';
  },
};

export const arn = {
  title: 'RRN/ARN',
  value: (item) => {
    const { arn, rrn, utr } = item?.acquirer_data ?? {};
    return arn || rrn || utr || '-';
  },
};

import moment from 'moment';
import CustomerDetails from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer/components/CustomerDetails';

import {
  RISK_TIER_COLOR_MAPPING,
  RISK_TIER_LABEL,
  DATE_FORMAT,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

export const receipt = {
  title: 'Receipt',
  value: (item) => (item.receipt ? `${item.receipt}` : '-'),
};

export const date = {
  title: 'Date',
  value: (item) => {
    if (!item.created_at) return '-';

    const date = new Date(item.created_at * 1000);
    const fullDate = moment(date).format(DATE_FORMAT);

    return fullDate;
  },
};

export const rtoRisk = {
  title: 'RTO Risk',
  value: (item) =>
    item.risk_tier ? (
      <span className={`status-label${RISK_TIER_COLOR_MAPPING[item.risk_tier]}`}>
        {RISK_TIER_LABEL[item.risk_tier] ?? 'high Risk'}
      </span>
    ) : (
      '-'
    ),
};

export const amount = {
  title: 'Order Amount',
  value: (item) => (item?.amount >= 0 ? `₹ ${item.amount / 100}` : '-'),
};

export const customerDetails = {
  title: 'Customer Details',
  value: (item) => <CustomerDetails item={item} />,
};

export const discount = {
  title: 'Discount',
  value: (item) => {
    const discount = item?.promotions[0]?.value || item?.magic_payment_link?.discount;
    return discount >= 0 ? `₹ ${discount / 100} off` : 'N/A';
  },
  columnClass: 'discount',
};

export const expiredOn = {
  title: 'Expired On',
  value: (item) => {
    const { expired_on } = item?.magic_payment_link;
    if (!expired_on) return 'N/A';

    const date = new Date(expired_on * 1000);
    const fullDate = moment(date).format(DATE_FORMAT);

    return fullDate;
  },
  columnClass: 'expire-date',
};

export const riskReason = {
  title: 'Risk Reasons',
  value: (item) => (
    <div className="risk-reason-container">
      {item?.rto_reasons?.map((reason, index) => (
        <div className="reason-box" key={index}>
          <div className="col-sm-1 warning-icon">
            <i className="i i-outlined-caution" />
          </div>
          <div className="col-sm-11 warning-info">{reason}</div>
        </div>
      ))}
    </div>
  ),
};

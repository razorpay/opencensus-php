import { Link } from 'react-router-dom';
import Input from 'common/new-ui/Input';
import moment from 'moment';
import ActionToolbar from 'merchant/views/MagicCheckout/CODOrdersTab/common/ActionToolbar';
import {
  RISK_TIER_COLOR_MAPPING,
  REVIEW_STATUS_MAP,
  RISK_TIER_LABEL,
  DATE_FORMAT,
  REVIEW_ORDERS_CATEGORY,
  REVIEWED_ORDERS_CATEGORY,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

export const orderId = (onSelectId, isChecked) => ({
  title: 'Razorpay Order Id',
  value: (item) => (
    <div className="orderId-container">
      {!item.review_status ||
      REVIEW_ORDERS_CATEGORY.includes(item.review_status) ||
      item.review_status === REVIEWED_ORDERS_CATEGORY.hold[0] ? (
        <>
          <Input.Check
            autoRender
            id={item.id}
            onChange={(e) => onSelectId(e)}
            value={item.id}
            checked={isChecked?.has(item.id)}
            disabled={item.review_status && item.review_status !== REVIEWED_ORDERS_CATEGORY.hold[0]}
          />
          {item?.id && !isChecked?.size ? (
            <Link to={`?order_id=${item.id}`}>
              <p className="Input-desc">{item.id}</p>
            </Link>
          ) : (
            <p className="Input-desc">{item.id}</p>
          )}
        </>
      ) : (
        <Link to={`?order_id=${item?.id}`}>
          <p className="Input-desc">{item?.id}</p>
        </Link>
      )}
    </div>
  ),
});

export const razorpayId = { title: 'Receipt', value: (item) => item.receipt ?? '-' };

export const date = (sortDate) => ({
  title: () => (
    <div className="date-container">
      Date
      <div className="container-actions">
        <i
          className="i i-arrow-up"
          data-testid="date-ascend-arrow"
          onClick={() => sortDate('descend')}
        />
        <i
          className="i i-arrow-down"
          data-testid="date-descend-arrow"
          onClick={() => sortDate('ascend')}
        />
      </div>
    </div>
  ),
  value: (item) => {
    if (!item.created_at) return '-';

    const date = new Date(item.created_at * 1000);
    const fullDate = moment(date).format(DATE_FORMAT);

    return fullDate;
  },
});

export const rtoRisk = (sort) => ({
  title: () => (
    <div className="risk-container">
      RTO Risk
      <div className="container-actions">
        <i className="i i-arrow-up" data-testid="riskTier-ascend" onClick={() => sort('descend')} />
        <i
          className="i i-arrow-down"
          data-testid="riskTier-descend"
          onClick={() => sort('ascend')}
        />
      </div>
    </div>
  ),
  value: (item) => {
    const riskLabel = item.risk_tier;
    return riskLabel ? (
      <span className={`status-label${RISK_TIER_COLOR_MAPPING[riskLabel]}`}>
        {RISK_TIER_LABEL[riskLabel]}
      </span>
    ) : (
      '-'
    );
  },
});

export const actions = (onReview, isChecked) => ({
  title: 'Actions',
  value: (item) => {
    const reviewedClass =
      item.review_status === REVIEWED_ORDERS_CATEGORY.approved[0] ||
      item.review_status === REVIEWED_ORDERS_CATEGORY.canceled[0]
        ? ` status-label ${item.review_status}-label`
        : '';
    return !item.review_status || item.review_status === REVIEWED_ORDERS_CATEGORY.hold[0] ? (
      <ActionToolbar
        item={item}
        onReview={onReview}
        disableActions={isChecked?.size}
        hideHold={item?.review_status}
      />
    ) : (
      <span className={`review-status-label${reviewedClass}`}>
        {REVIEW_STATUS_MAP[item?.review_status]}
      </span>
    );
  },
});

export const reviewedBy = {
  title: 'Reviewed By',
  value: (item) => {
    if (!item.reviewed_by) {
      return '-';
    }

    return item.reviewed_by !== 'automation_intelligence@razorpay.com'
      ? item.reviewed_by
      : 'Automation';
  },
  columnClass: 'reviewed-by-col',
};

import { getFormattedAmountNew } from 'common/utils/rzp-utils';

export const restrictedFeatures = [
  'disable_ondemand_for_loc',
  'disable_ondemand_for_card',
  'disable_ondemand_for_loan',
];

export const featureName = {
  disable_ondemand_for_loc: 'LOC',
  disable_ondemand_for_card: 'Card',
  disable_ondemand_for_loan: 'Loan',
};

export const settleNowRestrictionMsgFn = (
  settlementRestricted,
  ondemand_restrictions,
  isOnDemandDisabled,
  user,
) => {
  if (!settlementRestricted) return null;
  const {
    attempts_left,
    settlable_amount,
    max_amount_limit,
    settlements_count_limit,
  } = ondemand_restrictions.data;
  if (isOnDemandDisabled()) {
    const restrictedItem = restrictedFeatures
      .filter((feat) => user.isFeatureEnabled(feat))
      .map((feat) => featureName[feat]);

    const renderFeatureComponent = () => {
      return restrictedItem.map((item, i) => {
        if (i === restrictedItem.length - 1 && i != 0) {
          return (
            <>
              & <span className="highlight-tooltip"> {item}.</span>
            </>
          );
        } else {
          return (
            <span className="highlight-tooltip">
              {item}
              {i === restrictedItem.length - 1 ? '.' : i === restrictedItem.length - 2 ? ' ' : ', '}
            </span>
          );
        }
      });
    };
    return (
      <div className="disable-ondemand-msg">
        <span className="pr-5">
          On-demand Instant Settlements have been disabled because you have delayed the repayments
          on
        </span>
        {renderFeatureComponent()}
        <br /> <br />
        Please complete the repayments to re-enable Instant Settlements.
      </div>
    );
  } else if (!attempts_left && !settlable_amount) {
    return `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
      max_amount_limit,
      true,
    )} for the day.`;
  } else if (!attempts_left) {
    return `You've already settled your maximum allowed limit of ${settlements_count_limit} times for the day.`;
  } else if (!settlable_amount) {
    return `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
      max_amount_limit,
      true,
    )} for the day.`;
  }
  return null;
};

export const TIMELINE_EVENTS = {
  PAYMENT_CAPTURED: 'PAYMENT_CAPTURED',
  REFUND_PROCESSED: 'REFUND_PROCESSED',
  SCHEDULE_INFO: 'SCHEDULE_INFO',
  SETTLEMENT_INFO: 'SETTLEMENT_INFO',
  HOLIDAY_INFO: 'HOLIDAY_INFO',
};

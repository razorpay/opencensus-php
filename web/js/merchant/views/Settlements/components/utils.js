import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import LocRepaymentTooltip from 'merchant/views/Capital/CashAdvanceNudges/components/LocRepaymentTooltip';

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
  isNodalAccountBalanceLow,
  isEsOnDemandBlocked,
) => {
  if (!settlementRestricted) return null;
  const { attempts_left, settlable_amount, max_amount_limit, settlements_count_limit } =
    ondemand_restrictions.data;
  if (isEsOnDemandBlocked) {
    return 'Temporary Downtime: Settle Now Feature Unavailable Due to Technical Issues';
  } else if (isOnDemandDisabled()) {
    const restrictedItem = restrictedFeatures
      .filter((feat) => user.isFeatureEnabled(feat))
      .map((feat) => featureName[feat]);

    const showRepaymentTooltip =
      restrictedItem?.length === 1 && restrictedItem?.[0] === featureName.disable_ondemand_for_loc;
    if (showRepaymentTooltip) {
      return <LocRepaymentTooltip />;
    }

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
            <span className="highlight-tooltip" key={`${item}_${i}`}>
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
  } else if (isNodalAccountBalanceLow) {
    return 'We are temporarily offline. Will be back soon!';
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

export const SETTLEMENT_SLA_IN_HOURS = 3;

export const SETTLEMENT_RETRY_SLA_IN_HOURS = 7;

export const HEADING_INFO = {
  CURRENT_BALANCE:
    'This is the total amount that is due to be deposited in your bank account after deduction of taxes, platform fees, any other applicable charges, and adjustment of refunds and credits',
  SETTLEMENT_DUE_TODAY:
    'This is the amount initiated for deposit into your bank account and is in processing',
  PREVIOUS_SETTLEMENT:
    'This is the last amount initiated for deposit into your bank account (the settlement may have either processed or failed)',
  UPCOMING_SETTLEMENT:
    'This is the amount that’ll be deposited into your bank account next as per your settlement cycle.',
};

export const ALERT_INTENT = {
  NOTICE: 'notice',
  NEGATIVE: 'negative',
};

export const SETTLEMENT_STATUS = {
  CREATED: 'created',
  INITIATED: 'initiated',
  FAILED: 'failed',
  PROCESSED: 'processed',
};

export const BADGE_INFO = {
  CREATED: 'The due settlement is sent to the bank for further processing',
  FAILED: 'When the due settlement could not be deposited in your bank account',
  PROCESSED:
    'The due settlement deposit is successful and complete from our end (The settlement amount may take 2-3 hours to reflect in your account depending on the bank)',
  DELAYED: 'The settlement processing is taking more than the usual time',
  BLOCKED: 'All upcoming settlements are on-hold for your account',
};

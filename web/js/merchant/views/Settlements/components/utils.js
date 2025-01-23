import { isExperimentEnabled } from 'common/splitz/utils';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { isOrgFeatureExist } from 'merchant/models/User';
import LocRepaymentTooltip from 'merchant/views/Capital/CashAdvanceNudges/components/LocRepaymentTooltip';

export const getSettlementTimeFormat = (defaultFormat = 'DD MMM YYYY, hh:mm:ss a') => {
  const orgFeatureEnabled = isOrgFeatureExist('hide_settlement_time');
  return orgFeatureEnabled ? 'DD MMM YYYY' : defaultFormat;
};

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

//TODO: Use Proper variable names and follow SPOC(Exisiting function).Don't add anything new here, instead use getTooltipContent.
export const settleNowRestrictionMsgFn = (
  settlementRestricted,
  ondemand_restrictions,
  isOnDemandDisabled,
  user,
  isNodalAccountBalanceLow,
) => {
  if (!settlementRestricted) return null;
  const { attempts_left, settlable_amount, max_amount_limit, settlements_count_limit } =
    ondemand_restrictions?.data || {};
  if (isOnDemandDisabled()) {
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

export function filterAndTransformInPersonSchedule(obj) {
  const transformed = {};

  for (const [key, value] of Object.entries(obj)) {
    if (key.includes('in_person')) {
      const newKey = key
        .split(':')
        .filter((part) => part !== 'in_person')
        .join(' ');

      transformed[newKey] = value;
    }
  }

  return transformed;
}

export const isSettlementSOHBlockEnabled = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { settlements_soh_block: undefined } };
  if (!abExperiments?.settlements_soh_block) return false;
  return isExperimentEnabled(abExperiments.settlements_soh_block);
};

export const TIMELINE_EVENTS = {
  PAYMENT_CAPTURED: 'PAYMENT_CAPTURED',
  REFUND_PROCESSED: 'REFUND_PROCESSED',
  SCHEDULE_INFO: 'SCHEDULE_INFO',
  SETTLEMENT_INFO: 'SETTLEMENT_INFO',
  HOLIDAY_INFO: 'HOLIDAY_INFO',
};

export const SETTLEMENT_HOLD_FEATURE = {
  FOH: 'FOH', //FOH: Funds on Hold
  HOLD: 'SOH', //SOH: Settlement on Hold
  BLOCK: 'Block', //BLOCK: Settlement Blocked
  DISABLED_LIVE: 'DISABLED_LIVE', //DISABLED_LIVE: Live is disabled in case of RISK FOH
};

export const SETTLEMENT_HOLD_CTA_TEXT = {
  UPDATE_BANKACC: 'Update bank details',
  CONTACT_SUPPORT: 'Contact Support',
};

export const DEFAULT_SETTLEMENT_TITLE = {
  SOH: 'Update Bank Account Details to resume settlements',
  SOH_POST_BA_UPDATE: 'Your bank details have been successfully updated and are under review',
  SOH_CONTACT_SUPPORT: 'Contact support to resume settlements',
  FOH: 'Contact support to resume settlements for your account',
  BLOCK: 'Contact support to resume settlements for your account',
};

export const DEFAULT_SETTLEMENT_SUB_TITLE = {
  SOH: 'Your settlements are on-hold as we’ve encountered a few issues with your given bank account',
  SOH_POST_BA_UPDATE: 'Settlements will be retried after your bank account has been verified.',
  SOH_CONTACT_SUPPORT:
    'Your settlements are on-hold as we’ve encountered a few issues with your given bank account',
  FOH: 'Your settlements are on-hold as we’ve noticed unusual activity in your account',
  BLOCK: 'Your settlements are on-hold as per your request',
};

export const SETTLEMENT_HOLD_MESSAGE = {
  SOH: 'Update your bank account details to resume settlements',
  FOH: 'Contact support to resume settlements',
  BLOCK: 'Contact support to resume settlements',
};

export const SETTLEMENT_BANNER_BANK_UPDATE_MESSAGE = {
  TITLE: 'Your bank details have been successfully updated and are under review.',
  SUB_TITLE: 'Settlements will be retried after your bank account has been verified.',
};

export const SETTLEMENT_HOLD_PRIMARY_TEXT = 'Your settlements are on-hold';

export const SETTLEMENT_HOLD_BANK_UPDATE_MESSAGE = {
  HEADING: 'Your settlements are on hold, expect an update within 48 hours',
  STEP1: 'Bank account updated successfully and is under review',
  STEP2: 'Once verified, your settlements will be retried.',
};

export const SETTLEMENT_HOLD_CONTACT_SUPPORT_MESSAGE = {
  SUB_TITLE:
    "Please reach out to our support team, and we'll assist you in resolving this issue and getting your settlements back on track.",
};
export const SETTLEMENTS_BLOCK_TITLE =
  'Your settlements are on-hold because of your request raised with Razorpay.';

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
  INFORMATION: 'information',
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

export const isBlocked = (feature) => {
  return feature?.hold.status || feature?.global_hold_config.status || feature?.block?.status;
};

export const FOH_DISABLED_LIVE_TEXT =
  'This means that you will no longer be able to accept new transactions.';

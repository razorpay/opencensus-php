import { Checkbox, CheckboxGroup } from '@razorpay/blade/components';

import { findBy } from 'common/utils/rzp-utils';
import { WorkSection, WorkFlow } from 'merchant/components/WorkFlow';
import {
  filterNoCostTenures,
  getAllLowCostTenures,
} from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import { useLowCostOfferExperiment } from 'merchant/views/Offers/New/Screens/NoCostEMI/useLowCostOfferExperiment';
import { emiDurationString } from 'merchant/views/Offers/New/helpers';
import {
  OFFER_TYPE_LABELS,
  PAYMENT_NETWORK_MAP,
  BANK_MAP,
  WALLET_MAP,
  PAYMENT_METHODS,
  DISCOUNT_TYPES,
  APPLICABLE_ON_OPTIONS,
  REDEMPTION_TYPE_OPTIONS,
} from 'merchant/views/Offers/constants';
const summarizePaymentMethodsData = (paymentMethod, cardType, paymentNetwork, issuer) => {
  switch (paymentMethod) {
    case PAYMENT_METHODS.Card:
      return `All ${wordWithSpace(BANK_MAP[issuer])}${wordWithSpace(
        PAYMENT_NETWORK_MAP[paymentNetwork],
      )}${wordWithSpace(cardType)}Cards`;

    case PAYMENT_METHODS.NetBanking:
      return `Netbanking on all ${wordWithSpace(BANK_MAP[issuer])}accounts`;

    case PAYMENT_METHODS.Wallet:
      return `All ${wordWithSpace(WALLET_MAP[issuer])}wallets`;

    case PAYMENT_METHODS.UPI:
      return `UPI`;

    case PAYMENT_METHODS.EMI:
      return `EMI on all ${wordWithSpace(BANK_MAP[issuer])}${wordWithSpace(
        PAYMENT_NETWORK_MAP[paymentNetwork],
      )}${wordWithSpace(cardType)}cards`;

    case PAYMENT_METHODS.CardLessEmi:
      return `Cardless EMI`;

    case PAYMENT_METHODS.PayLater:
      return `Paylater`;

    default:
      return wordWithSpace(BANK_MAP[issuer]);
  }
};

export default function OverView(props) {
  const {
    currencySymbol,
    values: {
      type,
      terms,
      display_text,
      discount_type,
      flat_cashback,
      min_amount,
      percent_rate,
      max_cashback,
      issuer,
      payment_method,
      payment_network,
      payment_method_type,
      emi_durations,
      applicable_on,
      low_cost_emi,
      starts_at,
      ends_at,
      redemption_type,
    },
    values,
    setFieldTouched,
    setFieldValue,
    errors,
    touched,
  } = props;
  const handleFormChange = (name, value) => {
    setFieldTouched(name);
    setFieldValue(name, value);
  };
  const { isLowCostEnabled } = useLowCostOfferExperiment();

  const DiscountTypeHeading = `${discount_type.split('_').join(' ')} discount`;
  const OfferValidityDesc = `${starts_at ? starts_at.format('DD-MM-YY, hh:mm a') : 'Valid till'} ${
    ends_at ? ends_at.format('DD-MM-YY, hh:mm a') : '--'
  }`;

  let discountReview, applicableOn, redemptionType;
  if (discount_type === DISCOUNT_TYPES.FLAT) {
    discountReview = `Flat discount of  ${currencySymbol} ${flat_cashback} on a minimum purchase of  ${currencySymbol} ${min_amount}`;
  }

  if (discount_type === DISCOUNT_TYPES.PERCENT) {
    discountReview = `${percent_rate}% discount upto  ${currencySymbol} ${max_cashback} on a minimum purchase of  ${currencySymbol} ${min_amount}`;
  }

  if (discount_type === DISCOUNT_TYPES.NO_COST_EMI) {
    if (isLowCostEnabled && low_cost_emi && low_cost_emi.length) {
      const filteredTenures = filterNoCostTenures(emi_durations, low_cost_emi);
      if (filteredTenures && filteredTenures.length) {
        discountReview = `${emiDurationString(filteredTenures)}.`;
      }
    } else {
      discountReview = `${emiDurationString(emi_durations)}.`;
    }
  }

  if (applicable_on) {
    applicableOn = findBy(APPLICABLE_ON_OPTIONS, 'name', applicable_on).label;
  }

  if (redemption_type) {
    redemptionType = findBy(REDEMPTION_TYPE_OPTIONS, 'name', redemption_type).label;
  }
  errors.creation_terms_accepted = validateTermsAndConditions(values.creation_terms_accepted);
  return (
    <div class="Subscription--New-review">
      <div class="Payments">
        <WorkFlow>
          <WorkSection heading="Description">
            <DualColumnTable heading="Display Text">{display_text}</DualColumnTable>

            <DualColumnTable heading="Offer Type">{OFFER_TYPE_LABELS[type]}</DualColumnTable>

            <DualColumnTable heading="Offer Terms">{terms}</DualColumnTable>
          </WorkSection>

          <WorkSection heading="Discount Type">
            {discountReview ? (
              <DualColumnTable heading={DiscountTypeHeading}>{discountReview}</DualColumnTable>
            ) : (
              ''
            )}
            {isLowCostEnabled && low_cost_emi && low_cost_emi.length ? (
              <DualColumnTable heading="Low Cost EMI discount">
                {emiDurationString(getAllLowCostTenures(low_cost_emi))}
              </DualColumnTable>
            ) : (
              ''
            )}
          </WorkSection>

          <WorkSection heading="Applicable On">
            {applicableOn && <DualColumnTable heading="Applies to">{applicableOn}</DualColumnTable>}
            <DualColumnTable heading="Payment Method">
              {summarizePaymentMethodsData(
                payment_method,
                payment_method_type,
                payment_network,
                issuer,
              )}
            </DualColumnTable>
          </WorkSection>

          <WorkSection heading="Offer Validation">
            <DualColumnTable heading="Offer Validity">{OfferValidityDesc}</DualColumnTable>
            {redemptionType && (
              <DualColumnTable heading="Redemption Type">{redemptionType}</DualColumnTable>
            )}
          </WorkSection>
        </WorkFlow>

        <CheckboxGroup
          isRequired
          necessityIndicator="required"
          label="Terms and Conditions"
          marginBottom="spacing.3"
          isDisabled={props.isFormLocked}
          size="medium"
          name="creation_terms_accepted"
          value={values.creation_terms_accepted}
          onChange={({ name, values }) => {
            handleFormChange(name, values?.[0]);
          }}
          validationState={
            touched.creation_terms_accepted && errors?.creation_terms_accepted ? 'error' : 'none'
          }
          errorText={errors?.creation_terms_accepted}
        >
          <Checkbox value="1">
            I understand that the discount/cashback given in this offer will be borne by me and not
            Razorpay.
          </Checkbox>
        </CheckboxGroup>
      </div>
    </div>
  );
}

function DualColumnTable({ heading, children, columnRatio = 0.25 }) {
  return (
    <div
      className="dual-column-table"
      style={{ gridTemplateColumns: `${columnRatio}fr ${1 - columnRatio}fr` }}
    >
      {
        <span>
          {heading}
          {heading && ':'}
        </span>
      }
      <span>{children}</span>
    </div>
  );
}

export function wordWithSpace(word) {
  return word ? `${word} ` : '';
}

function validateTermsAndConditions(val) {
  if (!val || val == '') {
    return 'Please accept Terms and Conditions';
  }
  return false;
}

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
  ALL_PRE_PAID_PAYMENT_METHODS,
  OFFER_TYPES,
} from 'merchant/views/Offers/constants';

const getPaymentMethodDisplayString = (paymentMethod, cardType, paymentNetwork, issuer) => {
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

const summarizePaymentMethodsData = (
  instruments,
  paymentMethod,
  cardType,
  paymentNetwork,
  issuer,
) => {
  return (instruments || [paymentMethod])
    .filter((instrument) => instrument !== ALL_PRE_PAID_PAYMENT_METHODS)
    .map((instrument) =>
      getPaymentMethodDisplayString(instrument, cardType, paymentNetwork, issuer),
    )
    .join(', ');
};

const generateAdditionalOfferString = (values, currencySymbol, isLowCost = false) => {
  if (!values.additional_offer) {
    return '';
  }

  let discountString = '';

  let tenures = values.emi_durations || [];
  const lowCostTenures = values.low_cost_emi?.map((item) => item.tenure) || [];
  let lowCostTenuresSet = new Set(lowCostTenures);

  if (isLowCost) {
    tenures = lowCostTenures;
  } else {
    tenures = tenures.filter((tenure) => !lowCostTenuresSet.has(tenure));
  }

  if (!tenures.length) {
    return '';
  }

  const tenuresApplicable = values.tenures_applicable || [];

  const tenureHasAdditionalOffer = tenures.some((tenure) => tenuresApplicable.includes(tenure));

  if (!tenureHasAdditionalOffer) {
    return '';
  }

  if (values.additional_offer_discount_type === 1) {
    // Flat discount
    discountString = `Flat discount of ${currencySymbol}${values.flat_cashback} on a minimum purchase of ${currencySymbol}${values.min_amount}`;
  } else if (values.additional_offer_discount_type === 2) {
    // Percentage discount
    discountString = `${values.percent_rate}% discount upto ${currencySymbol}${values.max_cashback} on a minimum purchase of ${currencySymbol}${values.min_amount}`;
  }

  if (tenures.length > 0) {
    // Format tenure string based on number of tenures
    let tenureString;
    if (tenures.length === 1) {
      tenureString = `${tenures[0]}`;
    } else if (tenures.length === 2) {
      tenureString = `${tenures[0]} & ${tenures[1]}`;
    } else {
      // For 3 or more tenures, join all but last with comma, and last with &
      const allButLast = tenures.slice(0, -1).join(', ');
      const last = tenures[tenures.length - 1];
      tenureString = `${allButLast} & ${last}`;
    }

    discountString += `; ${tenureString} month${tenures.length > 1 ? 's' : ''} tenure${
      tenures.length > 1 ? 's' : ''
    }`;
  }

  return discountString;
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
      selectedInstruments,
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

  const handleTermsAndConditions = (evt) => {
    const { name, values } = evt;
    handleFormChange(name, values);
  };

  const getLowCostEMIString = (low_cost_emi) => {
    if (values.additional_offer) {
      const additionalOfferString = generateAdditionalOfferString(values, currencySymbol, true);
      if (additionalOfferString) {
        return `${additionalOfferString}.`;
      }
    }

    return emiDurationString(getAllLowCostTenures(low_cost_emi));
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
    // Add additional offer string if present in form data
    if (values.additional_offer) {
      const additionalOfferString = generateAdditionalOfferString(values, currencySymbol);
      if (additionalOfferString) {
        discountReview = ` ${additionalOfferString}`;
      }
    } else if (isLowCostEnabled && low_cost_emi && low_cost_emi.length) {
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
    <div className="Subscription--New-review">
      <div className="Payments">
        <WorkFlow>
          <WorkSection heading="Description">
            <DualColumnTable heading="Display Text">{display_text}</DualColumnTable>

            <DualColumnTable heading="Offer Type">
              {values.additional_offer
                ? OFFER_TYPE_LABELS[OFFER_TYPES.Clubbed]
                : OFFER_TYPE_LABELS[type]}
            </DualColumnTable>

            <DualColumnTable heading="Offer Terms">{terms}</DualColumnTable>
          </WorkSection>

          <WorkSection heading="Applicable On">
            {applicableOn && <DualColumnTable heading="Applies to">{applicableOn}</DualColumnTable>}
            <DualColumnTable heading="Payment Method">
              {summarizePaymentMethodsData(
                selectedInstruments,
                payment_method,
                payment_method_type,
                payment_network,
                issuer,
              )}
            </DualColumnTable>
          </WorkSection>
          <WorkSection heading="Discount Type">
            {discountReview ? (
              <DualColumnTable heading={DiscountTypeHeading}>{discountReview}</DualColumnTable>
            ) : (
              ''
            )}
            {isLowCostEnabled && low_cost_emi && low_cost_emi.length ? (
              <DualColumnTable heading="Low Cost EMI discount">
                {getLowCostEMIString(low_cost_emi)}
              </DualColumnTable>
            ) : (
              ''
            )}
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
            handleFormChange(name, values);
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
  if (!val || !val.length) {
    return 'Please accept Terms and Conditions';
  }
  return false;
}

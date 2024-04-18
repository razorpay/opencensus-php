import { getUser } from 'merchant/store';
import { cardlessEmiList, emiBanksList } from './constants';
import { Instrument } from './type';
import { convertToMinorUnit } from '@razorpay/i18nify-js/currency';

const user = getUser();

export const generateEligibilityTrackPayload = (payload: Instrument[]) => {
  if (!payload) return [];

  return payload?.map((instrument) => {
    return {
      method: instrument.method,
      instrument: instrument.provider || instrument.issuer || 'NA',
      eligibility_status: instrument.eligibility.status,
      ineligibility_code: instrument.eligibility.error?.code || 'NA',
      ineligibility_desc: instrument.eligibility.error?.description || 'NA',
    };
  });
};

export interface Error {
  code: string;
  description: string;
  source: string;
  step: string;
  reason: string;
  action: string;
}

export type TRACK_ERROR_PAYLOAD = {
  failure_reason_code: string;
  failure_reason_desc: string;
};

export const generateEligibilityErrorPayload = (error): TRACK_ERROR_PAYLOAD => {
  return {
    failure_reason_code: error?.status_code,
    failure_reason_desc: error?.errors[0] || error?.description,
  };
};

export const checkCardlessEmiEligibility = (provider, eligibilityData) => {
  if (!eligibilityData?.instruments) {
    return false;
  }
  return eligibilityData?.instruments.some((instrument) => {
    return (
      instrument.method === 'cardless_emi' &&
      instrument.provider.toLowerCase() === provider.toLowerCase() &&
      instrument.eligibility.status === 'ineligible'
    );
  });
};

export const checkEmiEligibility = (issuer, type, eligibilityData): boolean => {
  if (!eligibilityData?.instruments) {
    return false;
  }

  return eligibilityData?.instruments.some((instrument) => {
    return (
      instrument.method === 'emi' &&
      instrument.issuer.toLowerCase() === issuer.toLowerCase() &&
      instrument.type === type &&
      instrument.eligibility.status === 'ineligible'
    );
  });
};

export const calculateEmi = (principle, duration, rate) => {
  if (!rate) {
    return Math.ceil(principle / duration);
  }
  rate /= 1200;
  const multiplier = (1 + rate) ** duration;
  let emiValue = (principle * rate * multiplier) / (multiplier - 1);
  emiValue = Number(emiValue.toFixed(2));

  return emiValue;
};

/**
 * Remove decimals from the amount if decimals are zeroes.
 * @param {String} decimals Number of decimal digits
 * @param {String} separator Decimal point
 *
 * @return {Function}
 *  @param {String} amount Amount with decimals
 *
 *  @return {String}
 */
const removeDecimals =
  (decimals, separator = '.') =>
  (amount) => {
    let str = separator;

    for (let i = 0; i < decimals; i++) {
      str += '0';
    }

    return amount.replace(str, '');
  };

const formatForString = (amount, decimals) => {
  const amountStr = String(amount).replace(
    new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
    '$1,',
  );
  return removeDecimals(decimals)(amountStr);
};

/**
 * Formats amount by adding commas and decimal points.
 * @param {Number} amount Amount in the lowest denomination
 * @param {String} currency
 *
 * @return {String}
 */
const formatAmount = (amount) => {
  const divided = amount / 10 ** 2;

  return formatForString(divided.toFixed(2), 2);
};

export const getEmiInterestAmount = (amount, payback: number) => {
  return (amount / (1 - payback / 100) - amount).toFixed(2);
};

export const calculateEmiData = (emiPlans, orderAmount) => {
  const result: any = [];

  emiPlans.forEach((emiPlan) => {
    const updatedOrderAmount = convertToMinorUnit(parseFloat(orderAmount), {
      currency: user.merchant.currency,
    });

    const amountPerMonth = calculateEmi(updatedOrderAmount, emiPlan.duration, emiPlan.interest);

    const formattedAmountPerMonth = formatAmount(amountPerMonth);

    let interestChargedByBank = '';
    const totalAmount = amountPerMonth * emiPlan.duration;

    if (emiPlan.merchant_payback) {
      interestChargedByBank = formatAmount(
        getEmiInterestAmount(updatedOrderAmount, emiPlan.merchant_payback),
      );
    }

    result.push({
      emiPlan: `₹ ${formattedAmountPerMonth.split('.')[0]} x ${emiPlan.duration} m`,
      interest: `₹ ${interestChargedByBank.split('.')[0]} (@ ${emiPlan.interest}%)`,
      totalPayable: `₹ ${formatAmount(totalAmount).split('.')[0]}`,
    });
  });
  return result;
};

export const getCardlessEmiArray = (cardless_emi, eligibilityData) => {
  return Object.keys(cardless_emi)?.map((key) => {
    const providerName = key.toUpperCase();
    const name = `Pay using ${providerName}`;

    return {
      method: 'cardless_emi',
      title: cardlessEmiList[key]?.name || `${providerName} Cardless EMI`,
      name,
      provider: key,
      image: cardlessEmiList[key]?.icon,
      notEligible: checkCardlessEmiEligibility(key, eligibilityData),
    };
  });
};

export const getCardEmiArray = (emi_options, eligibilityData, orderAmount) => {
  return Object.keys(emi_options).map((key) => {
    const providerName = key.includes('DC') ? `${key.replace('_DC', '')}` : key;
    const title = emiBanksList[key]?.name ?? `${providerName} EMI`;
    const name = `Pay using ${providerName}`;

    return {
      method: 'emi',
      title,
      name,
      image: emiBanksList[key]?.icon,
      provider: providerName.toLowerCase(),
      type: key.includes('DC') ? 'debit' : 'credit',
      notEligible: checkEmiEligibility(
        key.replace('_DC', ''),
        key.includes('DC') ? 'debit' : 'credit',
        eligibilityData,
      ),
      emiPlan: calculateEmiData(emi_options[key], orderAmount),
    };
  });
};

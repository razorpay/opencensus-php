import {
  MAX_HOURS,
  MAX_MINS,
  VALIDATION_MSGS,
  DISCOUNT_TYPE,
  MIN_TIME,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';

import {
  getHoursString,
  getMinsString,
  toHoursAndMinutes,
} from 'merchant/views/MagicCheckout/helper';

export const getConversionString = (configs) => {
  const riskCategory = configs?.risk_category || [];

  if (riskCategory.length === 3) {
    // for [high, medium, low] risk categories
    return 'All COD orders';
  } else if (riskCategory.length === 2) {
    //for [high, medium] risk categories
    return 'High and Medium RTO risk COD orders';
  } else {
    const capitalizedString = riskCategory[0]
      ? riskCategory[0]?.charAt(0).toUpperCase() + riskCategory[0]?.slice(1)
      : '';
    return `${capitalizedString} RTO risk COD order`;
  }
};

export const getDiscountString = (configs) => {
  const { type, max_discount, minimum_order_value, discount_percentage } = configs?.discount || {};

  if (type === 'percentage') {
    return `₹${discount_percentage}% off${
      max_discount ? ` upto ₹${max_discount / 100}` : ''
    } on minimum order of ₹${minimum_order_value / 100}`;
  } else if (type === 'flat') {
    return `₹${max_discount / 100} on minimum order of ₹${minimum_order_value / 100}`;
  } else {
    return 'Disabled';
  }
};

export const getExpiryTimeString = (configs) => {
  const { expire_seconds } = configs?.communication || 0;

  const res = toHoursAndMinutes(expire_seconds);

  if (res.hours && res.mins) {
    return `${getHoursString(res.hours)} ${getMinsString(res.mins)}`;
  } else if (res.hours) {
    return getHoursString(res.hours);
  } else {
    return getMinsString(res.mins);
  }
};

export const getConvertOrderOnString = (configs) => {
  const { methods = [] } = configs?.communication || {};

  if (methods.length === 2) {
    return 'Both WhatsApp message & Order status page';
  }

  if (methods.length === 1) {
    return methods[0] === 'checkout' ? 'Order status page' : 'WhatsApp message';
  }

  return '';
};

export const getConvertRiskCategoryArray = (convertRiskCategory, isManualReviewOpted) => {
  if (!isManualReviewOpted || convertRiskCategory === 'all') {
    return ['high', 'medium', 'low'];
  } else if (convertRiskCategory === 'highMedium') {
    return ['high', 'medium'];
  } else {
    return [convertRiskCategory];
  }
};

export const increaseValidity = (unit, durationVal, setDurationVal) => {
  const val = parseInt(durationVal[unit], 10);

  if (isNaN(val)) {
    setDurationVal((prevState) => ({ ...prevState, [unit]: 0 }));
    return;
  }

  if ((unit === 'hours' && val < MAX_HOURS) || (unit === 'mins' && val < MAX_MINS))
    setDurationVal((prevState) => ({ ...prevState, [unit]: val + 1 }));
};

export const decreaseValidity = (unit, durationVal, setDurationVal) => {
  const val = parseInt(durationVal[unit], 10);

  if (val > 0) setDurationVal((prevState) => ({ ...prevState, [unit]: val - 1 }));
};

export const isValidDuration = (h, m) => {
  const hours = parseInt(h, 10);
  const mins = parseInt(m, 10);

  if (isNaN(hours) || isNaN(mins)) {
    return false;
  }

  if (
    (hours >= 0 && hours <= MAX_HOURS - 1 && mins >= 0 && mins <= MAX_MINS) ||
    (hours === MAX_HOURS && mins === 0)
  ) {
    return true;
  } else {
    return false;
  }
};

export const isDiscountInvalid = (discount, discountType) => {
  if (discount.error) return true;
  if (discountType === DISCOUNT_TYPE.percentage) {
    if (
      !discount.minOrderValue ||
      discount.percent < 0 ||
      discount.minOrderValue < 0 ||
      discount.maxDiscount < 0
    ) {
      return true;
    }
  } else if (discountType === DISCOUNT_TYPE.flat) {
    if (
      isNaN(discount.minOrderValue) ||
      isNaN(discount.maxDiscount) ||
      discount.minOrderValue <= 0 ||
      discount.maxDiscount < 0
    ) {
      return true;
    }
  }
  return false;
};

export const isDurationInvalid = (validity, durationVal) => {
  if (
    validity === 'custom' &&
    ((durationVal.hours === 0 && durationVal.mins === 0) ||
      (durationVal.hours === 0 && durationVal.mins < MIN_TIME))
  ) {
    return true;
  }
  if (!isValidDuration(durationVal.hours, durationVal.mins)) {
    return true;
  }
  return false;
};

export const customTimeValidators = {
  hours: (val) => {
    const parsedVal = parseInt(val, 10);

    if (parsedVal === 0) {
      return '';
    }

    if (isNaN(parsedVal)) {
      return VALIDATION_MSGS.required;
    }

    const regex = new RegExp(`^(?:[1-3]?[0-9]|4[0-8]*)$`, 'i');

    if (parsedVal < 0) {
      return VALIDATION_MSGS.lessThanZero;
    } else if (!regex.test(parsedVal)) {
      return VALIDATION_MSGS.duration.hoursError;
    }

    return '';
  },
  mins: (val) => {
    const parsedVal = parseInt(val, 10);

    if (parsedVal === 0) {
      return '';
    }

    if (isNaN(parsedVal)) {
      return VALIDATION_MSGS.required;
    }

    const regex = new RegExp(`^(?:[0-9]|[1-5][0-9])*$`, 'i');

    if (parsedVal < 0) {
      return VALIDATION_MSGS.lessThanZero;
    } else if (!regex.test(parsedVal)) {
      return VALIDATION_MSGS.duration.minutesError;
    }

    return '';
  },
};

import { DENOMINATION_TYPE_ENUM } from '../shared/constants';

export const transformStateToPatchAPIPayload = (data, updateUrl) => {
  const {
    name,
    description,
    denomination_type,
    pin,
    validity_quantity,
    validity_span,
    steps_to_redeem,
    terms_and_conditions,
    denomination_values,
    image,
    logo,
    brand_color,
  } = data;
  const payload = {
    program_name: name,
    policies: {
      program_desc: description,
      gift_card_pin_enabled: pin === 'yes',
      gift_card_price_type: denomination_type,
      redemption_steps: steps_to_redeem,
      tnc: terms_and_conditions,
      gift_card_validity_period_count: Number(validity_quantity),
      gift_card_validity_period: validity_span,
      gift_card_default_image_applicable: false,
    },
  };
  if (updateUrl && logo) {
    payload.policies.gift_card_brand_logo = image;
    payload.policies.gift_card_brand_color = brand_color;
    payload.policies.gift_card_file_storage_id = logo;
  } else if (updateUrl) {
    payload.policies.gift_card_file_storage_id = image;
  }

  if (denomination_type === DENOMINATION_TYPE_ENUM.RANGE) {
    payload.policies.gift_card_minimum_price = Number(denomination_values.from);
    payload.policies.gift_card_maximum_price = Number(denomination_values.to);
  } else {
    payload.policies.gift_card_price_denominations = denomination_values.map((val) => +val);
  }
  return payload;
};

export const transformStateToAPIPayload = (data) => {
  const {
    name,
    description,
    discount,
    denomination_type,
    pin,
    validity_quantity,
    validity_span,
    steps_to_redeem,
    terms_and_conditions,
    denomination_values,
    image,
    logo,
    brand_color,
  } = data;
  const payload = {
    name,
    type: 'giftcard',
    status: 'active',
    policies: {
      gift_card_ppi_type: 'non-ppi',
      max_discount_percent: Number(discount),
      min_discount_percent: Number(discount),
      program_desc: description,
      gift_card_pin_enabled: pin === 'yes',
      gift_card_price_type: denomination_type === 'fixed' ? 'fixed' : 'range',
      redemption_steps: steps_to_redeem,
      tnc: terms_and_conditions,
      gift_card_validity_period_count: Number(validity_quantity),
      gift_card_validity_period: validity_span,
      gift_card_default_image_applicable: false,
      gift_card_file_storage_id: image,
    },
  };
  if (logo) {
    payload.policies.gift_card_brand_logo = image;
    payload.policies.gift_card_brand_color = brand_color;
    payload.policies.gift_card_file_storage_id = logo;
  }

  if (denomination_type !== 'fixed') {
    payload.policies.gift_card_minimum_price = Number(denomination_values.from);
    payload.policies.gift_card_maximum_price = Number(denomination_values.to);
  } else {
    payload.policies.gift_card_price_denominations = denomination_values.map((val) => +val);
  }
  return payload;
};

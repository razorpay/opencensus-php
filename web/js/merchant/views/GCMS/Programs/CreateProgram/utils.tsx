import { GC_CARD_TYPE, GC_CARD_TYPE_VALUES } from './constants';

export const createFileObject = async () => {
  const file = new File(['Placeholder image file'], 'image.jpg', { type: 'plain/text' });
  return file;
};

export function translateToFormData(program) {
  const {
    name,
    policies: {
      program_desc,
      max_discount_percent,
      gift_card_price_type,
      gift_card_pin_enabled,
      redemption_steps,
      tnc,
      gift_card_validity_period_count,
      gift_card_validity_period,
      gift_card_brand_color,
      gift_card_brand_logo,
      gift_card_minimum_price,
      gift_card_maximum_price,
      gift_card_price_denominations,
      gift_card_number_length,
      gift_card_number_prefix,
      gift_card_number_alphanumeric_enabled,
    },
    url,
  } = program;

  const data = {
    name,
    description: program_desc,
    discount: max_discount_percent,
    denomination_type: gift_card_price_type,
    pin: gift_card_pin_enabled ? 'yes' : 'no',
    steps_to_redeem: redemption_steps || '',
    terms_and_conditions: tnc || '',
    validity_quantity: String(gift_card_validity_period_count),
    validity_span: gift_card_validity_period,
    // url: url[1],
    brand_color: gift_card_brand_color,
    logo: gift_card_brand_logo,
    card_length: gift_card_number_length,
    prefix: gift_card_number_prefix,
    card_type: gift_card_number_alphanumeric_enabled
      ? GC_CARD_TYPE_VALUES.ALPHANUMERIC
      : GC_CARD_TYPE_VALUES.NUMERIC,
  };
  if (gift_card_price_type === 'range') {
    data.denomination_values = {
      from: String(gift_card_minimum_price),
      to: String(gift_card_maximum_price),
    };
  } else {
    data.denomination_values = gift_card_price_denominations.map((val) => String(val));
  }

  data.upload_type = gift_card_brand_logo ? 'brand' : 'custom';

  return data;
}

export function generateRandomGiftCardNumber(
  length = 16,
  prefix = '',
  cardType = GC_CARD_TYPE_VALUES.NUMERIC,
) {
  const restLength = length - prefix.length;
  const numeric = '0123456789';
  const alphanumeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' + numeric;

  const characters = cardType === GC_CARD_TYPE_VALUES.NUMERIC ? numeric : alphanumeric;

  let result = '';
  for (let i = 0; i < restLength; i++) {
    result += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return `${prefix.toUpperCase()}${result}`;
}

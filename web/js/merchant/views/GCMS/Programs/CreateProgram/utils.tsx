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
    url: url[1],
    brand_color: gift_card_brand_color,
    logo: gift_card_brand_logo,
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

import { convertToMajorUnit, convertToMinorUnit } from '@razorpay/i18nify-js/currency';

import { deepClone } from 'common/utils/rzp-utils';

import {
  ShippingMethod,
  ShippingProfile,
} from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { ShippingMethodPayload } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/types';

export const convertServerDataToTableData = (shipping_profiles: {
  [key: string]: ShippingProfile;
}): ShippingMethod[] => {
  const shippingProfiles: ShippingProfile[] = [];
  const shippingMethods: ShippingMethod[] = [];

  Object.keys(shipping_profiles).forEach((profile) => {
    shippingProfiles.push(shipping_profiles[profile]);
  });

  shippingProfiles?.forEach((shippingProfile) => {
    shippingProfile?.zones?.forEach((zone) => {
      zone?.shipping_methods?.forEach((shippingMethod: ShippingMethod) => {
        shippingMethods.push({
          ...shippingMethod,
          zone_id: zone?.id,
          item_category_id: shippingProfile?.id as string,
          shippingZone: [shippingProfile?.name, zone?.name].join('-'),
          profileName: shippingProfile?.name,
        });
      });
    });
  });
  return shippingMethods;
};

export const convertTableDataToForm = (tableData: ShippingMethod): ShippingMethod => {
  const formData = deepClone(tableData);
  Object.keys(formData).forEach((key) => {
    if (key === 'cod_fee_rules') {
      const { amount } = formData[key] ?? {};
      if (amount) {
        amount.gte = convertToMajorUnit(amount.gte, { currency: 'INR' });
        amount.lt = convertToMajorUnit(amount.lt, { currency: 'INR' });
        formData.cod_fee_rules = { amount };
      } else {
        formData.cod_fee_rules = { amount: { gte: null, lt: null } };
      }
    }
  });
  return formData;
};

export const convertFormDataToPayload = (formData: ShippingMethod): ShippingMethodPayload => {
  const payload = deepClone(formData);
  const REDUNDANT_ATTRIBUTES = ['created_at', 'updated_at', 'shippingZone', 'profileName'];
  REDUNDANT_ATTRIBUTES.forEach((redundantAttribute) => delete payload[redundantAttribute]);

  if (
    !payload?.allow_cod ||
    (!payload.cod_fee_rules?.amount?.gte && !payload.cod_fee_rules?.amount?.lt)
  ) {
    payload.cod_fee_rules = null;
  } else {
    payload.cod_fee_rules.amount.gte = convertToMinorUnit(
      Number(payload.cod_fee_rules?.amount?.gte),
      {
        currency: 'INR',
      },
    );
    payload.cod_fee_rules.amount.lt = convertToMinorUnit(
      Number(payload.cod_fee_rules?.amount?.lt),
      {
        currency: 'INR',
      },
    );
  }
  //we have to update shopify metafields only for magicX
  payload.app_type = 'sopc';
  return payload;
};

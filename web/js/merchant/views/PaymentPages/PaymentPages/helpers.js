import { RULE_TYPES } from 'merchant/views/MagicCheckout/constants';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

/*
  Function: transfeeRuleToApiFormat used to convert rupee to paise and change the structure to API required format
*/
export function transfeeRuleToApiFormat({ rule_type, flat, slabs }) {
  if (rule_type === RULE_TYPES.FLAT) {
    flat = flat * 100;
  } else if (rule_type === RULE_TYPES.SLABS) {
    slabs = slabs.map((slab) => ({
      gte: slab.gte * 100,
      lte: slab.lte * 100,
      fee: slab.fee * 100,
    }));
  }
  return { rule_type, flat, slabs };
}

/*
  Function: transfeeRuleToNormalFormat used to convert paise to rupee and change the structure to UI required format
*/
export function transfeeRuleToNormalFormat({ rule_type, flat, slabs }) {
  if (rule_type === RULE_TYPES.FLAT) {
    flat = flat / 100;
  } else if (rule_type === RULE_TYPES.SLABS) {
    slabs = slabs.map((slab) => ({
      gte: slab.gte / 100,
      lte: slab.lte / 100,
      fee: slab.fee / 100,
    }));
  }
  return { rule_type, flat, slabs };
}
const TAX_EXEMPTION_NAME_MAP = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: '80g',
  [ORG_CUSTOM_CODE_MAP.CURLEC]: 'Tax Exemption',
};

export const getI18nTaxExemptionName = (orgCode) =>
  TAX_EXEMPTION_NAME_MAP[orgCode] || TAX_EXEMPTION_NAME_MAP[ORG_CUSTOM_CODE_MAP.RAZORPAY];

export const getAlertMsg = ({ isBatchPaymentPages, field }) => {
  const config = [
    {
      condition: !isBatchPaymentPages,
      message: (
        <>
          <b>Mandatory</b> {field?.name} field to be filled by customers.This field cannot be
          deleted.
        </>
      ),
    },
    {
      condition: isBatchPaymentPages && field?.name !== 'pri__ref__id',
      message: (
        <>
          {' '}
          <b>Mandatory</b> {field?.title} field present in the batch upload file, this field cannot
          be deleted.
        </>
      ),
    },
    {
      condition: isBatchPaymentPages && field?.name === 'pri__ref__id',
      message: (
        <>
          <b>Mandatory</b> {field?.title} to be present in the batch upload file and to be used by
          customer for validation, this field cannot be deleted.
        </>
      ),
    },
  ];
  const finalList = config.filter((obj) => {
    return obj?.condition;
  });
  return finalList[0]?.message;
};

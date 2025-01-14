import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';
import { MAGICX_PUBLICAPP_COD_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';

export const BLOCKLIST_TYPES = ['zipcode', 'email', 'phone', 'ip'];

export const getBlockListTypes = (
  options = {
    isRCOD: false,
  },
) => {
  const isMagicXPublicAppCODEnabled = useMagicExperiment(MAGICX_PUBLICAPP_COD_EXPERIMENT);
  const blocklistTypesZipcodeOnly = BLOCKLIST_TYPES.filter((type) => type === 'zipcode');

  if (isMagicXPublicAppCODEnabled && options.isRCOD) {
    return BLOCKLIST_TYPES;
  }

  return options.isRCOD ? blocklistTypesZipcodeOnly : BLOCKLIST_TYPES;
};

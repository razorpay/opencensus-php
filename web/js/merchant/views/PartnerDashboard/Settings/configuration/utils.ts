import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PartnerConfig } from './configTypes';
import { DEFAULT_CONFIG } from './constants';

export const getInitialState = ({ isError, data }: TODO_PD): PartnerConfig => {
  let initialState = {
    config_id: '',
    brand_color: `#${DEFAULT_CONFIG.BRAND_COLOR}`,
    text_color: `#${DEFAULT_CONFIG.TEXT_COLOR}`,
    brand_name: '',
    brand_logo: '',
  };
  if (!isError && data?.data) {
    const {
      data: { id, partner_metadata },
    } = data;
    initialState = { ...initialState, config_id: id };
    if (partner_metadata) {
      initialState = {
        ...initialState,
        brand_name: partner_metadata?.brand_name ?? '',
        text_color: `#${partner_metadata.text_color ?? DEFAULT_CONFIG.TEXT_COLOR}`.toUpperCase(),
        brand_color: `#${partner_metadata.brand_color ?? DEFAULT_CONFIG.BRAND_COLOR}`.toUpperCase(),
        brand_logo: partner_metadata?.logo_url ?? '',
      };
    }
  }
  return initialState;
};

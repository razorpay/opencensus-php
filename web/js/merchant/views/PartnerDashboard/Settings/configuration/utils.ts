import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PartnerConfig } from './configTypes';

export const getInitialState = ({ isError, data }: TODO_PD): PartnerConfig => {
  let initialState = {
    config_id: '',
    brand_color: '#528FF0',
    text_color: '#FFFFFF',
    brand_name: '',
    brand_logo: '',
  };
  if (!isError && data?.data?.partner_metadata) {
    const {
      data: { id, partner_metadata },
    } = data;
    initialState = { ...initialState, config_id: id };
    if (partner_metadata?.brand_color) {
      const { brand_color, text_color, brand_name } = partner_metadata;
      initialState = {
        ...initialState,
        brand_name,
        text_color: `#${text_color.toUpperCase()}`,
        brand_color: `#${brand_color.toUpperCase()}`,
        brand_logo: partner_metadata?.logo_url || '',
      };
    }
  }
  return initialState;
};

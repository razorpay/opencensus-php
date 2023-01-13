import rzpLogo from 'assets/rzpLogo.svg';
import { DEFAULT_CONFIG } from 'merchant/views/PartnerDashboard/Settings/configuration/constants';

export const config = {
  id: 'JI2nCsLmGJRQVE',
  commission_model: 'commission',
  partner_metadata: {
    brand_name: 'Amazon Web Services',
    brand_color: '518691',
    text_color: 'FFFFFF',
    logo_url: 'https://dashboard.razorpay.in/logos/KuVwuta5ybMfQi_original.png',
  },
};

export const responseDataEmpty = {
  data: {
    id: 'JI2nCsLmGJRQVE',
    partner_metadata: null,
  },
};

export const initialState = {
  config_id: '',
  brand_color: `#${DEFAULT_CONFIG.BRAND_COLOR}`,
  text_color: `#${DEFAULT_CONFIG.TEXT_COLOR}`,
  brand_name: '',
  brand_logo: '',
};

export const initialStateWithResponse = {
  config_id: config.id,
  brand_color: `#${config.partner_metadata.brand_color}`,
  text_color: `#${config.partner_metadata.text_color}`,
  brand_name: config.partner_metadata.brand_name,
  brand_logo: config.partner_metadata.logo_url,
};

export const errorResponse = {
  code: 'UNKNOWN_ERROR_CODE',
  status_code: 400,
  success: false,
  errors: ['Sorry, We couldn’t save your changes', 'Status Code: 400'],
};

export const errorResponseFetch = {
  status_code: 400,
  success: false,
  errors: ['There was an error', 'Status Code: 400'],
};

export const previewProps = {
  brandName: 'Razorpay',
  brandColor: '#528FF0',
  textColor: '#FFFFFF',
  uploadLogo: rzpLogo,
  rzpLogo,
};

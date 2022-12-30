import rzpLogo from 'assets/rzpLogo.svg';

export const config = {
  id: 'JI2nCsLmGJRQVE',
  commission_model: 'commission',
  brand_name: 'Amazon Web Services',
  brand_color: '518691',
  text_color: 'FFFFFF',
  logo_url: 'https://betacdn.np.razorpay.in/logos/KuVwuta5ybMfQi_original.png',
};

export const errorResponse = {
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

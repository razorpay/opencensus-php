import { storefrontData } from '../../mocks/mockData';

export const defaultAddSocialMediaDetailsProps = {
  openSocialMediaDrawer: false,
  storefront: storefrontData,
  editStorefront: jest.spyOn(
    require('merchant/reducers/paymentPages/storefront'),
    'editStorefront',
  ),
  showSocialMedialAlert: false,
  isMobile: false,
  storefrontId: '123',
};

export const mockSocialHandleModalProps = {
  saveSocialHandle: jest.fn(),
  inputVal: 'test social handle',
  selectedHandle: null,
  updateInputValue: jest.fn(),
  cancelSocialHandleOperation: jest.fn(),
  uploadedFile: null,
  setUploadedFile: jest.fn(),
  isSaving: false,
  uploadedLogo: '',
  setUploadedLogo: jest.fn(),
  isSaveDisabled: false,
  setSelectedHandle: jest.fn(),
};

export const mockSocialHanleList = [
  {
    id: 'Q2gtpONut30N5N',
    platform: 'instagram',
    profile_url: 'https://www.instagram.com/username',
    position: 1,
    meta_data: null,
    nocode_id: 'Q2gtpDycwzP8Sx',
    logo_url:
      'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2enzzenkbxdxm.jpeg',
  },
];

export const mockSocialHandleListProps = {
  reorderSocialHandle: jest.fn(),
  isMobile: false,
  socialHandleList: mockSocialHanleList,
  editSocialHandle: jest.fn(),
  showDeleteConfirmation: jest.fn(),
};

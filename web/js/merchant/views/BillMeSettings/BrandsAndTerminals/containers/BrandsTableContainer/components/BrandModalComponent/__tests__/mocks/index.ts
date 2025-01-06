import { BRAND_OPERATION_TYPE } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/constants';

export const BRAND_MODAL_COMPONENT_MOCK_PROPS = {
  onCloseModal: jest.fn(),
  refetchBrandsList: jest.fn(),
  brandModalInfo: {
    operationType: BRAND_OPERATION_TYPE.READ,
    selectedBrandId: '1234',
  },
};

export const BRAND_INFO_MOCK_QUERY_RESPONSE = {
  refetch: jest.fn(),
  data: null,
  isFetching: false,
  isLoading: false,
  isError: false,
  isSuccess: false,
  error: null,
};

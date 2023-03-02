import { LoaderSize } from 'merchant/views/PaymentHandle/typings';
import {
  checkSlugAvailabilityApi,
  getSlugSuggestionsApi as suggestion,
} from 'merchant/reducers/paymentHandle/api';
import { SHIMMER_BAR_VARIANTS, ERROR_MAPPING } from 'merchant/views/PaymentHandle/constants';

export const calculateLoaderSize = (variant: 'desktop' | 'mobile', size: string): LoaderSize => {
  let width: string;
  let height: string;

  switch (size) {
    case SHIMMER_BAR_VARIANTS.LARGE:
      width = variant === 'desktop' ? '432px' : '240px';
      height = '32px';
      break;
    case SHIMMER_BAR_VARIANTS.MEDIUM:
      width = variant === 'desktop' ? '432px' : '200px';
      height = '24px';
      break;
    case SHIMMER_BAR_VARIANTS.SMALL:
      width = variant === 'desktop' ? '290px' : '180px';
      height = '24px';
      break;
    default:
      width = '0px';
      height = '0px';
  }
  return { width, height };
};

export const isHandleAvailableForMerchant = (errors: string[]): boolean => {
  if (errors[0] === ERROR_MAPPING.HANDLE_INVALID) {
    return false;
  }
  return true;
};

export const getSlugAvailability = async (slug: string): Promise<any> => {
  try {
    const response = await checkSlugAvailabilityApi(slug);
    return response.data.exists;
  } catch (error) {
    console.warn(error);
    return false;
  }
};

export const getSlugSuggestions = async (): Promise<any> => {
  try {
    const suggestionsData = await suggestion();
    return suggestionsData.data.suggestions;
  } catch (error) {
    console.warn(error);
    return [];
  }
};

export const getPHProductOnboarding = (user): boolean => {
  return user.isPaymentHandleEnabled;
};

export const getIsTestMode = (mode) => mode === 'test';

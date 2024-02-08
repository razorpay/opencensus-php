import { merchantFetch } from 'merchant/utils/ajax';

import { ApiDataType, FormikValues } from './types';
import { formatApiResponse, generateApiData } from './utils';

export const fetchAdditionalDocumentFormData = async (): Promise<ApiDataType> => {
  try {
    const response = await merchantFetch('international_enablement');
    return formatApiResponse(response.data);
  } catch {
    return {};
  }
};

export const saveAdditionalDocumentFormData = async (
  apiData: ApiDataType,
  formData: FormikValues,
): Promise<void> => {
  try {
    await merchantFetch({
      url: 'international_enablement/draft',
      method: 'post',
      data: generateApiData(apiData, formData),
    });
  } catch {
    throw new Error("Data couldn't be saved because of some intermittent issue! Please try again.");
  }
};

export const submitAdditionalDocumentFormData = async (
  apiData: ApiDataType,
  formData: FormikValues,
): Promise<void> => {
  try {
    await merchantFetch({
      url: 'international_enablement/submit',
      method: 'post',
      data: generateApiData(apiData, formData),
    });
  } catch {
    throw new Error("Data wasn't submitted because of some intermittent issue! Please try again.");
  }
};

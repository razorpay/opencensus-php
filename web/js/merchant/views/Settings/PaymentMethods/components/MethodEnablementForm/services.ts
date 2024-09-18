import { User } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';

import {
  trackIntlMethodEnablementFormData,
  trackIntlMethodEnablementFormDataErr,
} from './analytics';
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
  user: User,
): Promise<void> => {
  try {
    const data = generateApiData(apiData, formData);
    trackIntlMethodEnablementFormData(user?.business_type, data);
    await merchantFetch({
      url: 'international_enablement/draft',
      method: 'post',
      data,
    });
  } catch (err) {
    trackIntlMethodEnablementFormDataErr({
      businessType: user?.business_type,
      documents: apiData?.documents,
      purposeCode: apiData?.purpose_code,
      error: err,
    });
    throw new Error("Data couldn't be saved because of some intermittent issue! Please try again.");
  }
};

export const submitAdditionalDocumentFormData = async (
  apiData: ApiDataType,
  formData: FormikValues,
  user?: User,
): Promise<void> => {
  try {
    await merchantFetch({
      url: 'international_enablement/submit',
      method: 'post',
      data: generateApiData(apiData, formData),
    });
  } catch (err) {
    trackIntlMethodEnablementFormDataErr({
      businessType: user?.business_type,
      documents: apiData?.documents,
      purposeCode: apiData?.purpose_code,
      error: err,
    });
    throw new Error("Data wasn't submitted because of some intermittent issue! Please try again.");
  }
};

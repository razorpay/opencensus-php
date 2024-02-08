import * as Yup from 'yup';

import {
  ADDITIONAL_DOCUMENTS_FOR_BUSINESS_TYPE,
  DOCUMENTS_SCHEMA,
  FORM_SCHEMA,
  PRODUCT,
} from './constants';
import { ApiDataType, FormikValues, Doc, IntlFormDataType } from './types';

export const getAdditionalDocumentsBasedOnBusinessType = (businessType: string): Array<Doc> => {
  return ADDITIONAL_DOCUMENTS_FOR_BUSINESS_TYPE[businessType];
};

export const getFormSchema = (businessType: string) => {
  const additionalDocSchema = DOCUMENTS_SCHEMA[businessType];
  return FORM_SCHEMA.concat(
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    Yup.object().shape({
      documents: additionalDocSchema,
    }),
  );
};

export const generateApiData = (apiData: ApiDataType = {}, formData: FormikValues): ApiDataType => {
  const transformedData = {
    ...apiData,
    products: [PRODUCT],
    documents: { ...apiData?.documents, ...formData.documents },
    accepts_intl_txns: apiData?.accepts_intl_txns ? 1 : 0,
    version: 'v2',
    goods_type: apiData?.goods_type || 'both',
    business_use_case:
      apiData?.business_use_case ||
      'International is already enabled, Requesting for Other International PA CB Methods Enablement',
    existing_risk_checks: apiData?.existing_risk_checks || ['None'],
  };
  return transformedData;
};

export const getFormData = (apiData: ApiDataType): ApiDataType['documents'] => {
  let documents: ApiDataType['documents'] = {};
  if (apiData?.products?.includes(PRODUCT)) {
    documents = apiData.documents;
  }
  return documents;
};

export const formatApiResponse = (apiData: ApiDataType & IntlFormDataType): ApiDataType => {
  delete apiData?.submitted_at;
  delete apiData?.updated_at;
  delete apiData?.created_at;
  return apiData;
};

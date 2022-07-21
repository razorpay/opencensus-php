import {
  INSTRUMENTS,
  MERCHANT_INFO,
  OWNER_DETAILS,
  SPECIAL_PURPOSE_CODES,
  instrumentFormSchema,
  detailsFormSchema,
  ownershipFormSchema,
  tabs,
} from './constants';
import moment from 'moment';

export const getFormSchema = (isSpecialPurposecode) => {
  const formSchema = instrumentFormSchema
    .concat(detailsFormSchema(isSpecialPurposecode))
    .concat(ownershipFormSchema);
  return formSchema;
};

/**
 * This function transforms the response from get apmFormData api
 * to accepted response for formik to work upon
 * Returns :
 * 1. values -> transforms the initial passed in values
 * 2. saveDocuments -> seperates documents from api response to be saved seperately
 * 3. unEditable -> creates an object with all the unEditable fields we get from response
 * 4. isSpecialPurposeCode -> this variables carries the information weather purpose from response is special or not
 * @param {*} initialValues - initialFormValues
 * @param {*} formData - response from getFormData
 * @returns {array} - array of values, savedDocuments, unEditable, isSpecialPurposecode
 */
export const initializeForm = (initialValues, formData) => {
  const values = { ...initialValues };
  const savedDocuments = {};
  let unEditable = {};
  let isSpecialPurposecode;
  Object.keys(initialValues).forEach((key) => {
    if (formData?.[key] && key === INSTRUMENTS) {
      values[key] = formData[key];
    }
    if (formData?.[key] && key === MERCHANT_INFO) {
      const { documents, ...data } = formData[key];
      documents?.forEach((document) => {
        savedDocuments[document?.id] = document;
        values[key][document.key] = document?.id;
      });
      values[key] = { ...values?.[key], ...data };
    }
    if (formData?.[key] && key === OWNER_DETAILS) {
      const owners = formData[key].map((owner) => {
        const { documents, ...data } = owner;
        documents?.forEach((document) => {
          savedDocuments[document?.id] = document;
          data[document.key] = document?.id;
        });
        return { ...values?.[key]?.[0], ...data };
      });
      if (owners.length) values[key] = owners;
    }
  });
  if (formData?.non_editable) {
    Object.keys(formData.non_editable).forEach(
      (key) => (values[MERCHANT_INFO][key] = formData.non_editable[key]),
    );
    unEditable = formData.non_editable;
  }
  if (SPECIAL_PURPOSE_CODES.includes(values?.[MERCHANT_INFO]?.purpose_code)) {
    isSpecialPurposecode = true;
  }
  return [values, savedDocuments, unEditable, isSpecialPurposecode];
};

const sortDocuments = (values, documents) => {
  const bodyDocuments = [];
  const filteredValues = {};
  Object.keys(values).forEach((key) => {
    const document = documents?.[values[key]];
    if (document && document?.id) bodyDocuments.push(document);
    if (!document) filteredValues[key] = values[key];
  });
  if (bodyDocuments.length) filteredValues.documents = bodyDocuments;
  return filteredValues;
};

export const transformApiBody = (values, errors, documents, activeOwner, isSubmitted) => {
  const body = {};
  let apiBody = {};
  tabs.forEach(({ dataKey }) => {
    if (errors?.[dataKey]) return;
    const isOwner = dataKey === OWNER_DETAILS;
    const tabValues = isOwner ? values?.[dataKey]?.[activeOwner] : values?.[dataKey];
    if (dataKey === INSTRUMENTS) apiBody = tabValues;
    else apiBody = sortDocuments(tabValues, { ...documents });
    body[dataKey] = isOwner ? [apiBody] : apiBody;
  });
  body.submitted = isSubmitted;
  return body;
};

/**
 * This functions transforms the response we get from Get purpose code api
 * @param {*} data - response from api
 * @returns {array} - transformed purpose code list
 */
export const transformPurposeCodeList = (data) => {
  return data.reduce((prev, current) => {
    return prev.concat(
      current.codes.map(({ purposeCode, description }) => {
        return { label: `${purposeCode}-${current.purposeGroup}`, description };
      }),
    );
  }, []);
};

/**
 * This function disables future dates of a calender
 * @param {*} current - date
 * @returns {boolean} - disabled if returns true;
 */
export const disableFutureDates = (current) => {
  if (!current) {
    return false;
  }
  current.endOf('day');
  const date = moment();
  date.endOf('day');
  const diffInDays = current.diff(date, 'days');
  return diffInDays >= 0;
};

export const refreshEntries = (history) => {
  history.replace('/');
  setTimeout(() => {
    history.replace('/payment-methods');
  }, 10);
};

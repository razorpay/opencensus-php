import { getCookie } from 'common/utils/cookies';

const payloadFieldValueMap = {
  firstname: 'name',
  email: 'email',
  phone: 'phone',
  website: 'url',
  merchant_id__c: 'mId',
  what_tech_stack_is_your_website_built_on_: 'stack',
  cod: 'cod',
  average_monthly_online_sales__gmv__from_website: 'gmv',
};

export const createHubspotPayload = (formData = {}) => {
  const fields = [];

  Object.keys(payloadFieldValueMap).forEach((field) => {
    const fieldInfo = {
      name: field,
      value: formData[payloadFieldValueMap[field]],
    };

    fields.push(fieldInfo);
  });

  return {
    fields,
    context: {
      hutk: getCookie('hubspotutk'),
      pageUri: window.location.href,
      pageName: document.title,
    },
  };
};

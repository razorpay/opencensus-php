import GenericEntity from '../GenericEntity';

export default class BaseReportingEntity extends GenericEntity {
  makeGenericAjaxCall(props) {
    return super.makeGenericAjaxCall({
      ...props,
      ...(this.reportType && {
        httpData: {
          headers: appendHeadersIfRequired(this),
        },
      }),
    });
  }
}

const possibleHeaders = [
  { headerKey: 'X-Report-type', entityKey: 'reportType' },
  { headerKey: 'X-Razorpay-Account', entityKey: 'accountId' },
];

function appendHeadersIfRequired(entity) {
  const headers = {};
  possibleHeaders.forEach(({ headerKey, entityKey }) => {
    if (!!entity[entityKey]) {
      headers[headerKey] = entity[entityKey];
    }
  });

  return headers;
}

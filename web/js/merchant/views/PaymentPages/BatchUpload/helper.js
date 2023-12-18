export const getHeaderList = (details) => {
  const { payment_page_items = [], settings } = details || {};

  const otherFields =
    typeof settings?.udf_schema === 'string' ? JSON.parse(settings?.udf_schema) : [];

  const allFields = [...payment_page_items, ...otherFields];

  // Sort the fields according to their position.
  allFields.sort((a, b) => Number(a?.settings?.position) - Number(b?.settings?.position));

  const cols = allFields.map((fi) => fi?.item?.name ?? fi.title);

  return cols;
};

export const getFormattedExcelData = (headerList) => {
  const newHeaderList = headerList.reduce((obj, element) => {
    obj[element] = '';
    return obj;
  }, {});
  return [newHeaderList];
};

export const allowSendAllLinks = (batch) => {
  // config object will not be available for older batches
  // duplicate batches will have no success count
  //if more than 0 payment link(s) has been sent, disable the btn
  return batch?.config && Object.keys(batch.config).length
    ? !(Number(batch?.config?.sms_notify) > 0 || Number(batch?.config?.email_notify) > 0)
    : false;
};

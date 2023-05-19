export const getHeaderList = (details) => {
  const paymentPageItems = details.payment_page_items?.map((element) => {
    return element?.item?.name;
  });
  let udfSchema = [];
  try {
    udfSchema = JSON.parse(details.settings?.udf_schema);
  } catch (error) {
    return false;
  }
  const udfSchemaItems = udfSchema?.map((element) => {
    return element?.title;
  });
  return paymentPageItems?.concat(udfSchemaItems);
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

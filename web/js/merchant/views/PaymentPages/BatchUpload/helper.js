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

export const getSupportDetailsPayload = (payload, supportDetail = {}, isIndividual) => {
  if (!isIndividual) {
    return payload;
  }
  const { data = {} } = supportDetail;
  return Object.keys(payload).reduce((acc, key) => {
    if (payload[key] && payload[key] !== data[key]) {
      acc[key] = payload[key];
    }
    if (key === 'url' && payload[key] !== data[key]) {
      acc[key] = payload[key];
    }
    return acc;
  }, {});
};

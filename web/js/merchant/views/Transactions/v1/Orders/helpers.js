const formattedKeyObj = {
  gstin: 'GSTIN',
  order_instructions: 'Order instructions',
};

export const getFormattedKey = (key) => formattedKeyObj[key] || key;

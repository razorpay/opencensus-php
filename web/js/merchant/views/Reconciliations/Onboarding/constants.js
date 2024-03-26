export const getReconDuration = (countryCode) => {
  switch (countryCode) {
    case 'IN':
      return '2 hours';
    default:
      return '2 hours';
  }
};

export const selectProductMeta = {
  title: 'Setup reconciliation process',
  question: 'Which product do you want to start setup?',
  questionSubText:
    'Get started with setting up a recon process for a product. You can always create more processes later for other products.',
};

export const getSuccessMessage = (countryCode) => {
  const time = getReconDuration(countryCode);
  return {
    config:
      'We have processed the sample file. You can start recon runs anytime now by uploading your file in the same format.',
    runs: `We are currently processing your records. The reconciliation may take upto ${time} to complete. You'll receive an email as soon as the process is complete.`,
  };
};

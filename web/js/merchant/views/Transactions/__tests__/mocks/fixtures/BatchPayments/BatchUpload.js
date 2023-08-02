import BatchUploadContainer from 'merchant/views/Transactions/BatchPayments/BatchUpload';

export const merchantTokenPageDetails = {
  data: {
    token: '2ab0a160eb2fb6ce2a4dc11e85b2d71f760f87e1',
  },
};

export const App = (props) => {
  return <BatchUploadContainer {...props} />;
};

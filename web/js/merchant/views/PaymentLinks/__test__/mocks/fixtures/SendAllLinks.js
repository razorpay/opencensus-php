import SendAllLinks from 'merchant/views/PaymentLinks/BatchUpload/components/SendAllLinks';

export const App = (props = {}) => {
  return (
    <SendAllLinks
      {...props}
      notifyBatch={jest.fn(() => Promise.resolve())}
      showNotification={jest.fn()}
      closeModal={jest.fn()}
      fetchAll={jest.fn()}
    />
  );
};

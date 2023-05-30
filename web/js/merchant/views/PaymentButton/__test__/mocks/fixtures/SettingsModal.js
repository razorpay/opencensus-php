import SettingsModal from 'merchant/views/PaymentButton/PaymentButton/components/SettingsModal';

const mockEditPaymentButton = jest.fn(() => Promise.resolve({ data: true }));

export const App = (props) => {
  return (
    <SettingsModal
      track={props.trackMock}
      paymentSuccessMessage="Payment Done"
      editPaymentButton={props.editPaymentButton || mockEditPaymentButton}
      {...props}
    />
  );
};

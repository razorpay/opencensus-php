// redux
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

// component
import PaymentListContainer from 'merchant/views/Transactions/v1/UploadInvoice/components/PaymentList';

// actions
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchFA, resetFA } from 'merchant/reducers/payments/details';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import { openModal } from 'merchant_common/reducers/modals';
import {
  uploadInvoicePending,
  uploadInvoiceError,
  uploadInvoiceSuccess,
  viewInvoicePending,
  viewInvoiceError,
  viewInvoiceSuccess,
} from 'merchant/reducers/paymentUploadInvoice';

const mapStatesToProps = (state) => ({
  ...state.payment,
  ...state.payments,
  ...state.paymentUploadInvoice,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
      fetchAll,
      fetchFA,
      resetFA,
      openModal,
      uploadInvoicePending,
      uploadInvoiceError,
      uploadInvoiceSuccess,
      viewInvoicePending,
      viewInvoiceError,
      viewInvoiceSuccess,
    },
    dispatch,
  );
};

export default connect(mapStatesToProps, mapDispatchToProps)(PaymentListContainer);

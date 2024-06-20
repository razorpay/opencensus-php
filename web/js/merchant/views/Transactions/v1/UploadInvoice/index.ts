import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withSplitzService } from 'common/splitz';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import {
  uploadInvoicePending,
  uploadInvoiceError,
  uploadInvoiceSuccess,
  viewInvoicePending,
  viewInvoiceError,
  viewInvoiceSuccess,
} from 'merchant/reducers/paymentUploadInvoice';
import { fetchFA, resetFA } from 'merchant/reducers/payments/details';
import { fetchPurposeCode } from 'merchant/reducers/profile';
import PaymentListContainer from 'merchant/views/Transactions/v1/UploadInvoice/components/PaymentList';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

const mapStatesToProps = (state) => ({
  ...state.payment,
  ...state.payments,
  ...state.paymentUploadInvoice,
  user: state.session.user,
  purposeCode: state.profile.fircDetails?.data?.purpose_code,
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
      fetchPurposeCode,
    },
    dispatch,
  );
};

export default connect(
  mapStatesToProps,
  mapDispatchToProps,
)(withSplitzService(PaymentListContainer));

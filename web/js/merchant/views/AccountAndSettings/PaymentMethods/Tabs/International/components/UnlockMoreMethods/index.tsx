import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';

import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchEddDetails } from 'merchant/reducers/unlockIntlPaymentMethods/actions';
import { setIsMethodEnablementFormOpen } from 'merchant/reducers/unlockIntlPaymentMethods/reducer';
import UnlockMoreMethods from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/UnlockMoreMethods/UnlockMoreMethods';

const mapStateToProps = ({ session, unlockIntlPaymentMethods }) => {
  return {
    user: session.user,
    status: unlockIntlPaymentMethods.status,
    kycDocumentStatus: unlockIntlPaymentMethods.kycDocumentStatus,
    kycRejectedReason: unlockIntlPaymentMethods.kycRejectedReason,
    vKycRejectedReason: unlockIntlPaymentMethods.vKycRejectedReason,
    eddStatus: unlockIntlPaymentMethods.eddStatus,
    vKycStatus: unlockIntlPaymentMethods.vKycStatus,
    isStatusLoading: unlockIntlPaymentMethods.isStatusLoading,
    showMorePaymentMethodsSection: unlockIntlPaymentMethods.showMorePaymentMethodsSection,
    isMethodEnablementFormOpen: unlockIntlPaymentMethods.isMethodEnablementFormOpen,
  };
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) => {
  return bindActionCreators(
    {
      fetchEddDetails,
      setIsMethodEnablementFormOpen,
      showNotification,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(UnlockMoreMethods);

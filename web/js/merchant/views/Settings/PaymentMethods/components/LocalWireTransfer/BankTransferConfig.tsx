import React, { useMemo } from 'react';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

//redux helpers
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//redux actions
import { openModal } from 'merchant_common/reducers/modals';
import { openSupport } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/utils';

//types and constants
import {
  ContainerErrorType,
  BankTransferConfigType,
  BankTransferConfigInterface,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
import { GREYED, ACTION_REQUIRED } from 'merchant/views/Settings/PaymentMethods/constants';
import { VA_USD } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';

const FircFormModal = lazy(
  () =>
    import(
      /* webpackChunkName: "FircFormModal" */ 'merchant/views/Account/Profile/components/FIRC/FIRCFormModal'
    ),
);

const withBankTransferConfig = (Component, method = VA_USD) => {
  const BankTransferConfig: React.FC<BankTransferConfigInterface> = ({
    accounts,
    isFetching,
    user,
    fircData,
    openModal,
    ...props
  }) => {
    const promoterPan = user?.promoter_pan_name;
    const purposeCode = fircData?.data?.purpose_code;
    const containerStatus = useMemo(() => {
      if (!purposeCode || !promoterPan) {
        return ACTION_REQUIRED;
      }
      return GREYED;
    }, [purposeCode, promoterPan]);
    const shouldShowAction = containerStatus !== GREYED;
    const shouldShowListAction = containerStatus === GREYED && !isFetching;

    const addPurposeCode = () => {
      openModal({
        size: 'medium',
        component: (
          <SuspenseWithLoader>
            <FircFormModal />
          </SuspenseWithLoader>
        ),
      });
    };

    const getContainerError = (): ContainerErrorType | boolean => {
      if (!purposeCode) {
        return {
          message: `Purpose code is required for activating ${method} account`,
          action: (
            <p>
              Update your <a onClick={addPurposeCode}>purpose code</a>
            </p>
          ),
        };
      }
      if (!promoterPan) {
        return {
          message: 'Authorised Signatory PAN details are mandatory for activating SWIFT account',
          action: (
            <p>
              To get help, <a onClick={openSupport}>contact our support team</a>
            </p>
          ),
        };
      }
      return false;
    };

    const config: BankTransferConfigType = {
      accounts,
      shouldShowAction,
      shouldShowListAction,
      containerStatus,
      containerError: getContainerError(),
    };

    return <Component {...props} config={config} />;
  };

  const mapStateToProps = (state) => ({
    accounts: state.b2bExportsAccounts.data,
    isFetching: state.b2bExportsAccounts.isLoading,
    user: state.session.user,
    fircData: state.profile.fircDetails,
  });

  const mapDispatchToProps = (dispatch) =>
    bindActionCreators(
      {
        openModal,
      },
      dispatch,
    );

  return connect(mapStateToProps, mapDispatchToProps)(BankTransferConfig);
};

export default withBankTransferConfig;

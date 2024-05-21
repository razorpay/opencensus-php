import React, { useMemo } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import {
  VA_USD,
  DEACTIVATED,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import {
  ContainerErrorType,
  BankTransferConfigType,
  BankTransferConfigInterface,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
import { openSupport } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/utils';
import { GREYED, ACTION_REQUIRED } from 'merchant/views/Settings/PaymentMethods/constants';
import { openModal } from 'merchant_common/reducers/modals';

const FircFormModal = lazy(
  () =>
    import(
      /* webpackChunkName: "FIRCFormModal" */ 'merchant/views/Account/Profile/components/FIRC/FIRCFormModal'
    ),
);

const withBankTransferConfig = (Component, method = VA_USD) => {
  const BankTransferConfig: React.FC<BankTransferConfigInterface> = ({
    accounts,
    isFetching,
    user,
    fircData,
    accountsDeactivated,
    reason,
    publicPaymentLink,
    openModal,
    showMorePaymentMethodsSection,
    ...props
  }) => {
    const promoterPan = user?.promoter_pan_name;
    const purposeCode = fircData?.data?.purpose_code;

    const isDisableInternationalPaymentMethods = showMorePaymentMethodsSection && !isFetching;

    const containerStatus = useMemo(() => {
      if (accountsDeactivated) {
        return DEACTIVATED;
      }

      if (isDisableInternationalPaymentMethods) {
        return GREYED;
      }

      if (!purposeCode || !promoterPan) {
        return ACTION_REQUIRED;
      }
      return GREYED;
    }, [purposeCode, promoterPan, accountsDeactivated, isDisableInternationalPaymentMethods]);
    const shouldShowAction = containerStatus !== GREYED && !isDisableInternationalPaymentMethods;
    const shouldShowListAction = containerStatus === GREYED && !isFetching;

    const addPurposeCode = () => {
      openModal({
        size: 'medium',
        component: (
          <SuspenseWithLoader>
            <FircFormModal editMode={Boolean(purposeCode)} code={purposeCode} />
          </SuspenseWithLoader>
        ),
      });
    };

    const getContainerError = (): ContainerErrorType | boolean => {
      if (isDisableInternationalPaymentMethods) {
        return false;
      }

      if (accountsDeactivated) {
        return {
          message: reason,
          action: (
            <p>
              Update your <a onClick={addPurposeCode}>purpose code</a>
            </p>
          ),
        };
      }
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
      isFetching,
      shouldShowAction,
      shouldShowListAction,
      containerStatus,
      publicPaymentLink,
      containerError: getContainerError(),
      isRequestButtonDisabled: isDisableInternationalPaymentMethods,
    };

    return <Component {...props} purposeCode={purposeCode} config={config} />;
  };

  const mapStateToProps = ({ b2bExportsAccounts, session, profile, unlockIntlPaymentMethods }) => ({
    accounts: b2bExportsAccounts.data,
    accountsDeactivated: b2bExportsAccounts.accountsDeactivated,
    reason: b2bExportsAccounts.reason,
    isFetching: b2bExportsAccounts.isLoading,
    user: session.user,
    fircData: profile.fircDetails,
    showMorePaymentMethodsSection: unlockIntlPaymentMethods.showMorePaymentMethodsSection,
    publicPaymentLink: b2bExportsAccounts.publicPaymentLink,
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

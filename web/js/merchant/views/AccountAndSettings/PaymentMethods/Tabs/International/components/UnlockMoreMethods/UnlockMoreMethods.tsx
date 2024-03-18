import React, { useEffect, Suspense, useCallback } from 'react';
import { Heading, Box, Text, Divider, Button, Alert } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { MORE_PAYMENT_METHOD_STATUS } from 'merchant/reducers/unlockIntlPaymentMethods/initialState';
import { setKycDocumentStatus } from 'merchant/reducers/unlockIntlPaymentMethods/reducer';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { ICProductStates } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { trackVkycStatusResponse } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/analytics';
import { openModal } from 'merchant_common/reducers/modals';

import TimelineView from './TimelineView';
import { ALERT_STATUS_MAPPING } from './constants';
import { DEFAULT_STATE } from './labels';
import { UnlockMoreMethodsProps, fetchEddDetailsResponse } from './types';
import { getDefaultTab } from './utils';

const MethodEnablementForm = React.lazy(
  () =>
    import(
      /* webpackChunkName: "MethodEnablementForm" */
      'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm'
    ),
);

const UnlockMoreMethods = ({
  user,
  status,
  instrument,
  vKycStatus,
  kycDocumentStatus,
  kycRejectedReason,
  productPaCbStatus,
  isMethodEnablementFormOpen,
  vKycRejectedReason,
  fetchEddDetails,
  setIsMethodEnablementFormOpen,
  setKycDocumentStatus,
  openModal,
  showNotification,
}: UnlockMoreMethodsProps) => {
  const kycAlert = ALERT_STATUS_MAPPING[kycDocumentStatus as ICProductStates];

  const closeMethodEnablementForm = () => {
    setIsMethodEnablementFormOpen({ isOpen: false });
  };

  const openMethodEnablementForm = () => {
    const defaultTab = getDefaultTab(user.business_type);
    setIsMethodEnablementFormOpen({ isOpen: true, defaultTab });
  };

  const onRetryKyc = () => {
    if (kycDocumentStatus === ICProductStates.REJECTED) {
      openMethodEnablementForm();
    } else {
      openModal({
        size: 'small',
        component: (
          <NeedsClarificationModal
            workflowType={WORKFLOW_TYPES.INTERNATIONAL_PRODUCTS_PA_CB_ENABLEMENT}
            workflowName={
              WORKFLOW_TYPES.INTERNATIONAL_PRODUCTS_PA_CB_ENABLEMENT && 'Action Required'
            }
            onResponseSubmit={() => setKycDocumentStatus(ICProductStates.UNDER_REVIEW)}
          />
        ),
      });
    }
  };

  const memoizedFetchEddDetails = useCallback(
    async (productStatus) => {
      try {
        const response: fetchEddDetailsResponse = await fetchEddDetails(productStatus);
        if (response?.error) {
          const errorMessage = JSON.parse(response.error.message ?? '');
          trackVkycStatusResponse(
            user.business_type,
            '',
            errorMessage?.statusCode,
            errorMessage?.message,
          );
        } else {
          trackVkycStatusResponse(user.business_type, response?.payload?.vKycStatus ?? '');
        }
      } catch {
        showNotification({
          type: 'error',
          message: 'Something went wrong. Please try again later!',
        });
      }
    },
    [fetchEddDetails],
  );

  useEffect(() => {
    memoizedFetchEddDetails(productPaCbStatus);
  }, [productPaCbStatus, memoizedFetchEddDetails]);

  return (
    <Box>
      <Box display="flex">
        <Box display="flex" flexDirection="column" gap="spacing.2">
          <Heading type="subtle">{instrument.header}</Heading>
          <Text type="subtle">{instrument.listDescription}</Text>
        </Box>

        {status === MORE_PAYMENT_METHOD_STATUS.INITIAL ? (
          <Box marginLeft="auto">
            <Button size="small" onClick={openMethodEnablementForm}>
              Request for more methods
            </Button>
          </Box>
        ) : null}
      </Box>
      {kycAlert && (
        <Alert
          marginTop="spacing.7"
          isFullWidth
          isDismissible={false}
          color={kycAlert.color}
          title={kycAlert.title}
          description={
            kycAlert.description?.[kycRejectedReason as string] ?? kycAlert.description?.default
          }
          actions={{
            primary: {
              onClick: onRetryKyc,
              text: kycAlert.ctaText,
            },
          }}
        />
      )}
      {status === MORE_PAYMENT_METHOD_STATUS.INITIAL ? (
        <Alert
          marginTop="spacing.5"
          isFullWidth
          color="notice"
          isDismissible={false}
          description={DEFAULT_STATE}
        />
      ) : (
        <>
          <Divider marginY="spacing.5" />

          <TimelineView
            kycDocumentStatus={kycDocumentStatus}
            vKycStatus={vKycStatus}
            user={user}
            vKycRejectedReason={vKycRejectedReason}
          />
        </>
      )}
      <Suspense fallback={null}>
        <MethodEnablementForm
          isOpen={isMethodEnablementFormOpen}
          onDismiss={closeMethodEnablementForm}
        />
      </Suspense>
    </Box>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ openModal, setKycDocumentStatus }, dispatch);
};

export default connect(null, mapDispatchToProps)(UnlockMoreMethods);

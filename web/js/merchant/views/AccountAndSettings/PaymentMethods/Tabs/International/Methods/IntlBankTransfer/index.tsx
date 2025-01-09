import React, { lazy, Suspense } from 'react';
import { Badge, Box, Button, Switch, Text, Skeleton } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { AnyAction, bindActionCreators, Dispatch } from 'redux';

import { useIntlBankTransfer } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/states';
import MethodSection from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/Section';
import { IntlBankTransferProps } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/types';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';

const ActivationModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlBankTransferActivationModal' */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal'
    ),
);

const Details = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlBankTransferDetails' */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/Details'
    ),
);

const FailureBanner = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlBankTransferFailureBanner' */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/FailureBanner'
    ),
);

const ExportLinkBanner = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlBankTransferExportLinkBanner' */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ExportLinkBanner'
    ),
);

const MethodIntlBankTransfer = ({ item, user, showNotification }: IntlBankTransferProps) => {
  const {
    status,
    isLoading,
    isActivated,
    isActivating,
    isEddVerified,
    isToggleLoading,
    isActivationModalOpen,
    iecCode,
    purposeCode,
    purposeCodeDesc,
    hasPromoterPanName,
    canRequestActivation,
    activationModalCurrentStep,
    handleActivate,
    handleFailureRetry,
    handleAccountStatusToggle,
    handleActivationModalOpen,
    handleSingleAccountActivation,
  } = useIntlBankTransfer({
    user,
    showNotification,
  });

  if (!item) {
    return null;
  }

  return (
    <MethodSection
      id={item.slug || 'money-saver-export'}
      header={item.header}
      description={item.listDescription}
      trailing={
        !isLoading ? (
          <>
            {canRequestActivation && (
              <Button onClick={() => handleActivate()} isLoading={isActivating}>
                Activate
              </Button>
            )}
            {status && (
              <Box display="flex" gap="spacing.2" alignItems="center">
                {isToggleLoading ? (
                  <Text size="small">Saving</Text>
                ) : (
                  <Badge color={isActivated ? 'positive' : 'negative'}>
                    {isActivated ? 'Active' : 'Deactivated'}
                  </Badge>
                )}{' '}
                <Switch
                  isChecked={isActivated}
                  onChange={handleAccountStatusToggle}
                  accessibilityLabel="toggle virtual accounts"
                  isDisabled={isToggleLoading}
                />
              </Box>
            )}
          </>
        ) : (
          <Skeleton width="90px" height="30px" />
        )
      }
    >
      {!status && (
        <Suspense fallback={null}>
          <FailureBanner onRetry={handleFailureRetry} />
        </Suspense>
      )}

      {hasPromoterPanName && isActivated && (
        <Suspense fallback={null}>
          <ExportLinkBanner />
        </Suspense>
      )}

      <Suspense fallback={null}>
        {item.leafList?.map((listItem) => {
          return (
            <Details
              key={listItem.slug}
              item={listItem}
              isLoading={isActivating}
              onAccountActivate={handleSingleAccountActivation}
            />
          );
        })}
      </Suspense>

      <Suspense fallback={null}>
        <ActivationModal
          isOpen={isActivationModalOpen}
          purposeCode={purposeCode}
          purposeCodeDesc={purposeCodeDesc}
          iecCode={iecCode}
          promoterPanName={user?.promoter_pan_name}
          isEddVerified={isEddVerified}
          step={activationModalCurrentStep}
          onDismiss={handleActivationModalOpen}
        />
      </Suspense>
    </MethodSection>
  );
};

const mapStateToProps = ({ session }) => ({
  user: session.user,
});

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) => {
  return bindActionCreators(
    {
      showNotification: showNotificationFn,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(MethodIntlBankTransfer);

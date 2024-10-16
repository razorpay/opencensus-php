import React, { useEffect, useState } from 'react';
import { Box, Modal, ModalBody, ModalFooter } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { User } from 'common/typings';
import lazy from 'merchant/routes/LazyLoader';
import { getIsInviteFlowEnabled } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/utils/tabsData';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE, ORG_NAME } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

import ChooseOAuthApp from './components/ChooseOAuthApp';
import ModalHeader from './components/ModalCommon/ModalHeader';
import SelectProduct from './components/SelectProduct';
import { INVITE_MERCHANT_STEPS } from './constants';

const InviteMerchantTabs = lazy(
  () => import(/* webpackChunkName: "InviteMerchantTabs" */ './components/InviteMerchantTabs'),
);

const { SELECT_PRODUCT, INVITE_TABS, CHOOSE_OAUTH_APP } = INVITE_MERCHANT_STEPS;

const getModalHeaderText = (
  productType: string,
  currentStep: string,
  orgName: string,
  { isInviteFlowEnabled }: { isInviteFlowEnabled: boolean },
): string => {
  switch (currentStep) {
    case SELECT_PRODUCT:
      return isInviteFlowEnabled ? 'Add New Clients' : 'Add New Merchants';
    case CHOOSE_OAUTH_APP:
      return 'Choose app to refer';
    case INVITE_TABS:
    default:
      switch (productType) {
        case PRODUCT_TYPE.X:
          return 'Add New Merchants - RazorpayX';
        case PRODUCT_TYPE.CAPITAL:
          return 'Add New Merchants - Line Of Credit';
        case PRODUCT_TYPE.POS:
          return 'Add New Clients - Razorpay POS';
        case PRODUCT_TYPE.PG:
        default:
          if (isInviteFlowEnabled) return `Add New Clients - ${orgName} Payments`;
          return `Add New Merchants - ${orgName} Payments`;
      }
  }
};

type InviteMerchantModalProps = {
  user: User;
  org: Org;
  isOpen: boolean;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  initialProductType?: string;
  initialStep: string;
};
const InviteMerchantModal = ({
  user,
  org,
  initialProductType = PRODUCT_TYPE.PG,
  initialStep,
  isOpen,
  onDismiss,
  onAddSuccess,
}: InviteMerchantModalProps): JSX.Element => {
  const [selectedProductType, setProductType] = useState<string | null>(null);
  const [selectedApp, setSelectedApp] = useState<string | null>(null);
  const [selectedStep, setCurrentStep] = useState<string | null>(null);
  const [shouldShowHeaderAndTabs, setShowHeaderAndTabs] = useState(true);
  const [shouldShowFooter, setShouldShowFooter] = useState(true);
  const experiments = usePartnerDashboardExperiments();
  // Note: we need the defaults outside useState because the component may not remount.
  const productType = selectedProductType || initialProductType;
  const currentStep = (selectedStep || initialStep) as INVITE_MERCHANT_STEPS;
  useEffect(() => {
    // For back navigation/re-open cases
    setShowHeaderAndTabs(true);
  }, [currentStep]);

  // experiment conditions
  const { isInviteFlowEnabled } = getIsInviteFlowEnabled(productType, experiments);
  const orgName = org?.business_name || ORG_NAME.RZP;
  const modalTitle = getModalHeaderText(productType, currentStep, orgName, {
    isInviteFlowEnabled,
  });

  // cta handlers
  const onSelectProductNextClick = () => {
    if (user.isPartner('pure_platform')) setCurrentStep(CHOOSE_OAUTH_APP);
    else setCurrentStep(INVITE_TABS);
  };
  const onChooseOAuthAppNextClick = () => {
    setCurrentStep(INVITE_TABS);
  };
  const goToAppSelectionStep = () => {
    setCurrentStep(CHOOSE_OAUTH_APP);
  };
  const onInviteTabsBackClick = () => {
    if (user.isPartner('pure_platform')) setCurrentStep(CHOOSE_OAUTH_APP);
    else setCurrentStep(SELECT_PRODUCT);
  };
  const onDismissModal = () => {
    setCurrentStep(null);
    setProductType(null);
    onDismiss();
  };

  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} rank={Ranks.P0} resetOnProps>
      <Modal isOpen={isOpen} onDismiss={onDismissModal} size="small">
        <ModalBody>
          {/* Note: Current ModalHeader from blade doesn't support hiding the divider */}
          <ModalHeader
            modalTitle={shouldShowHeaderAndTabs ? modalTitle : ''}
            onDismiss={onDismissModal}
            showDivider={[SELECT_PRODUCT, CHOOSE_OAUTH_APP].includes(currentStep)}
          />
          {currentStep === SELECT_PRODUCT ? (
            <SelectProduct
              orgName={orgName}
              productType={productType}
              setProductType={setProductType}
              onNextClick={onSelectProductNextClick}
            />
          ) : null}
          {currentStep === CHOOSE_OAUTH_APP ? (
            <ChooseOAuthApp
              selectedApp={selectedApp}
              setSelectedApp={setSelectedApp}
              onNextClick={onChooseOAuthAppNextClick}
            />
          ) : null}
          {currentStep === INVITE_TABS ? (
            <SuspenseWithLoader>
              <InviteMerchantTabs
                onDismiss={onDismiss}
                productType={productType}
                selectedApp={selectedApp}
                shouldShowHeaderAndTabs={shouldShowHeaderAndTabs}
                onAddSuccess={onAddSuccess}
                setShowHeaderAndTabs={setShowHeaderAndTabs}
                onInviteTabsBackClick={onInviteTabsBackClick}
                goToAppSelectionStep={goToAppSelectionStep}
                setShouldShowFooter={setShouldShowFooter}
              />
            </SuspenseWithLoader>
          ) : null}
        </ModalBody>

        {shouldShowFooter ? (
          <ModalFooter>
            <Box backgroundColor="surface.background.gray.intense" height="36px" width="100%" />
          </ModalFooter>
        ) : null}
      </Modal>
    </ErrorBoundary>
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ ...state.session }),
    (dispatch) => bindActionCreators({}, dispatch),
  ),
)(InviteMerchantModal);

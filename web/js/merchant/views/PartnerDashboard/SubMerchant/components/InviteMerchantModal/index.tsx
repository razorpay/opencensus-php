import React, { useEffect, useState } from 'react';
import { Modal, ModalBody } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { User } from 'common/typings';
import lazy from 'merchant/routes/LazyLoader';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import ModalHeader from './components/ModalCommon/ModalHeader';
import SelectProduct from './components/SelectProduct';
import { INVITE_MERCHANT_STEPS } from './constants';

const InviteMerchantTabs = lazy(
  () => import(/* webpackChunkName: "InviteMerchantTabs" */ './components/InviteMerchantTabs'),
);
const { SELECT_PRODUCT, INVITE_TABS } = INVITE_MERCHANT_STEPS;

const getModalHeaderText = (
  productType: string,
  currentStep: string,
  orgName: string,
  { isPGInviteFlow }: { isPGInviteFlow: boolean },
): string => {
  switch (currentStep) {
    case SELECT_PRODUCT:
      return isPGInviteFlow ? 'Add New Clients' : 'Add New Merchants';
    case INVITE_TABS:
    default:
      switch (productType) {
        case PRODUCT_TYPE.X:
          return 'Add New Merchants - RazorpayX';
        case PRODUCT_TYPE.CAPITAL:
          return 'Add New Merchants - Line Of Credit';
        case PRODUCT_TYPE.PG:
        default:
          if (isPGInviteFlow) return `Add New Clients - ${orgName} Payments`;
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
  initialStep?: string;
};
const InviteMerchantModal = ({
  user,
  org,
  initialProductType = PRODUCT_TYPE.PG,
  initialStep = SELECT_PRODUCT,
  isOpen,
  onDismiss,
  onAddSuccess,
}: InviteMerchantModalProps): JSX.Element => {
  const [selectedProductType, setProductType] = useState<string | null>(null);
  const [selectedStep, setCurrentStep] = useState<string | null>(null);
  const [shouldShowHeaderAndTabs, setShowHeaderAndTabs] = useState(true);

  // Note: we need the defaults outside useState because the component may not remount.
  const productType = selectedProductType || initialProductType;
  const currentStep = selectedStep || initialStep;
  useEffect(() => {
    // For back navigation/re-open cases
    setShowHeaderAndTabs(true);
  }, [currentStep]);

  // experiment conditions
  const isPGInviteFlow = user.isPartnershipsInviteFlowEnabled && productType == PRODUCT_TYPE.PG;

  const orgName = org?.business_name || 'Razorpay';
  const modalTitle = getModalHeaderText(productType, currentStep, orgName, { isPGInviteFlow });

  // cta handlers
  const onSelectProductNextClick = () => {
    setCurrentStep(INVITE_TABS);
  };
  const onInviteTabsBackClick = () => {
    setCurrentStep(SELECT_PRODUCT);
  };
  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} rank={Ranks.P0} resetOnProps>
      {/* // Note: zIndex for sidenav in the dashboard is 1111 */}
      <Modal zIndex={1112} isOpen={isOpen} onDismiss={onDismiss} size="small">
        <ModalBody>
          <ModalHeader
            modalTitle={shouldShowHeaderAndTabs ? modalTitle : ''}
            onDismiss={onDismiss}
            showDivider={currentStep === SELECT_PRODUCT}
          />
          {currentStep === SELECT_PRODUCT ? (
            <SelectProduct
              orgName={orgName}
              productType={productType}
              setProductType={setProductType}
              onNextClick={onSelectProductNextClick}
              isOnboardingDisabled
            />
          ) : null}
          {currentStep === INVITE_TABS ? (
            <SuspenseWithLoader>
              <InviteMerchantTabs
                onDismiss={onDismiss}
                productType={productType}
                shouldShowHeaderAndTabs={shouldShowHeaderAndTabs}
                onAddSuccess={onAddSuccess}
                setShowHeaderAndTabs={setShowHeaderAndTabs}
                onInviteTabsBackClick={onInviteTabsBackClick}
              />
            </SuspenseWithLoader>
          ) : null}
        </ModalBody>
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

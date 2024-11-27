import React, { useEffect, useState, Suspense } from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { useI18Service } from 'common/i18';
import DashboardBanner from 'common/ui/DashboardBanner';
import Loader from 'common/ui/Loader';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { trackShorterKYCEvents } from 'common/utils/analytics';
import ShowWhen from 'merchant/components/ShowWhen';
import { merchantFetch } from 'merchant/utils/ajax';
import ActivationGuide from 'merchant/views/PartnerDashboard/Home/Components/ActivationGuide';
import POSReferralGuide from 'merchant/views/PartnerDashboard/Home/Components/POS/POSReferralGuide';
import { CapitalReferralCard } from 'merchant/views/PartnerDashboard/Home/Components/ReferralGuide/CapitalReferralCard';
import ReferralGuide from 'merchant/views/PartnerDashboard/Home/Components/ReferralGuide/index';
import {
  showActivationConfetti,
  getExperimentsForTracking,
} from 'merchant/views/PartnerDashboard/Home/Components/utils';
import {
  AddMerchantSource,
  FUXStatusStateT,
  FUXStatusT,
  PartnerHomeT,
} from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import AddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/AddMerchant';
import 'merchant/views/PartnerDashboard/Home/home.styl';
import InviteMerchantModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal';
import { INVITE_MERCHANT_STEPS } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/constants';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import PageHeading from './Components/PageHeading';

const PurePlatformSwitchGuideLazy = React.lazy(
  () => import('merchant/views/PartnerDashboard/Home/Components/PurePlatformSwitch'),
);

const Home = ({
  user,
  showNotification,
  openModal,
  closeModal,
  history,
  org,
  partnerSwitchFlag,
}: PartnerHomeT): JSX.Element => {
  const { isPartnershipsInviteFlowEnabled, isPartnershipsForPosEnabled, is2FaEnabled } =
    usePartnerDashboardExperiments();
  const { criticalFlow } = useTwoFactorVerificationContext();
  const [FUXStatus, setFUXStatus] = useState<FUXStatusStateT>({
    value: null,
    isFetching: true,
  });
  const [isInviteMerchantModalOpen, setIsInviteMerchantModalOpen] = useState(false);
  const [inviteMerchantModalProductType, setInviteMerchantProductType] = useState('');

  const merchant = user.merchants[user.current];
  const partnerName = merchant.name;
  const { isPartnershipForCapitalEnabled } = user;
  const partnerId = user?.merchant?.id;
  const { isConfigTagEnabled } = useI18Service();

  const loadData = async () => {
    try {
      const { data } = await merchantFetch({
        url: 'partner/first_user_experience',
        method: 'get',
        mode: 'live',
      });
      const fux_status = data as FUXStatusT;
      setFUXStatus({
        value: fux_status,
        isFetching: false,
      });
    } catch (_) {
      showNotification({
        type: 'error',
        message: 'An error occurred in connecting to the server',
        hidePrevious: true,
      });
    }
  };

  useEffect(() => {
    loadData();
    trackShorterKYCEvents({
      objectName: 'Partner Dashboard',
      actionName: 'Loaded',
      screen: 'Partner Dashboard',
      properties: {},
    });
  }, []);

  const onAddMerchantSuccess = () => {
    showActivationConfetti('start-referring', true);
    if (FUXStatus.value?.first_submerchant_added === false) {
      const value = {
        ...FUXStatus.value,
        first_submerchant_added: true,
      };
      setFUXStatus({
        isFetching: false,
        value,
      });
    }
  };
  const handleReferClient = (source: AddMerchantSource, type?: string): void => {
    if (isPartnershipsInviteFlowEnabled) {
      setIsInviteMerchantModalOpen(true);
      setInviteMerchantProductType(type || '');
    } else if (is2FaEnabled) {
      criticalFlow({
        enforceVerifyOtp: true,
        modes: ['live', 'test'],
        onUserTwoFaVerified: () => {
          openModal({
            size: 'med-large',
            component: (
              <AddMerchant
                closeModal={closeModal}
                addType={type}
                onAddSuccess={onAddMerchantSuccess}
                source={source}
                org={org}
                isConfigTagEnabled={isConfigTagEnabled}
              />
            ),
          });
        },
      });
    } else {
      openModal({
        size: 'med-large',
        component: (
          <AddMerchant
            closeModal={closeModal}
            addType={type}
            onAddSuccess={onAddMerchantSuccess}
            source={source}
            org={org}
            isConfigTagEnabled={isConfigTagEnabled}
          />
        ),
      });
    }
  };

  const handleCapitalRefer = (): void => {
    history.push({
      pathname: '/partners/submerchants/capital',
      state: { addType: PRODUCT_TYPE.CAPITAL },
    });
  };

  /**
   *
   * @returns false to hide the banner and true to show it
   */
  const isShowPartnerSwitch = () => {
    if (partnerSwitchFlag === true) {
      return false;
    }
    return FUXStatus.value?.partner_migration_enabled;
  };

  const isFirstReferralDone = FUXStatus.value?.first_submerchant_added === true;
  const trackingExperiments = getExperimentsForTracking(user);

  return (
    <div className="partner-dashboard-home">
      <DashboardBanner />
      <PageHeading org={org} user={user} partnerName={partnerName} />
      <ShowWhen
        additionalCondition={(currentUser) =>
          currentUser.isPartner() && !currentUser.isPartner('fully_managed')
        }
      >
        <ActivationGuide
          handleReferClient={handleReferClient}
          fuxStatus={FUXStatus}
          partnerName={partnerName}
          user={user}
          org={org}
        />
      </ShowWhen>
      <ShowWhen
        myRole="owner manager admin"
        additionalCondition={(currentUser) =>
          // TODO v2: enable referral guide for platform partners
          currentUser.isPartner() && !currentUser.isPartner('pure_platform')
        }
      >
        <ShowWhen additionalCondition={() => isPartnershipsForPosEnabled}>
          <POSReferralGuide handleReferClient={handleReferClient} />
        </ShowWhen>
        <ReferralGuide
          partnerName={partnerName}
          isFirstReferralDone={isFirstReferralDone}
          isFetching={FUXStatus.isFetching}
          handleReferClient={handleReferClient}
          org={org}
        />
        <ShowWhen additionalCondition={() => isPartnershipForCapitalEnabled}>
          <CapitalReferralCard handleReferClient={handleCapitalRefer} mid={partnerId} />
        </ShowWhen>
      </ShowWhen>
      <ShowWhen
        additionalCondition={(currentUser) =>
          currentUser.isPartner('reseller') && isShowPartnerSwitch()
        }
      >
        <Suspense fallback={<Loader />}>
          <PurePlatformSwitchGuideLazy
            openModal={openModal}
            closeModal={closeModal}
            trackingExperiments={trackingExperiments}
          />
        </Suspense>
      </ShowWhen>

      {isPartnershipsInviteFlowEnabled ? (
        <InviteMerchantModal
          isOpen={isInviteMerchantModalOpen}
          onAddSuccess={onAddMerchantSuccess}
          initialStep={
            inviteMerchantModalProductType === ''
              ? INVITE_MERCHANT_STEPS.SELECT_PRODUCT
              : INVITE_MERCHANT_STEPS.INVITE_TABS
          }
          initialProductType={inviteMerchantModalProductType}
          onDismiss={() => setIsInviteMerchantModalOpen(false)}
        />
      ) : null}
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  org: state.session.org,
  partnerSwitchFlag: state.partnerDashboard.partnerSwitchFlag,
});

const getDispatchToProps = () => ({
  showNotification,
  openModal,
  closeModal,
});

export default compose<any>(
  rTracking({
    page: 'PartnerHome',
  }),
  withRouter,
  connect(mapStateToProps, getDispatchToProps()),
)(Home);

import React, { useEffect, useState, Suspense } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import { withRouter } from 'common/deprecated/withRouter';
import { merchantFetch } from 'merchant/utils/ajax';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  AddMerchantSource,
  FUXStatusStateT,
  FUXStatusT,
  PartnerHomeT,
} from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import ShowWhen from 'merchant/components/ShowWhen';
import AddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/AddMerchant';
import ActivationGuide from 'merchant/views/PartnerDashboard/Home/Components/ActivationGuide';
import ReferralGuide from 'merchant/views/PartnerDashboard/Home/Components/ReferralGuide/index';
import {
  showActivationConfetti,
  getExperimentsForTracking,
} from 'merchant/views/PartnerDashboard/Home/Components/utils';
import 'merchant/views/PartnerDashboard/Home/home.styl';
import Loader from 'common/ui/Loader';
import DashboardBanner from 'common/ui/DashboardBanner';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { CapitalReferralCard } from 'merchant/views/PartnerDashboard/Home/Components/ReferralGuide/CapitalReferralCard';
import PageHeading from './Components/PageHeading';
import InviteMerchantModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

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
  const { isEasierAccessToSubmerchantKycEnabled } = usePartnerDashboardExperiments();
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

  const loadData = async () => {
    try {
      const { data } = await merchantFetch({
        url: 'partner/first_user_experience',
        method: 'get',
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
    if (isEasierAccessToSubmerchantKycEnabled) {
      setIsInviteMerchantModalOpen(true);
      setInviteMerchantProductType(type || '');
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
          currentUser.isPartner() && !currentUser.isPartner('pure_platform')
        }
      >
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
          currentUser.isPartner('reseller') &&
          currentUser?.isEnablePurePlatformSwitch &&
          isShowPartnerSwitch()
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

      {isEasierAccessToSubmerchantKycEnabled ? (
        <InviteMerchantModal
          isOpen={isInviteMerchantModalOpen}
          onAddSuccess={onAddMerchantSuccess}
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

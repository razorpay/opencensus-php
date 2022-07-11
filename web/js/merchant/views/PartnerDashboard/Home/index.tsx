import React, { useEffect, useState, Suspense } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import rTracking from 'react-tracking';
import axios from 'axios';
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
import CommissionCardBody from 'merchant/views/PartnerDashboard/Commissions/components/FUX-Cards/CommissionCard/CardBody';
import { showActivationConfetti } from 'merchant/views/PartnerDashboard/Home/Components/utils';
import './home.styl';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import Loader from 'common/ui/Loader';

const AggregatorFormLazy = React.lazy(() =>
  import('merchant/views/PartnerDashboard/Home/Components/ReferralGuide/AggregatorForm'),
);
const AggregatorSuccessLazy = React.lazy(() =>
  import('merchant/views/PartnerDashboard/Home/Components/ReferralGuide/AggregatorSuccess'),
);

const Home = ({ user, showNotification, openModal, closeModal, tracking }: PartnerHomeT) => {
  const [FUXStatus, setFUXStatus] = useState<FUXStatusStateT>({
    value: null,
    isFetching: true,
  });
  const [value, setValue] = useState(0);

  const merchant = user.merchants[user.current];
  const partnerName = merchant.name;

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
    openModal({
      size: 'med-large',
      component: (
        <AddMerchant
          closeModal={closeModal}
          addType={type}
          onAddSuccess={onAddMerchantSuccess}
          source={source}
        />
      ),
    });
  };

  const onSuccessClose = () => {
    setValue(value + 1);
    closeModal();
  };

  const handleSubmitAggregator = (
    phone_number: number | null,
    reason: string,
    will_handle_risk: boolean,
    website_url: string,
    business_type: string,
    other_business_type: string,
  ): void => {
    axios({
      method: 'post',
      url: 'https://hooks.zapier.com/hooks/catch/12775470/bwyqe0h/',
      data: {
        partner_id: user?.merchant?.id,
        phone_number,
        name: user?.name,
        email: user?.email,
        reason,
        will_handle_risk,
        website_url,
        business_type,
        other_business_type,
      },
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
    })
      .then((response) => {
        if (response.status == 200) {
          localStorage.setItem('aggregatorApplicationSubmit', 'true');
          closeModal();
          openModal({
            size: isMobileAndTablet() ? 'full-screen' : 'xlarge',
            className: 'full-screen-mobile',
            component: (
              <Suspense fallback={<Loader />}>
                <AggregatorSuccessLazy
                  closeModal={onSuccessClose}
                  isMobileAndTablet={isMobileAndTablet()}
                />
              </Suspense>
            ),
          });
        }
      })
      .catch((err) => {
        console.log(err);
      });
  };

  const handleAggregatorApplyNow = (): void => {
    tracking?.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_aggr_req.initiate _agg_req', {
        device: isMobileAndTablet() ? 'mobile' : 'desktop',
        mid: user?.merchant?.id,
      }),
    );

    tracking?.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_aggr_req.open_form', {
        device: isMobileAndTablet ? 'mobile' : 'desktop',
        mid: user?.merchant?.id,
      }),
    );

    openModal({
      size: isMobileAndTablet() ? 'full-screen' : 'xlarge',
      className: 'full-screen-mobile',
      component: (
        <Suspense fallback={<Loader />}>
          <AggregatorFormLazy
            closeModal={closeModal}
            isMobileAndTablet={isMobileAndTablet()}
            handleSubmitAggregator={handleSubmitAggregator}
            contactNumber={Number(user?.contact_mobile)}
            mid={user?.merchant?.id}
          />
        </Suspense>
      ),
    });
  };

  const isFirstReferralDone = FUXStatus.value?.first_submerchant_added === true;
  const isFirstInvoiceGen = FUXStatus.value?.first_commission_payout === true;
  const isUserOwner = user?.role === 'owner';
  return (
    <div className="partner-dashboard-home">
      <h2 className="page-heading">{`Welcome to Partner dashboard, ${partnerName}!`}</h2>
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
          handleAggregatorApplyNow={handleAggregatorApplyNow}
          isUserOwner={isUserOwner}
          user={user}
        />
      </ShowWhen>

      <ShowWhen
        additionalCondition={(currentUser) =>
          currentUser.isPartner() && currentUser.isPartner('pure_platform') && isFirstInvoiceGen
        }
      >
        <div className="fux-commission-cards home-view">
          <CommissionCardBody />
        </div>
      </ShowWhen>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
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
  connect(mapStateToProps, getDispatchToProps()),
)(Home);

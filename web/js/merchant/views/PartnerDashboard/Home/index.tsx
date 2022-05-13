import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
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

const Home = ({ user, showNotification, openModal, closeModal }: PartnerHomeT) => {
  const [FUXStatus, setFUXStatus] = useState<FUXStatusStateT>({
    value: null,
    isFetching: true,
  });

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

  const isFirstReferralDone = FUXStatus.value?.first_submerchant_added === true;
  const isFirstInvoiceGen = FUXStatus.value?.first_commission_payout === true;
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

export default connect(mapStateToProps, getDispatchToProps())(Home);

import React, { useEffect, useState } from 'react';
import ReferralGuide from './Components/ReferralGuide';
import { connect } from 'react-redux';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import './home.styl';

interface FUXStatusT {
  api_integration: boolean;
  first_commission_payout: boolean;
  first_earning_generated: boolean;
  first_submerchant_added: boolean;
}
interface FUXStatusStateT {
  value: FUXStatusT | null;
  isFetching: boolean;
}

const Home = ({ user, showNotification }) => {
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

  return (
    <div className="partner-dashboard-home">
      <h2 className="page-heading">{`Welcome to Partner dashboard, ${partnerName}!`}</h2>
      <ShowWhen
        additionalCondition={(currentUser) => currentUser.isPartner('reseller', 'aggregator')}
      >
        <ReferralGuide
          partnerName={partnerName}
          isFirstReferralDone={FUXStatus.value?.first_submerchant_added}
          isFetching={FUXStatus.isFetching}
        />
      </ShowWhen>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const getDispatchToProps = () => ({
  showNotification,
});

export default connect(mapStateToProps, getDispatchToProps())(Home);

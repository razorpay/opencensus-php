import { useEffect } from 'react';
import { connect } from 'react-redux';
import RootList from './components/RootList';
import IntermediateList from './components/IntermediateList';
import LeafList from './components/LeafList';
import Spinner from 'common/ui/Spinner';
import Banner from 'common/ui/Banner';
import { showNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import {
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  clearIntermediateInstrument,
  clearLeafInstrument,
  setLoading,
} from 'merchant/reducers/instrumentRequests';

const PaymentMethod = ({
  intermediateInstrument,
  loading,
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  clearIntermediateInstrument,
  clearLeafInstrument,
  setLoading,
  showNotification,
}) => {
  useEffect(() => {
    setLoading();
    fetchAllIntruments();
    return () => {
      clearIntermediateInstrument();
      clearLeafInstrument();
    };
  }, [fetchMerchantInstruments, fetchRequestedInstruments]);

  const fetchAllIntruments = async () => {
    try {
      await fetchMerchantInstruments();
      await fetchRequestedInstruments();
    } catch (errors) {
      showNotification({
        type: 'error',
        message: errors[0],
      });
    }
    return;
  };

  const isActivatedUser = window.rzp_user.activation_status === 'activated';

  return loading ? (
    <div class="page-spinner-container">
      <Spinner />
    </div>
  ) : (
    <>
      {!isActivatedUser && (
        <Banner className="no-margin">
          <span>
            <i className="i i-info-outline"></i> KYC verification is mandatory to request for new
            payment methods. Please complete your
            <a> activation form</a>, if not done already.
          </span>
        </Banner>
      )}

      <div class="content-wrapper" id="settings-payment-methods">
        <div class="panel-heading">
          <span class="title">Manage Payment Methods </span>
          &nbsp;
          <span class="toggler-btn">
            <a
              href="https://razorpay.com/docs/payment-gateway/dashboard-guide/settings/payment-methods/"
              target="_blank"
              rel="noreferrer"
              onClick={() =>
                analyticsTrack({
                  objectName: 'know more',
                  actionName: 'clicked',
                  screen: 'settings',
                  properties: {
                    location: 'Payment Methods',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                })
              }
            >
              Know More <i class="i i-external-link" style={{ marginLeft: '5px' }} />
            </a>
          </span>
          <div style={{ marginTop: '5px', marginBottom: '20px' }}>
            We offer a host of payment methods. Some of them are available by default, while others
            require approval. Raise a request directly from here to enable such payment methods.
          </div>
          {/* list view starts*/}
          <div class="methods-view">
            <RootList />
            {intermediateInstrument && Array.isArray(intermediateInstrument.intermediateList) && (
              <IntermediateList instrument={intermediateInstrument} />
            )}
            <LeafList />
          </div>
        </div>
      </div>
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    intermediateInstrument: state.instrumentRequests.intermediateInstrument,
    loading: state.instrumentRequests.loading,
  };
};

export default connect(mapStateToProps, {
  setLoading,
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  clearIntermediateInstrument,
  clearLeafInstrument,
  showNotification,
})(PaymentMethod);

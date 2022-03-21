import { useEffect } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import RootList from './components/RootList';
import IntermediateList from './components/IntermediateList';
import LeafList from './components/LeafList';
import Spinner from 'common/ui/Spinner';
import Banner from 'common/ui/Banner';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { showNotification as sN } from 'merchant_common/reducers/notifications';
import { trackLinkClick } from 'merchantLA/containers/TestModeBanner/ga';

import {
  fetchMerchantInstruments as fMI,
  fetchRequestedInstruments as fRI,
  clearIntermediateInstrument as cII,
  clearLeafInstrument as cLI,
  setLoading as sL,
  getDiscrepanciesCategories as gDC,
} from 'merchant/reducers/instrumentRequests';

const user = window.rzp_user;

const PaymentMethod = (props) => {
  const {
    intermediateInstrument,
    loading,
    fMI: fetchMerchantInstruments,
    fRI: fetchRequestedInstruments,
    cII: clearIntermediateInstrument,
    cLI: clearLeafInstrument,
    sL: setLoading,
    sN: showNotification,
    gDC: getDiscrepanciesCategories,
  } = props;

  const fetchAllIntruments = async () => {
    await Promise.all([
      fetchMerchantInstruments(),
      fetchRequestedInstruments(),
      getDiscrepanciesCategories(),
    ]).catch((errors) => {
      showNotification({
        type: 'error',
        message: errors[0],
      });
    });
  };

  const onClickKnowMore = () => {
    analyticsTrack({
      objectName: 'know more',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'Payment Methods',
        ...getCommonAnalyticsProperties(user),
      },
    });
  };

  useEffect(() => {
    setLoading();
    fetchAllIntruments();
    return () => {
      clearIntermediateInstrument();
      clearLeafInstrument();
    };
  }, [fetchMerchantInstruments, fetchRequestedInstruments]);

  const isActivatedUser = user.activation_status === 'activated';

  return loading ? (
    <div className="page-spinner-container">
      <Spinner />
    </div>
  ) : (
    <>
      {!isActivatedUser && (
        <Banner className="no-margin">
          <span>
            <i className="i i-info-outline" /> KYC verification is mandatory to request for new
            payment methods. Please complete your
            <Link to="/activation" onClick={() => trackLinkClick('Go To - Activation Form')}>
              &nbsp; activation form
            </Link>
            , if not done already.
          </span>
        </Banner>
      )}

      <div className="content-wrapper" id="settings-payment-methods">
        <div className="panel-heading">
          <span className="title">Manage Payment Methods </span>
          &nbsp;
          <span className="toggler-btn">
            <a
              href="https://razorpay.com/docs/payment-gateway/dashboard-guide/settings/payment-methods/"
              target="_blank"
              rel="noopener noreferrer"
              onClick={onClickKnowMore}
            >
              Know More <i className="i i-external-link" style={{ marginLeft: '5px' }} />
            </a>
          </span>
          <div style={{ marginTop: '5px', marginBottom: '20px' }}>
            We offer a host of payment methods. Some of them are available by default, while others
            require approval. Raise a request directly from here to enable such payment methods.
          </div>
          <div className="methods-view">
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
  sL,
  fMI,
  fRI,
  cII,
  cLI,
  sN,
  gDC,
})(PaymentMethod);

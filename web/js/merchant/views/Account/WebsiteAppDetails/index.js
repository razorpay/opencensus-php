import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { fetchActivationDetails } from 'merchant/reducers/activation';
import Loader from 'common/ui/Loader';
import * as ModalActions from 'merchant_common/reducers/modals';

function WebsiteAppDetails({ fetchActivationDetails, activationData }) {
  useEffect(() => {
    fetchActivationDetails();
  }, [fetchActivationDetails]);

  if (!Object.keys(activationData).length)
    return (
      <div className="website-app-details-container">
        <Loader />
      </div>
    );

  const businessWebsiteUrl = activationData.business_website || '--';
  const appStoreUrl = activationData.appstore_url || '--';
  const playStoreUrl = activationData.playstore_url || '--';

  const onButtonClick = () => {};

  return (
    <div className="website-app-details-container">
      <div className="section-content">
        <div className="section-header">
          <span className="website-app-info-status details-required">
            <p>UNDER VERIFICATION</p>
          </span>
          <p className="text">
            Update your business information now as per RBI guidelines to avoid settlements being
            put on-hold.
          </p>
          <div className="comment">
            Ops comment will come here. Your cancellation and refund page does not match
            requirements. <p>View more</p>
          </div>
        </div>
        <div className="section-body">
          <div>
            <span>Website</span>
            <p>{businessWebsiteUrl}</p>
          </div>
          <div>
            <span>Android app</span>
            <p>{playStoreUrl}</p>
          </div>
          <div>
            <span>iOS app</span>
            <p>{appStoreUrl}</p>
          </div>
        </div>
        <div className="section-footer">
          <button onClick={onButtonClick}>Update details</button>
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  activationData: state.activation.data,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchActivationDetails, ...ModalActions }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(WebsiteAppDetails);

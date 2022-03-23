import React from 'react';
import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { AsyncBtn } from 'common/new-ui/Button';
import './CrossSellModal.styl';

const CrossSellSubscriptionsModal = ({ user }) => {
  let url = '';
  if (user.isCSSEducationEnabled) url = 'cross_sell_subscription_education_campaign.svg';
  else url = 'cross_sell_subscription_other_business_campaign.svg';
  return (
    <>
      <div id="crossSellModalBody">
        <img
          className="background-img"
          src={`${window?.cdnBaseUrl}/static/assets/growth-assets/banner/${url}`}
          alt="image"
        />
      </div>
      <div id="crossSellModalFooter">
        <AsyncBtn.Primary
          className="btn"
          type="submit"
          onClick={() => window.open('/app/plans ', '_blank')}
        >
          Create a Subscription Plan
        </AsyncBtn.Primary>
      </div>
    </>
  );
};

export default compose(connect(null, { closeModal: closeModalProp }))(CrossSellSubscriptionsModal);

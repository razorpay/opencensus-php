import React, { useState } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';

function CovidSideBar(props) {
  return (
    <div class="covid-info-sidebar">
      <div>
        {' '}
        <strong>How your customers see it</strong>
      </div>
      <div>
        <ul>
          <li>Customers will be able to donate only post successfull paymment</li>
          <li>You will not have any impact on your payments</li>
          <li>We will not show it for failed or pending payments</li>
        </ul>
      </div>
      <div>
        <img src="https://cdn.razorpay.com/static/assets/covid-relief/Checkout.svg" />
      </div>
    </div>
  );
}

function CovidKnowMore(props) {
  const title = props.user.isFeatureEnabled('covid_19_relief')
    ? 'Donations enabled on Checkout'
    : 'Donations disabled on Checkout';

  return (
    <>
      <div class="covid-know-more-container">
        <div class="modal-header">
          <div class="covid__donations">
            {props.user.isFeatureEnabled('covid_19_relief') ? (
              <i class="i i-done" style={{ color: '#1F890E' }} />
            ) : (
              <i class="i i-Donate" />
            )}{' '}
            <h3 class="modal-title">{title}</h3>
          </div>
        </div>
        <div class="description">
          This will only appear for Standard Checkout users. It will not be shown to Android and iOS
          app users.
        </div>
        <div class="content">
          <div class="upper-panel">
            <div class="info">
              <div class="headline">
                <strong> All donations will be towards verified NGOs like below</strong>
              </div>
              <div class="item">
                <div class="title">
                  <img src="https://lp.razorpay.com/hubfs/Zomato.png" height="50" width="50" />
                  <span>
                    <strong>Help Save My India</strong>
                    <i>by Zomato</i>
                  </span>
                </div>
                <div class="bio">
                  Zomato Feeding India is a not-for-profit, designing interventions to help
                  hospitals and patients with oxygen, food, and health support. Help them save
                  thousands of lives.
                </div>
              </div>
              <div class="item">
                <div class="title">
                  <img src="https://lp.razorpay.com/hubfs/ISKON%20.jpeg" height="50" width="50" />
                  <span>
                    <strong> Donate for Food for Life- ANNAD...</strong>
                    <i>by Iskon</i>
                  </span>
                </div>
                <div class="bio">
                  ISKCON is distributing free meals for poor and needy people who have been affected
                  and are suffering from hunger in this Covid 19 pandemic.
                </div>
              </div>
              <div class="item">
                <div class="title">
                  <img src="https://lp.razorpay.com/hubfs/Hemkunt.jpeg" height="50" width="50" />
                  <span>
                    <strong> Seeking funds for O2 Cylinders</strong>
                    <i>by Hemkunt Foundation</i>
                  </span>
                </div>
                <div class="bio">
                  Hemkunt Foundation is seeking funds for O2 Cylinders to help COVID patients across
                  the country. The cylinders will be distributed to those in need.
                </div>
              </div>
            </div>
          </div>
          <div class="lower-panel">
            <button class="btn btn-primary" onClick={props.closeModal}>
              Close
            </button>
          </div>
        </div>
      </div>
      <CovidSideBar />
    </>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(CovidKnowMore);

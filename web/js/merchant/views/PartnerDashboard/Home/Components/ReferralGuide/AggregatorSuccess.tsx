import React from 'react';
import { AggregatorSuccessT } from '../../TypesDeclare/home';
import { compose } from 'redux';
import { reduxForm } from 'redux-form';
import ScrollView from '@razorpay/blade-old/src/atoms/ScrollView';
import Button from 'common/new-ui/Button';

const AggregatorSuccess = ({ closeModal, isMobileAndTablet }: AggregatorSuccessT): JSX.Element => {
  return (
    <ScrollView>
      <div className="agg-container agg-container-success">
        <div className="modal-left-side">
          <div className="left-content">
            <div className="agg-content-header success-msg">
              {isMobileAndTablet ? (
                <>
                  <div>Application Submitted</div>
                  <div>We’ll get back to you soon!</div>
                </>
              ) : (
                'Application Submitted'
              )}
            </div>
            <div className="succ-img" />
          </div>
        </div>
        <div className="modal-right-side">
          <div className="right-header" onClick={closeModal}>
            <button type="button" className="close agg-close">
              <i className="i i-close" />
            </button>
          </div>
          <div className="partner-dashboard-home">
            <div className="activation-guide-card agg-success-card">
              <div className="activation-steps">
                <div className="confetti-wrapper">
                  <canvas id="confettiActivationGuide" />
                </div>
                <div className="agg-activation-step">
                  <div className="activation-step__icon">
                    <img
                      src="https://cdn.razorpay.com/static/assets/partner-dashboard/fux-cards/activation-guide/activation-step-done.svg"
                      alt="completed step icon"
                    />
                    <div className="agg-step-connector">
                      <span className="connector" />
                      <span className="connector" />
                      <span className="connector" />
                    </div>
                  </div>
                  <div className="activation-step__content_agg">
                    <div className="activation-step__title">We have received your request</div>
                    <div className="activation-step__sub-title">&nbsp;</div>
                  </div>
                </div>
                <div className="agg-activation-step">
                  <div className="activation-step__icon">
                    <img
                      src="https://cdn.razorpay.com/static/assets/partner-dashboard/fux-cards/activation-guide/activation-step-current.svg"
                      alt="step icon"
                    />
                    <div className="agg-step-connector">
                      <span className="connector" />
                      <span className="connector" />
                      <span className="connector" />
                      <span className="connector" />
                    </div>
                  </div>
                  <div className="activation-step__content_agg">
                    <div className="activation-step__title">
                      Our team will evaluate your responses and reach out to you in case more
                      details are required.
                    </div>
                    <div className="activation-step__sub-title">
                      (Please note this can take 1-2 weeks)
                    </div>
                  </div>
                </div>
                <div className="activation-step--opaque agg-activation-step">
                  <div className="activation-step__icon">
                    <img
                      src="https://cdn.razorpay.com/static/assets/partner-dashboard/fux-cards/activation-guide/activation-step-current.svg"
                      alt="step icon"
                    />
                    <div className="agg-step-connector" />
                  </div>
                  <div className="activation-step__content_agg">
                    <div className="activation-step__title">
                      Once your request has been approved, you can manage your affiliates Razorpay
                      account
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="agg-success-btn">
            <Button.Primary onClick={closeModal} className={`${isMobileAndTablet && 'full-width'}`}>
              <span className={`${isMobileAndTablet ? 'device--mobile' : 'device--desktop'}`}>
                Go Back to Dashboard
              </span>
            </Button.Primary>
          </div>
        </div>
      </div>
    </ScrollView>
  );
};

export default compose(
  reduxForm({
    form: 'aggregatorSuccess',
  }),
)(AggregatorSuccess);

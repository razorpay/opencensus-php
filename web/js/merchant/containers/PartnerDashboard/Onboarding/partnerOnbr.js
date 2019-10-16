import React from 'react';
import Slider from 'component/Slider';
import S0 from './steps/S0';
import S1 from './steps/S1';
import S2 from './steps/S2';
import S3 from './steps/S3';
import User from 'merchant/models/User';
import { updateSession } from 'merchant/modules/session';
import { merchantFetch } from 'merchant/utils/ajax';
import { connect } from 'react-redux';

@connect(
  state => ({
    session: state.session,
    user: state.session.user,
  }),
  {
    updateSession,
  }
)
export default class BaseScreen extends React.Component {
  state = { role: null };

  onRoleSelect = role => {
    this.setState({ role });
  };

  closeTransaction = (url, data) => {
    const userval = new User({
      ...this.props.user,
      partner_intent: false,
      partner_type: this.state.role,
    });
    this.props.updateSession({ user: userval });
    merchantFetch({
      url: url,
      method: 'PATCH',
      mode: 'live',
      data,
    })
      .then(response => {})
      .catch(err => {});
    this.props.closeModal();
  };

  onCompleteClick = () => {
    this.closeTransaction('merchant/partner_type', {
      partner_type: this.state.role,
    });
  };

  onNotIntrestedClick = () => {
    this.closeTransaction('merchant/partner-intent', { partner_intent: false });
  };
  render() {
    return (
      <div className="partner-onboarding-base-screen">
        <Slider>
          {this.props.user.merchant_partner_intent &&
            (sliderProps => <S0 key={0} sliderProps={sliderProps} />)}
          {sliderProps => <S1 key={1} sliderProps={sliderProps} />}
          {sliderProps => (
            <S2
              key={2}
              sliderProps={sliderProps}
              onRoleSelect={this.onRoleSelect}
              role={this.state.role}
              isExistingUser={this.props.user.merchant_partner_intent}
            />
          )}
          {sliderProps => (
            <S3
              key={3}
              sliderProps={sliderProps}
              onNext={this.onCompleteClick}
            />
          )}
        </Slider>
      </div>
    );
  }
}

import React from 'react';
import Slider, { SliderDots } from 'component/Slider';
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
  state = { type: null };

  onRoleSelect = role => {
    this.setState({ type: role });
  };

  closeTransaction = (url, data) => {
    const userval = new User({
      ...this.props.user,
      partner_intent: false,
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
      partner_type: this.state.type,
    });
  };

  onNotIntrestedClick = () => {
    this.closeTransaction('merchant/partner-intent', { partner_intent: false });
  };

  render() {
    return (
      <div className="partner-onboarding-base-screen">
        <Slider>
          {sliderProps => <S1 key={1} sliderProps={sliderProps} />}
          {sliderProps => (
            <S2
              key={2}
              sliderProps={sliderProps}
              onRoleSelect={this.onRoleSelect}
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

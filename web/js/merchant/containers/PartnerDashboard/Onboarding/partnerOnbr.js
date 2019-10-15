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
  state = { value: null, isNew: false };

  onRoleSelect = role => {
    this.setState({ value: role });
  };

  onCompleteClick = () => {
    const userval = new User({
      ...this.props.user,
      partner_intent: false,
    });
    this.props.updateSession({ user: userval });
    merchantFetch({
      url: 'merchant/instant_activation',
      method: 'POST',
      mode: 'live',
      data: {},
      accountId: this.props.accountId,
    })
      .then(response => {})
      .catch(err => {});
    this.props.closeModal();
  };

  render() {
    return (
      <div className="partner-onboarding-base-screen">
        <Slider>
          {sliderProps => <S0 key={0} sliderProps={sliderProps} />}
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

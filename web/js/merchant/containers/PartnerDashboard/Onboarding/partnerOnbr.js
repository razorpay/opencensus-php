import React from 'react';
import Slider, { SliderDots } from 'component/Slider';
import S1 from './steps/S1';
import S2 from './steps/S2';
import S3 from './steps/S3';
import SlideContoller from './steps/SlideController';
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
  state = { value: null };

  onRoleSelect = role => {
    this.setState({ value: role });
  };

  onCompleteClick = () => {
    const { session, user } = this.props;
    // this.props.updateSession({
    //   user:{
    //     ...user,
    //     partner_intent: false
    //   }
    // });
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

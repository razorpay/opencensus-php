// import { Route, Switch, NavLink } from 'react-router-dom';

// import Applications from 'merchant/containers/Applications';
// import WriteApplicationEntity from 'merchant/containers/Applications/new';
import Slider, { SliderDots } from 'component/Slider';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import Welcome from './welcome';
import Role from './role';
export default class Onboarding extends React.Component {
  state = { value: null };

  onRoleSelect = e => {
    this.setState({ value: e.currentTarget.value });
  };
  render() {
    return (
      <div
        class="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing"
        key="LandingSlide"
      >
        <div class="partner--onbr-img">
          <img
            src="https://razorpay.com/assets/paymentpages/hero-main.svg"
            alt="landing-image"
          />
        </div>

        <div class="partner--onbr-content">
          <Slider>
            {sliderProps => <Welcome {...sliderProps} />}
            {sliderProps => (
              <Role
                {...sliderProps}
                onRoleSelect={this.onRoleSelect}
                value={this.state.value}
              />
            )}
            {sliderProps => <Welcome {...sliderProps} />}
          </Slider>
        </div>
      </div>
    );
  }
}

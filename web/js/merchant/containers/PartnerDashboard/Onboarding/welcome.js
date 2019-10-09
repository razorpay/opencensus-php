import Buttons from './buttons';

export default function Onboarding(props) {
  return (
    <div class="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing">
      <div class="patner--onbr-screen">
        <div class="partner--welcome">
          <span>Welcome to Partner Dashboard</span>
        </div>
        <div class="partner--info">
          <span>
            Razorpay Partner Program helps you offer your customers a complete
            suite of payment products. Start earning revenues with our instant &
            lucrative rewards program!
          </span>
        </div>
        <div class="partner--cmsn">
          <b>
            <span>0.1% commission </span>
          </b>
          <br />
          <span>on every transaction done by your referred merchants.</span>
        </div>
        <Buttons {...props} />
      </div>
    </div>
  );
}

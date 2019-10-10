import Buttons from './buttons';
import CheckboxField from 'rzp/ui/Forms/CheckboxField';

export default function Onboarding(props) {
  return (
    <div class="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing">
      <div class="partner--onbr-screen">
        <div class="partner--welcome">
          <span>Terms & Condition </span>
        </div>
        <div class="partner--tc">
          <iframe src="https://razorpay.com/terms/" />
          <div class="partner--tc-box">
            <div class="form-group">
              <label>
                <input
                  id="checkid"
                  // checked={props.value === 'merc'}
                  onChange={props.onRoleSelect}
                  type="checkbox"
                  name="role"
                  value="merc"
                />
                I accept to the Terms and Conditions
              </label>
            </div>
          </div>
        </div>

        <Buttons {...props} />
      </div>
    </div>
  );
}

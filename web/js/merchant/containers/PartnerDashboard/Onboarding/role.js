import Buttons from './buttons';

export default function Onboarding(props) {
  return (
    <div class="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing">
      <div class="patner--onbr-screen">
        <div class="partner--welcome">
          <span>What do you want to do as a Partner?</span>
        </div>
        <ul className="partner--type">
          <li className={props.value === 'merc' ? 'partner--selected' : ''}>
            <label>
              <input
                id="checkid"
                checked={props.value === 'merc'}
                onChange={props.onRoleSelect}
                type="radio"
                name="role"
                value="merc"
              />
              <div>
                I will onboard and manage my merchant accounts on Razorpay.
              </div>
            </label>
          </li>
          <li className={props.value === 'buss' ? 'partner--selected' : ''}>
            <label>
              <input
                id="checkid"
                checked={props.value === 'buss'}
                onChange={props.onRoleSelect}
                type="radio"
                name="role"
                value="buss"
              />
              <div>I want to connect and refer businesses to Razorpay</div>
            </label>
          </li>
          <div class="partner--role-notes">
            If you’re an Enterprise please contact our partnership team at{' '}
            <a
              href="mailto:someone@example.com?Subject=Hello%20again"
              target="_top"
            >
              partnership@razorpay.com
            </a>
          </div>
        </ul>
        <Buttons {...props} />
      </div>
    </div>
  );
}

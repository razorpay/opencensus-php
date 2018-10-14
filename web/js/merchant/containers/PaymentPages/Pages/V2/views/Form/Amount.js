import EditLayer from '../EditLayer';
import { classList, getFormattedAmount } from 'common/util';

export const AmountField = ({ amountToPay, handleAddAmount }) => {
  const cls = 'Field Field--disabled Field--required';
  const content = (
    <React.Fragment>
      <div class="Field-label">
        Amount
        <span class="symbol--red">*</span>
      </div>
      <div class="Field-content">
        <div class="Field-wrapper">
          {amountToPay ? (
            <input class="Field-el" disabled />
          ) : (
            <EditLayer
              onClick={handleAddAmount}
              style={{ display: 'inline-block' }}
            >
              <span class="btn-link">+ Add Amount</span>
            </EditLayer>
          )}
        </div>
      </div>
    </React.Fragment>
  );

  return amountToPay ? (
    <EditLayer class={cls}>{content}</EditLayer>
  ) : (
    <div class={cls}>{content}</div>
  );
};

export const FormFooter = ({ amountToPay }) => (
  <div id="form-footer">
    <img
      id="fin-logo"
      alt="pay-methods"
      src="https://cdn.razorpay.com/static/assets/pay_methods_branding.png"
    />
    <div class="btn" type="submit" disabled>
      <div>
        <span>Pay ₹{getFormattedAmount(amountToPay)}</span>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          viewBox="0 0 24 24"
        >
          <path d="M0 0h24v24H0z" fill="none" />
          <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" />
        </svg>
      </div>
    </div>
  </div>
);

export const AmountCreator = ({}) => {
  return <div />;
};

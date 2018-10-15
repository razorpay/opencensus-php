import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import EditLayer from '../EditLayer';
import { classList, getFormattedAmount } from 'common/util';

export const AmountField = ({ amountToPay, onAddAmount }) => {
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
              onClick={onAddAmount}
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

export class AmountCreator extends React.PureComponent {
  state = { isDynamicAmount: false, hasStock: false };

  onChange = ({ target }) => {
    const { name, value } = target;
    if (name === 'dynamic_amount') {
      this.setState({
        isDynamicAmount: value == 1 ? true : false,
        hasQuantityPerPerson: 0,
        hasStock: 0,
      });

      document.getElementsByName('amount')[0].value = '';
    } else if (name === 'quantity') {
      this.setState({
        hasQuantityPerPerson: target.checked ? 1 : 0,
      });
    } else if (name === 'stock') {
      this.setState({
        hasStock: value == 1 ? true : false,
      });
    }
  };

  render() {
    const { onClose, onSubmit } = this.props;
    const {
      amount,
      isDynamicAmount,
      hasQuantityPerPerson,
      hasStock,
    } = this.state;

    return (
      <Form onChange={this.onChange} onSubmit={onSubmit}>
        <div class="section section-1">
          <Input
            label="Amount"
            name="amount"
            type="number"
            placeholder="Enter Amount"
            addonBefore="₹"
            autoFocus
            disabled={isDynamicAmount}
          />
          <Input.Check
            name="dynamic_amount"
            fieldLabel="Customer decides this while paying"
          />
        </div>
        <div class="section section-2">
          <Input.Check
            name="quantity"
            fieldLabel="Allow multiple purchases per customer"
            disabled={isDynamicAmount}
            checked={Boolean(hasQuantityPerPerson)}
            autoRender
          />
          <Input.Check
            name="stock"
            autoRender={true}
            disabled={isDynamicAmount}
            checked={Boolean(hasStock)}
            fieldLabel={() => (
              <span>
                This item has limited stock{' '}
                {!!hasStock && (
                  <React.Fragment>
                    of{' '}
                    <Input name="quantity" class="checkbox-Input" autoFocus />{' '}
                    units available
                  </React.Fragment>
                )}
              </span>
            )}
          />
        </div>
        <footer>
          <button type="button" class="btn-link" onClick={onClose}>
            Cancel
          </button>
          <Button.Primary type="submit">Add</Button.Primary>
        </footer>
      </Form>
    );
  }
}

import Form from 'component/Form';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList, getFormattedAmount } from 'common/util';
import EditLayer from '../EditLayer';

export const AmountField = ({ paymentPageEntity = {}, onAddAmount }) => {
  console.log('PAYMENTPAGE ENTITY..', paymentPageEntity);
  const cls = 'Field Field--disabled Field--required';

  const isAmountEntitySet = paymentPageEntity.hasOwnProperty('amount');

  let amountToDisplay;
  if (isAmountEntitySet) {
    amountToDisplay = getFormattedAmount(
      Number(paymentPageEntity.amount || 0) * 100
    );
  }

  const content = (
    <React.Fragment>
      <div class="Field-label">
        Amount
        <span class="symbol--red">*</span>
      </div>
      <div class="Field-content">
        <div class="Field-wrapper">
          {do {
            if (isAmountEntitySet) {
              if (paymentPageEntity.amount) {
                <React.Fragment>
                  <span>
                    <b>₹ {amountToDisplay.split('.')[0]}</b>.{
                      amountToDisplay.split('.')[1]
                    }
                  </span>
                  {paymentPageEntity.allow_multiple_units && (
                    <React.Fragment>
                      <span style={{ margin: '0 24px' }}>×</span>
                      <div
                        class="Field-wrapper Field-wrapper--counter"
                        style={{
                          display: 'inline-block',
                          pointerEvents: 'none',
                        }}
                      >
                        <button type="button" disabled>
                          -
                        </button>
                        <input
                          class="Field-el counter-value"
                          name="field_1"
                          defaultValue="1"
                          disabled
                        />
                        <button type="button" disabled>
                          +
                        </button>
                      </div>
                    </React.Fragment>
                  )}
                </React.Fragment>;
              } else {
                <input class="Field-el" placeholder="Enter Amount" disabled />;
              }
            } else {
              <Button.Transparent
                onClick={onAddAmount}
                style={{ display: 'inline-block' }}
              >
                <span class="btn-link">+ Add Amount</span>
              </Button.Transparent>;
            }
          }}
        </div>
      </div>
    </React.Fragment>
  );

  return isAmountEntitySet ? (
    <EditLayer class={cls} onClick={onAddAmount}>
      {content}
    </EditLayer>
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
        <span>Pay ₹{getFormattedAmount(Number(amountToPay || 0) * 100)}</span>
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
  constructor(props) {
    super(props);
    const field = props.field;
    const isAmountEntitySet = field.hasOwnProperty('amount');

    this.state = {
      hasDynamicAmount: isAmountEntitySet ? !field.amount : false,
      stock: isAmountEntitySet ? field.stock : '',
      hasStock: isAmountEntitySet ? !!field.stock | 0 : false,
      disableSubmit: !isAmountEntitySet,
      allowMultipleUnits: isAmountEntitySet
        ? field.allow_multiple_units
        : false,
    };

    this.defaults = {
      amount: isAmountEntitySet ? field.amount : '',
    };
  }

  onChange = ({ target }) => {
    const { name, value } = target;
    const stateName = target.getAttribute('data-name');

    if (stateName === 'has_dynamic_amount') {
      this.setState({
        hasDynamicAmount: value == 1 ? true : false,
        allowMultipleUnits: 0,
        hasStock: 0,
      });

      document.getElementsByName('amount')[0].value = '';
    } else if (name === 'allow_multiple_units') {
      this.setState({
        allowMultipleUnits: target.checked ? 1 : 0,
      });
    } else if (stateName === 'has_stock') {
      this.setState({
        hasStock: value == 1 ? true : false,
      });
    }

    setTimeout(() => {
      const form = document.getElementsByName('form_creator_amount')[0];
      const amount = document.getElementsByName('amount')[0].value;

      if (
        form.querySelectorAll('.is-invalid').length ||
        (!amount && !this.state.hasDynamicAmount)
      ) {
        this.setState({ disableSubmit: true });
      } else {
        this.setState({ disableSubmit: false });
      }
    });
  };

  render() {
    const { onClose, onSubmit } = this.props;
    const {
      hasDynamicAmount,
      allowMultipleUnits,
      hasStock,
      disableSubmit,
    } = this.state;

    return (
      <Form
        name="form_creator_amount"
        onChange={this.onChange}
        onSubmit={onSubmit}
      >
        <div class="section section-1">
          <Input
            label="Amount"
            name="amount"
            placeholder="Enter Amount"
            defaultValue={this.defaults.amount}
            addonBefore="₹"
            autoFocus
            pattern="^[1-9]+(.([0-9]){1,2})?$"
            disabled={hasDynamicAmount}
          />
          <Input.Check
            data-name="has_dynamic_amount"
            fieldLabel="Customer decides this while paying"
            defaultValue={!!this.state.hasDynamicAmount | 0}
          />
        </div>
        <div class="section section-2">
          <Input.Check
            name="allow_multiple_units"
            fieldLabel="Allow multiple purchases per customer"
            disabled={hasDynamicAmount}
            checked={Boolean(allowMultipleUnits)}
            autoRender
          />
          <Input.Check
            data-name="has_stock"
            autoRender={true}
            disabled={hasDynamicAmount}
            checked={Boolean(hasStock)}
            fieldLabel={() => (
              <span>
                This item has limited stock{' '}
                {!!hasStock && (
                  <React.Fragment>
                    of{' '}
                    <Input
                      name="stock"
                      defaultValue={this.state.stock}
                      class="checkbox-Input"
                      autoFocus
                      step="1"
                      pattern="\d+"
                    />{' '}
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
          <Button.Primary type="submit" disabled={disableSubmit}>
            Add
          </Button.Primary>
        </footer>
      </Form>
    );
  }
}

import { validateAmount } from 'common/utils/validators';
import { classList } from 'common/utils/rzp-utils';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { onChangeNotes } from 'common/new-ui/Input/PairList';
import CustomerSelector from 'merchant/components/CustomerSelector';

const FORM_CLASS_NAME = 'QRCode--Create-Form';

const FIXED_AMOUNT_OPTIONS = [
  {
    name: '0',
    label: 'No',
  },
  {
    name: '1',
    label: 'Yes',
  },
];

const USAGE_OPTIONS = [
  {
    name: 'multiple',
    label: 'Multiple Payments',
  },
  {
    name: 'single',
    label: 'Single Payment',
  },
];

export default class CreationForm extends React.Component {
  state = {
    isSubmitting: false,
    isSubmitDisabled: false,
    formData: {
      usage: 'multiple',
      fixed_amount: false,
    },
  };

  componentDidMount() {
    this.toggleDisableState();
  }

  componentDidUpdate() {
    this.toggleDisableState();
  }

  onChangeNotes = (pairs) => {
    const notes = onChangeNotes(pairs);
    this.setState({
      formData: {
        ...this.state.formData,
        notes,
      },
    });
  };

  updateDate = (newDate) => {
    this.setState({
      ...this.state.formData,
      close_by: newDate,
    });
  };

  handleSelectCustomer = (customer) => {
    this.setState({
      customer,
    });
  };

  onFieldChange = (event) => {
    const fieldValue = event.target.value;
    const fieldName = event.target.name;

    const isInvalidField = !fieldName || fieldName.indexOf('notes[') > -1;
    if (isInvalidField) return true;

    const formData = {
      ...this.state.formData,
      [fieldName]: fieldValue,
    };

    this.setState({
      formData,
    });
  };

  toggleDisableState = () => {
    // if value not selected, html marks it as ':invalid' which is tehnically valid in our case. Hence, relying on is-invalid.
    const invalidFields = document.querySelectorAll(`.${FORM_CLASS_NAME} .Input.is-invalid`);
    const isSubmitDisabled = invalidFields.length;

    if (this.state.isSubmitDisabled !== isSubmitDisabled) {
      this.setState({ isSubmitDisabled: isSubmitDisabled });
    }
  };

  handleAdditionalOptions = () => {
    if (this.state.showAdditionalOptions) {
      const formTopEle = document.querySelector('.form-container input[name=usage]');

      if (formTopEle) {
        formTopEle.scrollIntoView({
          behavior: 'smooth',
          block: 'center',
        });
      }

      setTimeout(() => {
        this.setState({
          showAdditionalOptions: false,
        });
      }, 100);

      return;
    }

    this.setState(
      {
        showAdditionalOptions: true,
      },
      () => {
        setTimeout(() => {
          const scrollEle = document.querySelector('.Input--SelectCustomer');

          if (scrollEle) {
            scrollEle.scrollIntoView({
              behavior: 'smooth',
              block: 'start',
            });
          }

          return;
        }, 100);
      },
    );
  };

  onSubmit = () => {
    const payload = {
      ...this.state.formData,
      type: 'upi_qr',
      fixed_amount: this.state.formData.fixed_amount === '1',
    };

    if (this.state.customer && this.state.customer.id) {
      payload.customer_id = this.state.customer.id;
    }

    this.setState({
      isSubmitting: true,
    });

    this.props
      .onSubmit(payload)
      .then((resp) => {
        this.setState({
          isSubmitting: false,
        });

        return resp;
      })
      .catch((err) => {
        this.setState({
          isSubmitting: false,
        });

        return err;
      });
  };

  render() {
    const { props, state } = this;
    const isSubmitDisabled = state.isSubmitting || state.isSubmitDisabled;
    const { formData, showAdditionalOptions, isSubmitting } = state;

    return (
      <div class="QRCode--Create-wizard">
        <div class="title">Create QR Code</div>
        <div class="form-container">
          <Form class={FORM_CLASS_NAME} onChange={this.onFieldChange} onSubmit={this.onSubmit}>
            <main>
              <Input.Radio
                autoRender
                label="QR Usage"
                name="usage"
                class="Input--vTop"
                options={USAGE_OPTIONS}
                disabled={isSubmitting}
              />

              <Input.Radio
                autoRender
                label="Accept only fixed amount on this QR?"
                name="fixed_amount"
                class="Input--vTop"
                options={FIXED_AMOUNT_OPTIONS}
                disabled={isSubmitting}
              />

              {formData.fixed_amount === '1' && (
                <Input.Group class="InputGroup--inline InputGroup--vTop amount" required>
                  <div class="Input-content">
                    <Input.CurrencySelect
                      autoRender
                      disabled
                      name="currency"
                      parentQuerySelector=".Modal-body"
                    />
                    <Input
                      autoRender
                      required
                      name="payment_amount"
                      placeholder="0.00"
                      validator={amountValidator}
                      disabled={isSubmitting}
                    />
                  </div>
                </Input.Group>
              )}

              <Input
                autoRender
                name="description"
                label={
                  <>
                    Description <small>(Optional)</small>
                  </>
                }
                class="Input--vTop"
                placeholder="Description will be visible on the QR Code."
                maxLength="120"
                description={
                  <div class="text-right">{(formData.description || '').length} / 120</div>
                }
                disabled={isSubmitting}
              />

              <div
                class={classList('additional-options-btn', showAdditionalOptions && 'btn-hide')}
                onClick={this.handleAdditionalOptions}
              >
                <div>
                  <div class="heading">
                    {showAdditionalOptions ? 'Hide' : 'View'} Advance Options
                  </div>
                  <div class="description">QR Name, auto-closing date, and internal notes.</div>
                </div>
                <i
                  class={classList('i', showAdditionalOptions ? 'i-chevron-up' : 'i-chevron-down')}
                />
              </div>

              {showAdditionalOptions && (
                <div class="AdditionalOptions">
                  <Input.DateTime
                    autoRender
                    isInline
                    class="Input--vTop"
                    label={
                      <>
                        Close By <small>(Optional)</small>
                      </>
                    }
                    checkboxFieldLabel="Close this QR code at a specified time"
                    onChange={this.updateDate}
                    disabled={isSubmitting}
                  />

                  <div class="Input Input--vTop Input--SelectCustomer">
                    <div class="Input-label">
                      Customer <small>(Optional)</small>
                    </div>

                    <div class="Input-content">
                      <CustomerSelector
                        disabled={isSubmitting}
                        onChange={this.handleSelectCustomer}
                      />
                    </div>
                  </div>

                  <Input
                    autoRender
                    class="Input--vTop name"
                    name="name"
                    label={
                      <>
                        Name <small>(Optional)</small>
                      </>
                    }
                    description="This will appear on your dashboard."
                    disabled={isSubmitting}
                  />

                  <Input.PairList
                    class="Input--vTop"
                    name="notes"
                    label="Internal Notes"
                    onChange={this.onChangeNotes}
                    disabled={isSubmitting}
                  />
                </div>
              )}
            </main>

            <footer>
              {props.isModalView && (
                <Button type="button" onClick={props.onClose}>
                  Cancel
                </Button>
              )}

              <AsyncBtn.Primary
                type="submit"
                pendingState="Creating..."
                onClick={this.onSubmit}
                disabled={isSubmitDisabled}
              >
                Create QRCode
              </AsyncBtn.Primary>
            </footer>
          </Form>
        </div>
      </div>
    );
  }
}

function amountValidator(value) {
  return validateAmount(value);
}

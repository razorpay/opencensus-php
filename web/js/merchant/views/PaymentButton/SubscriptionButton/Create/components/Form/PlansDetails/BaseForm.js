import Alert from 'common/new-ui/Alert';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import EditorModal from '../components/EditorModal';
import InputDropdown from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/components/InputDropdown';

import { getCurrency } from 'common/ui/Amount';
import { classList, paiseToRupees } from 'common/utils/rzp-utils';
import { getPeriodLabel } from '../../../constants/billingCycle';
// import track from '../../../track';

export default class BaseForm extends React.Component {
  constructor(props) {
    super(props);

    const { field } = this.props;

    this.state = {
      selectedPlanOption: this.findSelectedOptionInPlans(field),
      disableSubmit: !field,
    };
  }

  findSelectedOptionInPlans(field) {
    if (!field) {
      return null;
    }

    const selectedOption = this.props.plansOptions.find(
      plan => plan.id === field.id
    );

    return selectedOption;
  }

  toggleSubmitBtn = () => {
    let disableSubmit = !!this.formEl.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  handleSubmit = formData => {
    const { billing_cycles_total_count } = formData;
    const { selectedPlanOption } = this.state;

    const newField = {
      ...selectedPlanOption,
      plan_id: selectedPlanOption.id,
      product_config: {
        plan_details: {
          interval: selectedPlanOption.interval,
          period: selectedPlanOption.period,
        },
        subscription_details: {
          total_count: billing_cycles_total_count,
        },
      },
    };

    this.props.onSubmit(newField);

    this.props.handleClose();
  };

  handleChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  onChangePlan = option => {
    this.setState({
      selectedPlanOption: option,
    });

    // track.lj.trackCustomerScreenFieldType(option);
  };

  get currencySymbol() {
    const currency = this.props.currency;
    const _currencySymbol = getCurrency(currency).symbol;

    return _currencySymbol;
  }

  planOptionComponent = ({ option }) => (
    <div>
      <div class="option-title">{option.item.name}</div>
      <div class="option-description">
        <span>
          {this.currencySymbol}{' '}
          {paiseToRupees(Number(option.item.amount)).toFixed(2)}
        </span>
        <span class="big-dot-separator" />
        <span>Charged {getPeriodLabel(option.period, option.interval)}</span>
      </div>
      <i class="i i-check" />
    </div>
  );

  selectedPlanOptionComponent = ({ option }) => option.item.name;

  get formFooter() {
    const { disableSubmit } = this.state;

    return (
      <div class="CreatorModal-BaseForm-footer">
        <button
          type="button"
          class="cancel-btn Button--transparent Button"
          onClick={() => {
            this.props.handleClose();

            // track.lj.trackCustomerScreenCancelFieldChanges();
          }}
        >
          <span>&times;</span>
          Cancel
        </button>

        <button
          type="submit"
          class="save-btn Button--transparent Button"
          disabled={disableSubmit}
        >
          <span class="icon i-check" />
          Save
        </button>
      </div>
    );
  }

  setRefForm = el => (this.formEl = el);

  render() {
    const { field, plansOptions } = this.props;
    const { selectedPlanOption } = this.state;

    let descriptionOfSelectedPlanFrequency;

    if (selectedPlanOption) {
      descriptionOfSelectedPlanFrequency = (
        <span>
          <b>
            {this.currencySymbol}{' '}
            {paiseToRupees(Number(selectedPlanOption.item.amount)).toFixed(2)}
          </b>{' '}
          to be charged{' '}
          {getPeriodLabel(
            selectedPlanOption.period,
            selectedPlanOption.interval
          )}
        </span>
      );
    }

    return (
      <EditorModal class="CreatorModal-BaseForm" overElement allowScroll>
        <Form
          onSubmit={this.handleSubmit}
          onChange={this.handleChange}
          setRef={this.setRefForm}
        >
          <InputDropdown
            label="Plan"
            class="Input--vTop"
            dropdownElementClass="ps-in-modal Input-el-PlansDropdown Input-el-PaymentButtonForm"
            placeholder="Select Plan"
            description={descriptionOfSelectedPlanFrequency}
            options={plansOptions}
            optionLabelPath="id"
            optionValuePath="id"
            selectedOptionComponent={this.selectedPlanOptionComponent}
            optionComponent={this.planOptionComponent}
            defaultValue={selectedPlanOption ? selectedPlanOption.id : ''}
            // searchEnabled
            // searchIndices={['id', 'item.name']}
            onChange={this.onChangePlan}
          />

          <Input
            name="billing_cycles_total_count"
            label="No. of Billing Cycles"
            class="Input--vTop"
            placeholder="Enter count"
            defaultValue={
              field ? field.product_config.subscription_details.total_count : ''
            }
            autoFocus
            description="No. of times customers will be charged."
            required
            validator={val => {
              if (!val) {
                return 'Billing cycle is required';
              }

              // TODO: Add 10 years check

              const regex = new RegExp(`^[0-9]+$`, 'i');

              if (!regex.test(val)) {
                return 'Please enter valid number';
              }

              if (val == 0) {
                return 'Count must be atleast 1';
              }
            }}
          />

          {this.formFooter}
        </Form>
      </EditorModal>
    );
  }
}

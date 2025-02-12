import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import EditorModal from 'merchant/views/PaymentButton/SubscriptionButton/Create/components/Form/components/EditorModal';
import InputDropdown from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/components/InputDropdown';

import Amount, { getCurrency } from 'common/ui/Amount';
import { getFormattedAmount } from 'common/utils/rzp-utils';
import { getPeriodLabel } from 'merchant/views/PaymentButton/SubscriptionButton/Create/constants/billingCycle';
import FieldOptionsDropdownWrapper, {
  OptionsItem,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldOptionsDropdown';
import Button from 'common/new-ui/Button';

import track from 'merchant/views/PaymentButton/SubscriptionButton/Create/track';

class BaseForm extends React.Component {
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

    const selectedOption = this.props.plansOptions.find((plan) => plan.id === field.plan_id);

    return selectedOption;
  }

  toggleSubmitBtn = () => {
    const disableSubmit = !!this.formEl.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  handleSubmit = (formData) => {
    const { billing_cycles_total_count } = formData;
    const { selectedPlanOption } = this.state;

    const newField = {
      ...selectedPlanOption,
      plan_id: selectedPlanOption?.id,
      product_config: {
        plan_details: {
          interval: selectedPlanOption?.interval,
          period: selectedPlanOption?.period,
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

  onChangePlan = (option) => {
    this.setState({
      selectedPlanOption: option,
    });

    track.lj.trackCustomerScreenFieldType(option);
  };

  getCurrencySymbol(plan) {
    const currency = plan.item.currency;
    const _currencySymbol = getCurrency(currency).symbol;

    return _currencySymbol;
  }

  planOptionComponent = ({ option }) => (
    <div>
      <div className="option-title">{option.item.name}</div>
      <div className="option-description">
        <span>
          <Amount value={option.item.amount} currency={option.item.currency} />
        </span>
        <span className="big-dot-separator" />
        <span>Charged {getPeriodLabel(option.period, option.interval)}</span>
      </div>
      <i className="i i-check" />
    </div>
  );

  selectedPlanOptionComponent = ({ option }) => option.item.name;

  get additionalOptionsButton() {
    const { indexInOrder, handleDeleteField } = this.props;

    const showDeleteOption = typeof indexInOrder !== 'undefined' && handleDeleteField;

    return (
      showDeleteOption && (
        <FieldOptionsDropdownWrapper
          trigger={
            <Button.Transparent>
              <i className="i i-ellipsis-v" />
            </Button.Transparent>
          }
        >
          <OptionsItem>
            <div className="OptionsDropdown-item--delete" onClick={handleDeleteField}>
              <i className="i i-delete" />
              <div>Delete Field</div>
            </div>
          </OptionsItem>
        </FieldOptionsDropdownWrapper>
      )
    );
  }

  get formFooter() {
    const { disableSubmit } = this.state;

    return (
      <div className="CreatorModal-BaseForm-footer">
        <button
          type="button"
          className="cancel-btn Button--transparent Button"
          onClick={() => {
            this.props.handleClose();

            track.lj.trackCustomerScreenCancelFieldChanges();
          }}
        >
          <span>&times;</span>
          Cancel
        </button>

        <button type="submit" className="save-btn Button--transparent Button" disabled={disableSubmit}>
          <span className="icon i-check" />
          Save
        </button>
      </div>
    );
  }

  handleAddNewPlan = (closeFn) => {
    closeFn();
    this.props.history.push('/plans/new');
  };

  setRefForm = (el) => (this.formEl = el);

  render() {
    const { field, plansOptions } = this.props;
    const { selectedPlanOption } = this.state;

    let descriptionOfSelectedPlanFrequency;

    if (selectedPlanOption) {
      const currencySymbol = this.getCurrencySymbol(selectedPlanOption);
      const periodLabel = getPeriodLabel(selectedPlanOption.period, selectedPlanOption.interval);

      descriptionOfSelectedPlanFrequency = (
        <span>
          <b>
            {currencySymbol}{' '}
            {getFormattedAmount(
              Number(selectedPlanOption.item.amount),
              selectedPlanOption.item.currency,
            )}
          </b>{' '}
          to be charged {periodLabel}
        </span>
      );
    }

    return (
      <EditorModal className="CreatorModal-BaseForm" overElement allowScroll>
        <Form onSubmit={this.handleSubmit} onChange={this.handleChange} setRef={this.setRefForm}>
          <InputDropdown
            label="Plan"
            className="Input--vTop"
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
            autoFocus={!field}
            afterOptionsComponent={({ select }) => {
              return (
                <div
                  className="create-plan-btn Button Button--transparent"
                  onClick={() => {
                    this.handleAddNewPlan(select.actions.close);
                    track.lj.trackAddNewPlanField();
                  }}
                >
                  <b>Add New Plan</b>
                </div>
              );
            }}
          />

          <Input
            name="billing_cycles_total_count"
            label="No. of Billing Cycles"
            className="Input--vTop"
            placeholder="Enter count"
            defaultValue={field ? field.product_config.subscription_details.total_count : ''}
            description="No. of times customers will be charged."
            required
            pattern="^[0-9]+?$"
            validator={(val) => {
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

              return '';
            }}
          />

          {this.formFooter}
        </Form>

        {this.additionalOptionsButton}
      </EditorModal>
    );
  }
}

export default withRouter(BaseForm);

import { Component } from 'react';

import { adminFetch } from 'common/fetch';
import { closeModal, notifySuccess } from 'common/modal';
import { stringToObj } from 'common/util';
import { isPresent, pickProps, without } from 'rzp/utils/rzp-utils';

import Form from 'ui/Form';
import { DateField, SwitchField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import EntityRow from 'ui/EntityRow';

import PerTransactionForm from './PerTransactionForm';

export default class WritePartnerConfig extends Component {
  constructor(props) {
    super();
    this.state = {
      internals: {},
      values: props.values,
      plans: {
        pricing: {
          pending: true,
          data: [],
        },
        commission: {
          pending: true,
          data: [],
        },
      },
    };
  }

  componentWillMount() {
    this.fetchPricingPlans({ type: 'commission' });
    this.fetchPricingPlans({ type: 'pricing' });
  }

  fetchPricingPlans = params => {
    adminFetch({
      url: 'live/pricing',
      params,
    }).then(data => {
      const fetchedPlans = {
        pending: false,
        data: formatPlanData(data.items),
      };
      const plans = stringToObj(params.type, fetchedPlans, this.state.plans);
      this.setState({ plans });
    });
  };

  handleSearchableChange = name => ({ option }) => {
    const target = {
      name,
      value: option.id,
    };
    this.handleChange({ target });
  };

  handleDateChange = name => momentDate => {
    const target = {
      name,
      value: momentDate.format('X'),
    };
    this.handleChange({ target });
  };

  handleChange = ({ target }) => {
    const name = target.name || target.dataset.name;

    let value = target.value;
    value = isPresent(value) && isNaN(value) ? value : Number(value);

    const stateKey = target.name ? 'values' : 'internals';

    this.setState(
      {
        [stateKey]: {
          ...this.state[stateKey],
          [name]: value,
        },
      },
      () => {
        // updating dependent fields if no negative value for certain fields
        if (!value) {
          let newValues = {};
          switch (name) {
            case 'explicit_plan_id':
              newValues = { explicit_should_charge: 0 };
              break;
            case 'explicit_should_charge':
              newValues = { explicit_refund_fees: 0 };
              break;
            default:
              return;
          }
          this.setState({
            values: {
              ...this.state.values,
              ...newValues,
            },
          });
        }
      }
    );
  };

  handleSubmitClick = body => {
    let { values, internals } = this.state;
    const { submit, config_id } = this.props;
    values = {
      ...values,
      ...pickProps(body, [
        'default_plan_id',
        'implicit_plan_id',
        'explicit_plan_id',
      ]),
    };

    return submit({
      url: 'live/partner_configs' + (config_id ? `/${config_id}` : ''),
      data: without(values, 'id'),
    }).then(partnerConfig => {
      if (partnerConfig) {
        notifySuccess('Partner Config updated successfully');
        closeModal();
      }
    });
  };

  renderForm = () => {
    const { values, internals, plans } = this.state;

    return (
      !!values && (
        <>
          <SwitchField
            label="Commission"
            name="commissions_enabled"
            enabledLabel="Enable"
            disabledLabel="Disable"
            onChange={this.handleChange}
            defaultValue={values.commissions_enabled}
          />

          <PerTransactionForm
            plans={plans}
            onSearchableChange={this.handleSearchableChange}
            internals={internals}
            values={values}
          />

          <DateField
            name="implicit_expiry_at"
            label="Expiry Date"
            allowToday={false}
            disablePastDates
            onChange={this.handleDateChange('implicit_expiry_at')}
            defaultValue={getDefaultDateVal(values.implicit_expiry_at)}
          />

          <DateField
            name="revisit_at"
            label="Revisit Date"
            allowToday={false}
            disablePastDates
            onChange={this.handleDateChange('revisit_at')}
            defaultValue={getDefaultDateVal(values.revisit_at)}
          />

          <SwitchField
            name="explicit_should_charge"
            label="Charge Add-on Commission"
            enabledLabel="Yes"
            disabledLabel="No"
            onChange={this.handleChange}
            defaultValue={values.explicit_should_charge}
            disabled={!values.explicit_plan_id}
          />

          <SwitchField
            name="explicit_refund_fees"
            label="Refund Add-on Commission on payment refund"
            enabledLabel="Yes"
            disabledLabel="No"
            onChange={this.handleChange}
            defaultValue={values.explicit_refund_fees}
            value={values.explicit_refund_fees}
            disabled={!values.explicit_should_charge}
            helpMsg={
              "If No, we'll only record but not charge Add-on commission from sub-merchant"
            }
          />

          <AsyncButton
            text={this.props.buttonText}
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleSubmitClick}
          />
        </>
      )
    );
  };

  render() {
    const { submerchant } = this.props;
    return (
      <>
        {isPresent(submerchant) && (
          <div className="box">
            <div className="heading">
              <strong>Submerchant Details</strong>
            </div>

            <EntityRow label="Id" value={submerchant.id} />
            <EntityRow label="Name" value={submerchant.name} />
            <EntityRow label="Email" value={submerchant.email} />
          </div>
        )}
        <div class="box">
          <header>Commission Settings</header>
          <div class="row-item">
            <Form class="full-span full-elements" onChange={this.handleChange}>
              {this.renderForm()}
            </Form>
          </div>
        </div>
      </>
    );
  }
}

function formatPlanData(data) {
  return data.map(plan => ({
    ...plan,
    value: plan.id,
  }));
}

function getDefaultDateVal(unixTime) {
  return unixTime ? moment(unixTime, 'X') : null;
}

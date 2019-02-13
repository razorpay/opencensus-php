import { Component } from 'react';

import { adminFetch, adminPost, adminPut } from 'common/fetch';
import { notifySuccess } from 'common/modal';
import { stringToObj } from 'common/util';
import { isPresent, pickProps, without } from 'rzp/utils/rzp-utils';

import Form from 'ui/Form';
import Field, { DateField, SwitchField, SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import PerTransactionForm from './PerTransactionForm';

export default class WritePartnerConfig extends Component {
  constructor(props) {
    super();
    this.state = {
      internals: {},
      applications: {
        pending: false,
        data: [],
      },
      merchantDetails: {},
      plans: {
        pricing: {
          pending: true,
          data: [],
        },
        comission: {
          pending: true,
          data: [],
        },
      },
    };
  }

  componentWillMount() {
    this.fetchMerchantDetails();
    this.fetchPricingPlans({ type: 'comission' });
    this.fetchPricingPlans({ type: 'pricing' });
  }

  fetchPricingPlans = params => {
    adminFetch({
      url: 'live/pricing/merchants',
      params,
    }).then(data => {
      const fetchedPlans = {
        pending: false,
        data: formatPlanData(data),
      };
      const plans = stringToObj(params.type, fetchedPlans, this.state.plans);
      this.setState({ plans });
    });
  };

  fetchMerchantDetails = () => {
    return adminFetch({
      url: 'live/merchants/details',
      headers: {
        'X-Razorpay-Account': this.props.match.params.id,
      },
    }).then(data => {
      this.setState(
        {
          merchantDetails: {
            pending: false,
            data,
          },
        },
        this.fetchConfigOrApps
      );
    });
  };

  fetchConfigOrApps = () => {
    const { merchantDetails } = this.state;
    if (isPurePlatformPartner(merchantDetails.data)) {
      return this.fetchApps();
    }
    return this.fetchConfig({ partner_id: merchantDetails.data.id });
  };

  fetchApps = () => {
    if (!this.state.applications.pending) {
      this.setState({ applications: { pending: true, data: [] } });
    }
    return adminFetch({
      url: `live_${this.props.match.params.id}/oauth/applications`,
    }).then(response => {
      if (response)
        this.setState({
          applications: {
            pending: false,
            data: response.items,
          },
          values: undefined,
        });
    });
  };

  fetchConfig = params => {
    if (!this.state.loadingValues) {
      this.setState({ loadingValues: true, values: undefined });
    }
    return adminFetch({
      url: 'live/partner_configs',
      params,
    }).then(response => {
      if (response) {
        const data = response.success ? response.data : response;
        this.setState({
          values: {
            application_id: params.application_id,
            ...sanitizeConfig(data),
          },
          loadingValues: false,
          internals: { _update: !!data },
        });
      }
    });
  };

  handleSearchableSelectChange = name => ({ option }) => {
    const target = {
      name,
      value: option.plan_id,
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
    const stateKey = target.name ? 'values' : 'internals';

    this.setState({
      [stateKey]: {
        ...this.state[stateKey],
        [name]: isPresent(value) && isNaN(value) ? value : Number(value),
      },
    });
  };

  handleAppChange = ({ target }) => {
    const application_id = target.value;
    this.fetchConfig({ application_id });
  };

  handleSubmitClick = body => {
    let { merchantDetails, values, internals } = this.state;
    const submit = internals._update ? adminPut : adminPost;
    values = {
      ...values,
      ...pickProps(body, [
        'default_plan_id',
        'implicit_plan_id',
        'explicit_plan_id',
      ]),
    };

    if (!isPurePlatformPartner(merchantDetails.data) && !internals._update) {
      values.partner_id = this.props.match.params.id;
    }

    return submit({
      url: 'live/partner_configs' + (values.id ? `/${values.id}` : ''),
      data: without(values, 'id'),
    }).then(partnerConfig => {
      if (partnerConfig) {
        this.setState({
          values: partnerConfig,
        });
        notifySuccess('Partner Config updated successfully');
      }
    });
  };

  renderForm = () => {
    const { values, internals, plans } = this.state;

    return (
      !!values && (
        <>
          <SwitchField
            label="Comission"
            name="commissions_enabled"
            enabledLabel="Enable"
            disabledLabel="Disable"
            onChange={this.handleChange}
            defaultValue={values.commissions_enabled}
          />

          <PerTransactionForm
            plans={plans}
            onSearchableSelectChange={this.handleSearchableSelectChange}
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
            name="explicit_refund_fees"
            label="Charge Refund Fees"
            enabledLabel="Yes"
            disabledLabel="No"
            onChange={this.handleChange}
            defaultValue={values.explicit_refund_fees}
          />

          <SwitchField
            name="explicit_should_charge"
            label="Charge Add-on Comission"
            enabledLabel="Yes"
            disabledLabel="No"
            onChange={this.handleChange}
            helpMsg="If No, we'll only record but not charge Add-on comission from sub-merchant"
            defaultValue={values.explicit_should_charge}
          />

          {/* add support for helpMsg in SwitchField to avoid this */}
          <div class="field">
            {/* dummy field since switch field does not support help msg */}
            <label>{/* dummy label */}</label>
            <div class="info-block">
              <i class="i i-info-circle" />
              If No, we'll only record but not charge Add-on comission from
              sub-merchant
            </div>
          </div>

          <AsyncButton
            text={internals._update ? 'Update' : 'Create'}
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleSubmitClick}
          />
        </>
      )
    );
  };

  render() {
    const { loadingValues, applications, merchantDetails } = this.state;
    return (
      <div class="box">
        <header>Comission Settings</header>
        <div class="row-item">
          <Form class="full-span full-elements" onChange={this.handleChange}>
            {applications.pending ? (
              <Field
                label="Application"
                value="Fetching applications..."
                disabled
              />
            ) : (
              isPurePlatformPartner(merchantDetails.data) && (
                <SelectField
                  name="application_id"
                  label="Application"
                  onChange={this.handleAppChange}
                >
                  <option value="">Select Application...</option>
                  {applications.data.map(app => (
                    <option key={app.id} value={app.id}>
                      {app.name}
                    </option>
                  ))}
                </SelectField>
              )
            )}
            {loadingValues ? (
              <div class="box">
                <div class="spinner center" />
              </div>
            ) : (
              this.renderForm()
            )}
          </Form>
        </div>
      </div>
    );
  }
}

function formatPlanData(data) {
  return data.map(({ plan_name, ...plan }) => ({
    ...plan,
    name: plan_name,
    value: plan.plan_id,
  }));
}

function getDefaultDateVal(unixTime) {
  return unixTime ? moment(unixTime, 'X') : undefined;
}

function isPurePlatformPartner(merchant = {}) {
  return merchant.partner_type === 'pure_platform';
}

function sanitizeConfig(data = {}) {
  return pickProps(data, [
    'commissions_enabled',
    'implicit_expiry_at',
    'revisit_at',
    'explicit_refund_fees',
    'explicit_should_charge',
    'default_plan_id',
    'implicit_plan_id',
    'explicit_plan_id',
    'id',
  ]);
}

import { Component } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';
import { Field, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import CheckboxField from 'rzp/ui/Forms/CheckboxField';
import Fieldset from 'rzp/ui/Forms/Fieldset';
import { required } from 'rzp/utils/validators';

const selector = formValueSelector('activationBusinessDetails');
@connect(state => {
  return {
    business_type: selector(state, 'business_type'),
    or_same: selector(state, 'or_same'),
    business_registered_address: selector(state, 'business_registered_address'),
    business_registered_state: selector(state, 'business_registered_state'),
    business_registered_city: selector(state, 'business_registered_city'),
    business_registered_pin: selector(state, 'business_registered_pin'),
  };
}, null)
export default class BusinessDetailsForm extends Component {
  updateOperationalAddress = (event, newValue) => {
    setTimeout(() => {
      // Allow the redux-form to update the store
      let props = this.props;
      if (props.or_same) {
        this.props.change(
          'business_operation_address',
          props.business_registered_address
        );
        this.props.change(
          'business_operation_state',
          props.business_registered_state
        );
        this.props.change(
          'business_operation_city',
          props.business_registered_city
        );
        this.props.change(
          'business_operation_pin',
          props.business_registered_pin
        );
      }
    });
  };

  render() {
    let {
      handleSubmit,
      save,
      saveAndNext,
      goBack,
      accountId,
      linkedAccountKyc,
    } = this.props;
    let locked = this.props.data.locked;

    return (
      <form class="form-horizontal" onSubmit={handleSubmit(saveAndNext)}>
        <Fieldset readOnly={locked}>
          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              Organisation Type
            </label>
            <div class="col-md-9">
              {!accountId &&
                this.props.business_type === '2' &&
                <div class="alert alert-warning">
                  We may not be able to support individuals as of now. Get in
                  touch with{' '}
                  <a href="mailto:support@razorpay.com">
                    support@razorpay.com
                  </a>{' '}
                  for more details
                </div>}

              <Field
                name="business_type"
                component={InputField}
                tagName="select"
                class="form-control"
                autoFocus={true}
                disabled={locked}
                validate={[required()]}
              >
                <option />
                <option value="1">Proprietorship</option>
                <option value="2">Individual</option>
                <option value="3">Partnership</option>
                <option value="4">Private Limited</option>
                <option value="5">Public Limited</option>
                <option value="6">LLP</option>
                <option value="7">NGO</option>
                <option value="8">Educational Institutes</option>
                <option value="9">Trust</option>
                <option value="10">Society</option>
              </Field>
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              Full Business Name
            </label>
            <div class="col-md-9">
              <Field
                name="business_name"
                component={InputField}
                class="form-control"
                placeholder="Acme Private Limited"
                validate={[required()]}
              />
            </div>
          </div>

          {accountId
            ? null
            : <div>
                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Billing Label
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="business_dba"
                      component={InputField}
                      class="form-control"
                      placeholder="Acme Watches"
                      validate={[required()]}
                    />
                    <small class="help-block">
                      <i class="icon icon-info-circle" />
                      <span>
                        This is the brand name that the customers are familiar
                        with.
                      </span>
                    </small>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    International Payments Required?
                  </label>
                  <div class="col-md-9 checkbox">
                    <label class="i-switch">
                      <Field
                        name="business_international"
                        component={CheckboxField}
                        disabled={locked}
                      />
                      <i />
                    </label>
                    <small class="help-block">
                      <i class="icon icon-info-circle" />
                      <span>
                        Please note that applications for international
                        transactions take longer time to process.
                      </span>
                    </small>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Payments Accepted for:
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="business_paymentdetails"
                      class="form-control"
                      component={InputField}
                      tagName="select"
                      disabled={locked}
                      validate={[required()]}
                    >
                      <option />
                      <option value="B2B">Business to Business (B2B)</option>
                      <option value="B2C">Business to Consumer (B2C)</option>
                      <option value="B2B+B2C">Both B2B and B2C</option>
                    </Field>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Business Model
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="business_model"
                      component={InputField}
                      tagName="textarea"
                      class="form-control"
                      placeholder="Business Model"
                      maxLength={255}
                      validate={[required()]}
                    />
                    <small class="help-block">
                      <i class="icon icon-info-circle" />
                      <span>
                        Please give a brief explanation of your business model
                        and future plans (Essential for startups)
                      </span>
                    </small>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Registered Address
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="business_registered_address"
                      component={InputField}
                      tagName="textarea"
                      class="form-control"
                      placeholder="Registered Address"
                      onChange={this.updateOperationalAddress}
                      validate={[required()]}
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Registration Address State
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="business_registered_state"
                      component={InputField}
                      class="form-control"
                      placeholder="Registered Address State"
                      onChange={this.updateOperationalAddress}
                      validate={[required()]}
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Registered Address City
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="business_registered_city"
                      component={InputField}
                      class="form-control"
                      placeholder="Registered Address City"
                      onChange={this.updateOperationalAddress}
                      validate={[required()]}
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Registered Address Pincode
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="business_registered_pin"
                      component={InputField}
                      class="form-control"
                      placeholder="Registered Address Pincode"
                      onChange={this.updateOperationalAddress}
                      validate={[required()]}
                    />
                  </div>
                </div>

                {!locked
                  ? <div class="form-group">
                      <label class="col-md-3 control-label">
                        Operational Address same as Registered Address
                      </label>
                      <div class="col-md-9 checkbox">
                        <label class="i-switch">
                          <Field
                            name="or_same"
                            component="input"
                            type="checkbox"
                            onChange={this.updateOperationalAddress}
                          />
                          <i />
                        </label>
                        <small class="help-block">
                          <i class="icon icon-info-circle" />
                          <span>
                            Physical Verification might be performed at your
                            operational address.
                          </span>
                        </small>
                      </div>
                    </div>
                  : null}

                <fieldset disabled={this.props.or_same}>
                  <div class="form-group">
                    <label class="col-md-3 control-label label-required">
                      Operational Address
                    </label>
                    <div class="col-md-9">
                      <Field
                        name="business_operation_address"
                        component={InputField}
                        tagName="textarea"
                        class="form-control"
                        placeholder="Operational Address"
                        validate={[required()]}
                      />
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-md-3 control-label label-required">
                      Operational Address State
                    </label>
                    <div class="col-md-9">
                      <Field
                        name="business_operation_state"
                        component={InputField}
                        class="form-control"
                        placeholder="Operational Address State"
                        validate={[required()]}
                      />
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-md-3 control-label label-required">
                      Operational Address City
                    </label>
                    <div class="col-md-9">
                      <Field
                        name="business_operation_city"
                        component={InputField}
                        class="form-control"
                        placeholder="Operational Address City"
                        validate={[required()]}
                      />
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-md-3 control-label label-required">
                      Operational Address Pincode
                    </label>
                    <div class="col-md-9">
                      <Field
                        name="business_operation_pin"
                        component={InputField}
                        class="form-control"
                        placeholder="Operational Address Pincode"
                        validate={[required()]}
                      />
                    </div>
                  </div>
                </fieldset>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Date of Establishment
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="business_doe"
                      component={InputField}
                      type="date"
                      max={moment().format('YYYY-MM-DD')}
                      placeholder="Date of Establishment (YYYY-MM-DD)"
                      class="form-control"
                      disabled={locked}
                      validate={[required()]}
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label">
                    GST Identification Number
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="gstin"
                      component="input"
                      class="form-control"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label">
                    Provisional GST Identification Number
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="p_gstin"
                      component="input"
                      class="form-control"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label">Company CIN</label>
                  <div class="col-md-9">
                    <Field
                      name="company_cin"
                      component="input"
                      class="form-control"
                      placeholder="Company CIN"
                    />
                    <small class="help-block">
                      <i class="icon icon-info-circle" />
                      <span>Mandatory for Companies</span>
                    </small>
                  </div>
                </div>
              </div>}

          {accountId && !linkedAccountKyc
            ? null
            : <div class="form-group">
                <label class="col-md-3 control-label">Company PAN</label>
                <div class="col-md-9">
                  <Field
                    name="company_pan"
                    component="input"
                    class="form-control"
                    placeholder="Company PAN"
                  />
                  {accountId
                    ? null
                    : <small class="help-block">
                        <i class="icon icon-info-circle" />
                        <span>Mandatory for Companies</span>
                      </small>}
                </div>
              </div>}

          {accountId
            ? null
            : <div>
                <div class="form-group">
                  <label class="col-md-3 control-label">Name on PAN Card</label>
                  <div class="col-md-9">
                    <Field
                      name="company_pan_name"
                      component="input"
                      class="form-control"
                      placeholder="Name on PAN (provided above)"
                    />
                    <small class="help-block">
                      <i class="icon icon-info-circle" />
                      <span>Mandatory for Companies</span>
                    </small>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Expected annual transaction volume (INR)
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="transaction_volume"
                      component={InputField}
                      tagName="select"
                      class="form-control"
                      disabled={locked}
                      validate={[required()]}
                    >
                      <option />
                      <option value="1">&lt; 1 Lakh</option>
                      <option value="2">1 to 10 lakh</option>
                      <option value="3">10 Lakh to 1 Crore</option>
                      <option value="4">&gt; 1 Crore</option>
                    </Field>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label label-required">
                    Expected average transaction value
                  </label>
                  <div class="col-md-9">
                    <Field
                      name="transaction_value"
                      component={InputField}
                      class="form-control"
                      placeholder="E.g. Average price of the commodities you sell"
                      validate={[required()]}
                    />
                  </div>
                </div>
              </div>}

          {accountId && !linkedAccountKyc
            ? null
            : <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  {accountId
                    ? 'Promoter/Individual PAN'
                    : 'PAN of any 1 authorised signatory/promoter/director'}
                </label>
                <div class="col-md-9">
                  <Field
                    name="promoter_pan"
                    component={InputField}
                    class="form-control"
                    placeholder="PAN Number of Promoter"
                    validate={[required()]}
                  />
                </div>
              </div>}

          {accountId
            ? null
            : <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  Name on PAN Card
                </label>
                <div class="col-md-9">
                  <Field
                    name="promoter_pan_name"
                    component={InputField}
                    class="form-control"
                    placeholder="Name on PAN Card"
                    validate={[required()]}
                  />
                </div>
              </div>}

          <div class="form-group">
            <div class="col-md-offset-3 col-md-9">
              <div class="btn-toolbar">
                {accountId
                  ? null
                  : <AsyncButton
                      type="button"
                      class="btn btn-default pull-left"
                      text="Back"
                      onClick={goBack}
                    />}

                <AsyncButton
                  class="btn btn-primary pull-right"
                  text="Save & Next"
                  pendingText="Saving..."
                  onClick={handleSubmit(saveAndNext)}
                />

                <AsyncButton
                  type="button"
                  class="btn btn-default pull-right"
                  text="Save"
                  onClick={handleSubmit(save)}
                />
              </div>
            </div>
          </div>
        </Fieldset>
      </form>
    );
  }
}

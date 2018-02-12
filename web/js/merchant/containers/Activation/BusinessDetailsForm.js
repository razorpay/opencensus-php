import { Component } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';
import { Field, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import CheckboxField from 'rzp/ui/Forms/CheckboxField';
import Fieldset from 'rzp/ui/Forms/Fieldset';
import {
  required,
  validatePincodeLength,
  validatePANCard,
  validateCIN,
} from 'rzp/utils/validators';
import { states } from 'rzp/utils/constants';

import { getPincodeDetails } from 'merchant/modules/activation';

const selector = formValueSelector('activationBusinessDetails');
@connect(state => {
  return {
    business_type: selector(state, 'business_type'),
    business_registered_address: selector(state, 'business_registered_address'),
    business_registered_state: selector(state, 'business_registered_state'),
    business_registered_city: selector(state, 'business_registered_city'),
    business_registered_pin: selector(state, 'business_registered_pin'),
    business_operation_address: selector(state, 'business_operation_address'),
    business_operation_state: selector(state, 'business_operation_state'),
    business_operation_city: selector(state, 'business_operation_city'),
    business_operation_pin: selector(state, 'business_operation_pin'),
  };
}, null)
export default class BusinessDetailsForm extends Component {
  state = {
    or_same: true,
  };

  componentWillMount() {
    this.verifySameAddress();
  }

  handleSameAddressCheck = e => {
    this.setState({ or_same: e.target.checked }, () =>
      this.updateOperationalAddress()
    );
  };

  verifySameAddress = () => {
    let props = this.props;
    let checkCounter = 0;

    //If values are null, then don't verify
    if (
      !props.business_registered_address ||
      !props.business_registered_city ||
      !props.business_registered_pin ||
      !props.business_registered_state
    ) {
      return;
    }

    if (
      props.business_registered_address.trim() !==
        props.business_operation_address.trim() ||
      props.business_registered_pin.trim() !==
        props.business_operation_pin.trim() ||
      props.business_registered_state.trim() !==
        props.business_operation_state.trim() ||
      props.business_registered_city.trim() !==
        props.business_operation_city.trim()
    ) {
      this.setState({ or_same: false }, () => this.updateOperationalAddress());
    }
  };

  updateOperationalAddress = () => {
    setTimeout(() => {
      // Allow the redux-form to update the store
      let props = this.props;
      if (this.state.or_same) {
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

  //Fetch state/city details based on pincode.
  fetchPincodeDetails = (e, code) => {
    const pincode = e.target.value;

    getPincodeDetails(e.target.value, (city = null, state = null) => {
      this.props.change(`business_${code}_city`, city);
      this.props.change(`business_${code}_state`, state);
      this.updateOperationalAddress();
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
                this.props.business_type === '2' && (
                  <div class="alert alert-warning">
                    We may not be able to support individuals as of now. Get in
                    touch with{' '}
                    <a href="mailto:support@razorpay.com">
                      support@razorpay.com
                    </a>{' '}
                    for more details
                  </div>
                )}

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
                <option value="11">Not yet registered</option>
                <option value="12">Other</option>
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

          {accountId ? null : (
            <div>
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
                    <i class="i i-info-circle" />
                    <span>
                      This is the brand name that the customers are familiar
                      with.
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
                    <i class="i i-info-circle" />
                    <span>
                      Please give a brief explanation of your business model and
                      future plans (Essential for startups)
                    </span>
                  </small>
                </div>
              </div>

              <div class="form-group">
                <div class="col-md-offset-3 col-md-9">
                  <div class="checkbox rzpCheckbox">
                    <Field
                      name="business_international"
                      id="business_international"
                      component="input"
                      type="checkbox"
                      disabled={locked}
                    />
                    <label for="business_international" class="icon i-check"/>
                    <span class="left-label label-required">
                      International Payments Required?
                    </span>
                  </div>
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
                  Registered Address Pincode
                </label>
                <div class="col-md-9">
                  <Field
                    name="business_registered_pin"
                    component={InputField}
                    class="form-control"
                    placeholder="Registered Address Pincode"
                    onChange={e => this.fetchPincodeDetails(e, 'registered')}
                    validate={[required(), validatePincodeLength]}
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
                    tagName="select"
                    class="form-control"
                    placeholder="Registered Address State"
                    onChange={this.updateOperationalAddress}
                    validate={[required()]}
                  >
                    <option />
                    {Object.keys(states).map(stateCode => (
                      <option value={stateCode} key={stateCode}>
                        {states[stateCode]}
                      </option>
                    ))}
                  </Field>
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

              {!locked ? (
                <div class="form-group">
                  <div class="col-md-offset-3 col-md-9">
                    <div class="checkbox rzpCheckbox">
                      <input
                        name="or_same"
                        id="or_same"
                        type="checkbox"
                        onChange={this.handleSameAddressCheck}
                        checked={this.state.or_same}
                      />
                      <label htmlFor="or_same" class="icon i-check"/>
                      <span class="left-label">
                        Operational Address same as Registered Address
                      </span>
                    </div>
                    <small class="help-block">
                      <i class="i i-info-circle" />
                      <span>
                        Physical Verification might be performed at your
                        operational address.
                      </span>
                    </small>
                  </div>
                </div>
              ) : null}

              {!this.state.or_same ? (
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
                      Operational Address Pincode
                    </label>
                    <div class="col-md-9">
                      <Field
                        name="business_operation_pin"
                        component={InputField}
                        class="form-control"
                        placeholder="Operational Address Pincode"
                        validate={[required(), validatePincodeLength]}
                        onChange={e => this.fetchPincodeDetails(e, 'operation')}
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
                        tagName="select"
                        class="form-control"
                        placeholder="Operational Address State"
                        validate={[required()]}
                      >
                        <option />
                        {Object.keys(states).map(stateCode => (
                          <option value={stateCode} key={stateCode}>
                            {states[stateCode]}
                          </option>
                        ))}
                      </Field>
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
                </fieldset>
              ) : null}

              <div class="form-group">
                <label class="col-md-3 control-label">
                  GST Identification Number
                </label>
                <div class="col-md-9">
                  <Field name="gstin" component="input" class="form-control" />
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
                    component={InputField}
                    class="form-control"
                    placeholder="Company CIN"
                    validate={validateCIN}
                  />
                  <small class="help-block">
                    <i class="i i-info-circle" />
                    <span>Mandatory for Companies</span>
                  </small>
                </div>
              </div>
            </div>
          )}

          {accountId && !linkedAccountKyc ? null : (
            <div class="form-group">
              <label class="col-md-3 control-label">Company PAN</label>
              <div class="col-md-9">
                <Field
                  name="company_pan"
                  component={InputField}
                  class="form-control"
                  placeholder="Company PAN"
                  validate={validatePANCard}
                />
                {accountId ? null : (
                  <small class="help-block">
                    <i class="i i-info-circle" />
                    <span>Mandatory for Companies</span>
                  </small>
                )}
              </div>
            </div>
          )}

          {accountId ? null : (
            <div>
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
                    <i class="i i-info-circle" />
                    <span>Mandatory for Companies</span>
                  </small>
                </div>
              </div>
            </div>
          )}

          {accountId && !linkedAccountKyc ? null : (
            <div class="form-group">
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
                  validate={[required(), validatePANCard]}
                />
              </div>
            </div>
          )}

          {accountId ? null : (
            <div class="form-group">
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
            </div>
          )}

          <div class="form-group">
            <div class="col-md-offset-3 col-md-9">
              <div class="btn-toolbar">
                {accountId ? null : (
                  <AsyncButton
                    type="button"
                    class="btn btn-default pull-left"
                    text="Back"
                    onClick={goBack}
                  />
                )}

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

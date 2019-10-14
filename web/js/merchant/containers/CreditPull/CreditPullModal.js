import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, FieldArray, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import ReduxDatetime from 'rzp/ui/ReduxDatetime';
import InputField from 'rzp/ui/Forms/InputField';
import { RadioGroup } from 'rzp/ui/Forms/RadioGroup';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { required, phone, pincode } from 'rzp/utils/validators';
import { showNotification } from 'rzp/modules/notifications';
import { states } from 'rzp/utils/constants';
import * as MerchantActions from 'merchant/modules/b-merchants';
import * as ModalActions from 'rzp/modules/modals';
import bMerchantReducer from 'merchant/modules/b-merchants';
import CheckBoxField from 'rzp/ui/Forms/CheckboxField';

const validate = values => {
  const errors = {};
  if (!values.hasOwnProperty('gender')) {
    errors.gender = 'Please select a gender';
  }
  if (!values.hasOwnProperty('dateOfBirth')) {
    errors.dateOfBirth = 'Please select a DOB';
  }
  if (values.hasOwnProperty('pinCode')) {
    let pin = Number(values.pinCode);
    if (!pin || pin < 100000 || pin > 999999) {
      errors.pinCode = 'Please enter 6 digit pin code';
    }
  }
  return errors;
};

@connect(
  state => {
    return {
      initialValues: state.bMerchant.merchantData,
      user: state.session.user,
    };
  },
  {
    showNotification,
    ...MerchantActions,
    ...ModalActions,
    bMerchantReducer,
  }
)
@reduxForm({
  form: 'b-merchant',
  validate,
})
export default class CreditPullModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      errors: null,
      fees: '',
      autoEnabled: false,
      isLoading: false,
      hasAcceptedTerms: false,
    };
  }

  componentWillMount() {
    this.props.fetchBMerchant();
  }

  save = props => {
    console.log('ONLY CALLED IF VALID');
    console.log(this.props);
  };

  successModalHeader = () => {
    return (
      <div>
        <i className="i i-done-all text-success modal-header-success" />
        Successfully Enabled!
      </div>
    );
  };

  renderPreEnablement = () => {
    const { handleSubmit } = this.props;

    return (
      <>
        <ModalHeader
          title={'Business Details'}
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <form
          className="form-horizontal bureau-merchant-form"
          onSubmit={handleSubmit(this.save)}
        >
          <div className="modal-body">
            <Alert type="error" message={this.state.errors} />
            <div>
              <div className="form-group customer">
                <label className="col-md-3 control-label help-label label-required">
                  Name
                </label>
                <div className="col-md-4">
                  <Field
                    name="firstName"
                    component={InputField}
                    class="form-control"
                    placeholder="First Name"
                    validate={required('Please enter a first name')}
                  />
                </div>

                <div className="col-md-4">
                  <Field
                    name="lastName"
                    component={InputField}
                    class="form-control"
                    placeholder="Last Name"
                    validate={required('Please enter a last email')}
                  />
                </div>
              </div>

              <div className="form-group customer">
                <label className="col-md-3 control-label label-required">
                  Contact Details
                </label>
                <div className="col-md-4">
                  <Field
                    name="mobile"
                    component={InputField}
                    class="form-control"
                    placeholder="Mobile Number"
                    validate={[
                      required('Please enter a phone number'),
                      phone('Please enter a valid phone number'),
                    ]}
                  />
                </div>

                <div className="col-md-4">
                  <Field
                    name="email"
                    component={InputField}
                    class="form-control"
                    placeholder="Email"
                    disabled={true}
                  />
                </div>
              </div>

              <div className="form-group">
                <label className="col-md-3 control-label label-required">
                  PAN Number
                </label>
                <div className="col-md-4">
                  <Field
                    name="pan"
                    component={InputField}
                    class="form-control"
                    placeholder="PAN Number"
                    disabled={true}
                  />
                </div>
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Gender
              </label>
              <div className="col-md-4">
                <Field
                  component={RadioGroup}
                  name="gender"
                  required={true}
                  options={[
                    { title: 'Male', value: 'M' },
                    { title: 'Female', value: 'F' },
                  ]}
                />
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Date of Birth
              </label>
              <div className="col-md-4">
                <Field
                  name="dateOfBirth"
                  component={ReduxDatetime}
                  placeholder="Select a date"
                  required={true}
                  timeFormat={false}
                />
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Residential Address
              </label>

              <div className="col-md-8">
                <Field
                  name="line1"
                  component={InputField}
                  tagName="textarea"
                  type="textarea"
                  class="form-control"
                  validate={required('Please enter the address line one')}
                />
              </div>
              <div className="col-md-3" />
              <div className="col-md-4" style={{ paddingTop: '15px' }}>
                <Field
                  name="city"
                  component={InputField}
                  class="form-control"
                  placeholder="City"
                  validate={required('Please enter a city')}
                />
              </div>

              <div className="col-md-4" style={{ paddingTop: '15px' }}>
                <Field
                  name="state"
                  component={InputField}
                  tagName="select"
                  class="form-control"
                  placeholder="State"
                >
                  {Object.keys(states).map(stateCode => (
                    <option value={stateCode} key={stateCode}>
                      {states[stateCode]}
                    </option>
                  ))}
                </Field>
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Pin Code
              </label>
              <div className="col-md-4">
                <Field
                  name="pinCode"
                  component={InputField}
                  class="form-control"
                  placeholder="Pin Code"
                />
              </div>
            </div>
          </div>

          <div className="modal-footer">
            <div className="form-group">
              <div className="col-md-3">
                <Field
                  name="consent"
                  component={CheckBoxField}
                  checked={this.state.hasAcceptedTerms}
                  onChange={this.handleCheckboxChange}
                />
              </div>
              <label htmlFor="consent" className="cap-consent col-md-9">
                Share my credit report with Razorpay and Razorpay's partners.
              </label>
            </div>

            <button
              type="button"
              className="btn btn-default"
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              class="btn btn-primary"
              text={'Verify & Submit'}
              disabled={!this.state.hasAcceptedTerms}
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </>
    );
  };

  handleCheckboxChange = event => {
    this.setState({
      hasAcceptedTerms: !this.state.hasAcceptedTerms,
    });
  };

  render() {
    return (
      <div className="container-cred-pull-modal">
        {this.renderPreEnablement()}
      </div>
    );
  }
}

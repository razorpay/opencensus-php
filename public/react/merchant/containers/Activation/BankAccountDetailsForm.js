import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import Alert from 'rzp/ui/Forms/Alert'
import { required, email, phone } from 'rzp/utils/validators'
import { saveStep } from 'merchant/modules/activation'
import { showNotification } from 'merchant/modules/notifications'
import { states } from 'rzp/utils/constants'

@connect(
  (state) => state.activation,
  { saveStep, showNotification }
)
@reduxForm({
  form: 'activationBankDetails',
  destroyOnUnmount: false,
})
export default class BankDetailsForm extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      errors: null
    }
  }

  componentWillMount() {
    if (!this.props.initialized) {
      this.props.initialize(this.props.data)
    }
  }

  save = (props) => {
    return this.props.saveStep(4, props).then((response) => {
      this.props.showNotification({
        type: 'success',
        message: 'Step saved successfully'
      })
      this.props.gotoTab(5)
    }).catch(({ errors }) => {
      this.setState({
        errors
      })
    })
  }

  render() {
    let { handleSubmit } = this.props

    return (
      <div class='panel'>
        <div class='panel-body'>
          <div class='row'>
            <div class='col-md-offset-2 col-md-10'>
              <h4 class='wizard-header'>Bank Account Details</h4>
            </div>
          </div>

          <div class='row'>
            <div class='col-lg-8 col-md-10 col-sm-12'>
              <Alert type='error' message={this.state.errors} />

              <form class='form-horizontal' onSubmit={handleSubmit(this.save)}>
                <fieldset>
                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Branch IFSC Code</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_branch_ifsc'
                        component={InputField}
                        class='form-control'
                        placeholder='IFSC Code of the Bank Branch'
                        autoFocus={true}
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Bank Account Number</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_account_number'
                        component={InputField}
                        class='form-control'
                        placeholder='Bank Account Number'
                        type='password'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Re-enter Bank Account Number</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_account_number_confirmation'
                        component={InputField}
                        class='form-control'
                        placeholder='Re-enter your Bank Account Number'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Bank Account Type</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_account_type'
                        component={InputField}
                        class='form-control'
                        placeholder='Bank Account Type e.g. Current'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Beneficiary Name</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_account_name'
                        component={InputField}
                        class='form-control'
                        placeholder='Account Holder Name'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Beneficiary Address Line 1</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_beneficiary_address1'
                        component={InputField}
                        tagName='textarea'
                        class='form-control'
                        placeholder='Beneficiary Address Line 1'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label'>Beneficiary Address Line 2</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_beneficiary_address2'
                        component={InputField}
                        tagName='textarea'
                        class='form-control'
                        placeholder='Beneficiary Address Line 2'
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label'>Beneficiary Address Line 3</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_beneficiary_address3'
                        component={InputField}
                        tagName='textarea'
                        class='form-control'
                        placeholder='Beneficiary Address Line 3'
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Beneficiary Address State</label>
                    <div class='col-md-9'>
                      <Field
                        name='bank_beneficiary_state'
                        component={InputField}
                        tagName='select'
                        class='form-control'
                        placeholder='Beneficiary Address State'
                        validate={[
                          required()
                        ]}
                      >
                        <option></option>
                        {
                          Object.keys(states).map((stateCode) => (
                            <option value={stateCode}>{states[stateCode]}</option>
                          ))
                        }
                      </Field>
                    </div>
                  </div>

                  <div class='form-group'>
                    <div class='col-md-offset-3 col-md-9'>
                      <div class='btn-toolbar'>
                        <AsyncButton
                          type='button'
                          class='btn btn-default pull-left'
                          text='Back'
                          onClick={() => this.props.gotoTab(2)}
                        />

                        <AsyncButton
                          class='btn btn-primary pull-right'
                          text='Save & Next'
                          pendingText='Saving...'
                          onClick={handleSubmit(this.save)}
                        />

                        <AsyncButton
                          type='button'
                          class='btn btn-default pull-right'
                          text='Save'
                          onClick={handleSubmit(this.save)}
                        />
                      </div>
                    </div>
                  </div>
                </fieldset>
              </form>
            </div>
          </div>
        </div>
      </div>
    )
  }
}

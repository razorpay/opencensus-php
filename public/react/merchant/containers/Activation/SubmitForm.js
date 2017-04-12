import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import { saveStep } from 'merchant/modules/activation'
import { showNotification } from 'merchant/modules/notifications'

@connect(
  (state) => state.activation,
  { saveStep, showNotification }
)
@reduxForm({
  form: 'activationSubmitForm',
  destroyOnUnmount: false,
})
export default class SubmitForm extends Component {
  componentWillMount() {
    if (!this.props.initialized) {
      this.props.initialize(this.props.data)
    }
  }

  save = (props) => {
    return this.props.saveStep(3, props).then((response) => {
      this.props.showNotification({
        type: 'success',
        message: 'Step saved successfully'
      })
      this.props.gotoTab(4)
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
              <h4 class='wizard-header'>Submit For Activation</h4>
            </div>
          </div>

          <div class='row'>
            <div class='col-lg-8 col-md-10 col-sm-12'>

              <form class='form-horizontal' onSubmit={handleSubmit(this.save)}>
                <fieldset>
                  <div class='form-group'>
                    <div class='checkbox text-center'>
                      <label class='i-checks'>
                        <Field
                          name='agree_terms'
                          component='input'
                          type='checkbox'
                        />
                        <i></i>
                        I have read and understood the <a href='https://razorpay.com/terms/' target='_blank' class='highlight'>terms and conditions</a>,
                        the <a href='https://razorpay.com/agreement/' target='_blank' class='highlight'>merchant agreement</a>,
                        and the <a href='https://razorpay.com/privacy/' target='_blank' class='highlight'>privacy policy</a> and agree to abide by them at all times.
                      </label>
                    </div>
                  </div>

                  <div class='form-group'>
                    <div class='col-md-offset-3 col-md-9'>
                      <div class='btn-toolbar'>
                        <AsyncButton
                          type='button'
                          class='btn btn-default pull-left'
                          text='Back'
                          onClick={() => this.props.gotoTab(1)}
                        />

                        <AsyncButton
                          class='btn btn-primary'
                          text='Click here to Submit'
                          pendingText='Submitting...'
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

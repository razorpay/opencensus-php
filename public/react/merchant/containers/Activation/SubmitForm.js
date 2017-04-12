import { Component } from 'react'
import { Field } from 'redux-form'
import AsyncButton from 'react-async-button'
import ActivationWizardHOC from './ActivationWizardHOC'

@ActivationWizardHOC
export default class SubmitForm extends Component {
  render() {
    let {
      handleSubmit,
      save,
      gotoTab,
    } = this.props

    return (
      <form class='form-horizontal' onSubmit={handleSubmit(save)}>
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
                  onClick={() => gotoTab(1)}
                />

                <AsyncButton
                  class='btn btn-primary'
                  text='Click here to Submit'
                  pendingText='Submitting...'
                  onClick={handleSubmit(save)}
                />
              </div>
            </div>
          </div>
        </fieldset>
      </form>
    )
  }
}

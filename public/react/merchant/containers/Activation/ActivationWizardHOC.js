import { Component } from 'react'
import { connect } from 'react-redux'
import * as ActivationActions from 'merchant/modules/activation'
import { showNotification } from 'merchant/modules/notifications'
import { Field, reduxForm } from 'redux-form'
import Alert from 'rzp/ui/Forms/Alert'

// Uses HOC (Higher Order Component) to leverage Inheritance Inversion pattern & Props proxying
// https://medium.com/@franleplant/react-higher-order-components-in-depth-cf9032ee6c3e#5247

export default function ActivationWizardHOC(WizardComponent) {
  @connect(
    (state) => state.activation,
    { ...ActivationActions, showNotification }
  )
  @reduxForm({
    destroyOnUnmount: false,
  })
  class ActivationBase extends WizardComponent {
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
      let step = this.props.step
      return this.props.saveStep(step, props).then((response) => {
        this.props.showNotification({
          type: 'success',
          message: 'Step saved successfully'
        })
      }).catch((err) => {
        this.setState({
          errors: err.errors
        })
        throw err
      })
    }

    saveAndNext = (props) => {
      return this.save(props).then(() => {
        this.props.gotoTab(this.props.step + 1)
      })
    }

    saveFile = (event, fieldName) => {
      let files = event.target.files

      return this.props.saveFile(files[0], fieldName).then((response) => {
        this.props.showNotification({
          type: 'success',
          message: 'File uploaded successfully'
        })
      }).catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors
        })
      })
    }

    render() {
      return (
        <div class='panel'>
          <div class='panel-body'>
            <div class='row'>
              <div class='col-md-offset-2 col-md-10'>
                <h4 class='wizard-header'>{this.props.pageTitle}</h4>
              </div>
            </div>

            <div class='row'>
              <div class='col-lg-8 col-md-10 col-sm-12'>
                <Alert type='error' message={this.state.errors} />

                <WizardComponent
                  {...this.props}
                  save={this.save}
                  saveAndNext={this.saveAndNext}
                  saveFile={this.saveFile}
                />
              </div>
            </div>
          </div>
        </div>
      )
    }
  }

  return ActivationBase
}

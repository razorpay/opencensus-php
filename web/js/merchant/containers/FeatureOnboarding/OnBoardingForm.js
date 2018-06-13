import { Component, Fragment } from 'react';

import Accordian from 'rzp/ui/Accordian';
import AsyncButton from 'react-async-button';

import FORM_TYPE from './Forms';

export default class OnBoardingForm extends Component {
  getWizardForm = () => {
    const { formType, handleChange, onSave, disabled } = this.props;

    const currentForm = FORM_TYPE[formType];
    const WizardForm = currentForm.formComponent;

    return (
      <Fragment key="form">
        <WizardForm handleChange={handleChange} />
        <AsyncButton
          type="button"
          class="btn btn-primary pull-left"
          style={{ marginTop: '16px' }}
          text="Apply Now"
          pendingText="Applying..."
          onClick={onSave}
          disabled={disabled}
        />
      </Fragment>
    );
  };

  render() {
    const { formType } = this.props;

    const currentForm = FORM_TYPE[formType];
    const WizardForm = currentForm.formComponent;
    const WizardFormPreStep = currentForm.preStepFormComponent || null;

    return (
      <Fragment>
        <Accordian>
          {WizardFormPreStep
            ? [<WizardFormPreStep key="step" />, this.getWizardForm()]
            : this.getWizardForm()}
        </Accordian>
      </Fragment>
    );
  }
}

import { Component, Fragment } from 'react';

import Accordian, {
  AccordianItem,
  AccordianItemTitle,
  AccordianItemContent,
} from 'rzp/ui/Accordian';
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
    const WizardFormPreStep = currentForm.preStep.component || null;

    return (
      <Fragment>
        {WizardFormPreStep ? (
          <Accordian>
            <AccordianItem>
              <AccordianItemTitle>
                Step 1: {currentForm.preStep.title}
              </AccordianItemTitle>
              <AccordianItemContent>
                <WizardFormPreStep />
              </AccordianItemContent>
            </AccordianItem>
            <AccordianItem>
              <AccordianItemTitle>
                Step 2: {currentForm.title}
              </AccordianItemTitle>
              <AccordianItemContent>
                {this.getWizardForm()}
              </AccordianItemContent>
            </AccordianItem>
          </Accordian>
        ) : (
          this.getWizardForm()
        )}
      </Fragment>
    );
  }
}

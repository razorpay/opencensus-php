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
    const { formType, isPreStepCompleted } = this.props;

    const currentForm = FORM_TYPE[formType];
    const WizardFormPreStep =
      currentForm.preStep && currentForm.preStep.component;

    return (
      <Fragment>
        {WizardFormPreStep ? (
          <Accordian expandedKey={isPreStepCompleted | 0}>
            <AccordianItem cantBeOpened={isPreStepCompleted}>
              <AccordianItemTitle
                classNames={`${isPreStepCompleted ? 'text-success' : ''}`}
              >
                {isPreStepCompleted && (
                  <i class="i i-check" style={{ marginRight: '4px' }} />
                )}
                Step 1: {currentForm.preStep.title}
              </AccordianItemTitle>
              <AccordianItemContent>
                <WizardFormPreStep disabled={isPreStepCompleted} />
              </AccordianItemContent>
            </AccordianItem>
            <AccordianItem cantBeOpened={!isPreStepCompleted}>
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

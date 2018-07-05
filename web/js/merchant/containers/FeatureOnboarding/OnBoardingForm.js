import { Component, Fragment } from 'react';

import Accordion, {
  AccordionItem,
  AccordionItemTitle,
  AccordionItemContent,
} from 'rzp/ui/Accordion';
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
          <Accordion expandedKey={isPreStepCompleted | 0}>
            <AccordionItem cantBeOpened={isPreStepCompleted}>
              <AccordionItemTitle
                className={`${isPreStepCompleted ? 'text-success' : ''}`}
              >
                {isPreStepCompleted && (
                  <i class="i i-check" style={{ marginRight: '4px' }} />
                )}
                Step 1: {currentForm.preStep.title}
              </AccordionItemTitle>
              <AccordionItemContent>
                <WizardFormPreStep disabled={isPreStepCompleted} />
              </AccordionItemContent>
            </AccordionItem>
            <AccordionItem cantBeOpened={!isPreStepCompleted}>
              <AccordionItemTitle>
                Step 2: {currentForm.title}
              </AccordionItemTitle>
              <AccordionItemContent>
                {this.getWizardForm()}
              </AccordionItemContent>
            </AccordionItem>
          </Accordion>
        ) : (
          this.getWizardForm()
        )}
      </Fragment>
    );
  }
}

import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm } from 'redux-form';
import Alert from 'rzp/ui/Forms/Alert';
import { without } from 'rzp/utils/rzp-utils';
import { showNotification } from 'rzp/modules/notifications';
import * as ActivationActions from 'merchant/modules/activation';
import * as SessionActions from 'merchant/modules/session';
import { fetchUser } from 'merchant/modules/session';
import User from 'merchant/models/User';

import ContactDetailsForm from './ContactDetailsForm';
import BusinessDetailsForm from './BusinessDetailsForm';
import BankAccountDetailsForm from './BankAccountDetailsForm';
import DocumentsUploadForm from './DocumentsUploadForm';
import SubmitForm from './SubmitForm';

import { track } from './ga';

const FORM_COMPONENTS = {
  activationContactDetails: ContactDetailsForm,
  activationBusinessDetails: BusinessDetailsForm,
  activationBankAccounts: BankAccountDetailsForm,
  activationDocumentUpload: DocumentsUploadForm,
  activationSubmitForm: SubmitForm,
};

// ====
// Heimdall specific
// ====
@connect(
  state => {
    let session = state.session;
    let { data, ...otherProps } = state.activation;
    let initialValues = { ...data };

    return {
      initialValues: {
        ...initialValues,
      },
      session: state.session,
      ...state.activation,
    };
  },
  {
    ...ActivationActions,
    ...SessionActions,
    showNotification,
    fetchUser,
  }
)
@reduxForm({
  form: 'activationForm',
  destroyOnUnmount: false,
  enableReinitialize: true,
  // keepDirtyOnReinitialize: true,
})
export default class WizardItem extends Component {
  finalStep = 5;

  constructor(props) {
    super(props);

    this.state = {
      errors: null,
    };

    this.updateSession = this.updateSession.bind(this);
  }

  componentWillMount() {
    if (this.props.accountId) {
      if (this.props.linkedAccountKyc === 0) {
        this.finalStep = 3;
      } else {
        this.finalStep = 4;
      }
    }
  }

  updateSession(data) {
    // %age is for current Account, not linked accounts
    if (this.props.accountId) {
      return;
    }

    const { session } = this.props;

    const { activation_progress, activated, submitted } = data.data;

    const user = new User({
      ...session.user,
      activation_progress,
      activated,
      submitted: +submitted,
    });

    this.props.updateSession({
      user,
      mode: session.mode,
    });
  }

  _save = props => {
    let { step, accountId } = this.props;
    let data = without(props, [
      'or_same',
      'bank_account_number_confirmation',
      'steps_finished',
    ]);

    if (step === this.finalStep) {
      return this.props.submitForm({ step, data, accountId }).then(data => {
        this.updateSession(data);

        return this.props
          .fetchActivationDetails(accountId)
          .then(this.updateSession);
      });
    } else {
      return this.props
        .saveStep({ step, data, accountId })
        .then(this.updateSession);
    }
  };

  /**
   * Tracks analytics.
   * @param {String} _analyticsAction Action for event.
   * @param {String} eventLabel Label for event.
   * @param {String} suffix Suffix for event action.
   */
  _trackAnalytics = (_analyticsAction, eventLabel, suffix = '') => {
    // Don't track events for Linked Account activation form.
    if (!this.props.accountId) {
      /**
       * Need to check for string because when this is wrapper around
       * `handleSubmit`, the second argument is a function.
       */
      let analyticsAction = _analyticsAction;
      if (typeof analyticsAction !== 'string') {
        analyticsAction = 'Click - Save';
      }

      if (this.props.step === this.finalStep) {
        track({
          eventAction: `Click - Submit${suffix}`,
          eventLabel,
        });
      } else {
        track({
          eventAction: `${analyticsAction}${suffix}`,
          eventLabel,
        });
      }
    }
  };

  save = (props, analyticsAction) => {
    const formName = this.props.formTitle;
    return this._save(props)
      .then(response => {
        let step = this.props.step;

        // For updating the accounts list view on success of activation
        if (step === this.finalStep && this.props.callback) {
          this.props.callback();
        }

        let message =
          step === this.finalStep
            ? 'Form submitted Successfully!'
            : 'Step saved successfully';

        this._trackAnalytics(analyticsAction, formName, ' (Success)');

        this.setState({
          errors: null,
        });

        this.props.initialize(props);

        this.props.showNotification({
          type: 'success',
          message,
        });
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });

        let errors;
        try {
          errors = JSON.stringify(err.errors);
        } catch (e) {}
        this._trackAnalytics(analyticsAction, errors, ' (Error)');

        throw { errors: err.errors };
      });
  };

  saveAndNext = props => {
    return this.save(props, 'Click - Save and Next').then(() => {
      this.goNext();
    });
  };

  saveFile = (event, fieldName) => {
    let files = event.target.files;

    return this.props
      .saveFile({
        fieldName,
        file: files[0],
        step: this.props.step,
        accountId: this.props.accountId,
      })
      .then(response => {
        this.props.showNotification({
          type: 'success',
          message: 'File uploaded successfully',
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  goBack = () => {
    this.props.gotoTab(
      this.props.step - 1,
      'Click - Back (Success)',
      this.props.step
    );
  };

  goNext = () => {
    this.props.gotoTab(this.props.step + 1);
  };

  render() {
    let WizardForm = FORM_COMPONENTS[this.props.form];

    return (
      <div class="panel">
        <div class="panel-body">
          <div class="row">
            <div
              class={`${
                this.props.accountId ? '' : 'col-lg-10'
              } col-md-12 col-sm-12`}
            >
              <div class="row">
                <div class="col-md-offset-3 col-md-9">
                  <h4 class="wizard-header">{this.props.pageTitle}</h4>
                  <Alert type="error" message={this.state.errors} />
                </div>
              </div>

              <WizardForm
                {...this.props}
                goBack={this.goBack}
                goNext={this.goNext}
                save={this.save}
                saveAndNext={this.saveAndNext}
                saveFile={this.saveFile}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }
}

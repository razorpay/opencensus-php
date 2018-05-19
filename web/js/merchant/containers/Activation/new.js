import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { showNotification } from 'rzp/modules/notifications';
import { without } from 'rzp/utils/rzp-utils';
import Spinner from 'rzp/ui/Spinner';
import { Modal, ModalContent } from 'component/Modal';
import { LinkCard } from 'component/Cards';
import ActivationWizard from 'component/merchant/Activation';
import OldActivationWizard from './index';
import Button from 'component/Button';

import { updateSession } from 'merchant/modules/session';
import User from 'merchant/models/User';

import { withRouter } from 'react-router-dom';
import { trackLinkClick, trackGoToConfig } from './ga_new';

/*
* ActivationContainer is used in:
* 1. '/activation' route for Activation form for merchant, and
* 2. Marketplace > Accounts for linked account (AccoundDetails)
*
* @props {onClose, Function, optional}. Without this modal would not be opened. Also, this would be used to close the modal
* @props {accountId, String, optional}. Needed if the ActivationWizard is opened for Linked Account
* */
@connect(
  state => ({
    session: state.session,
    user: state.session.user,
  }),
  {
    showNotification,
    updateSession,
  }
)
export class ActivationContainer extends React.Component {
  state = {
    data: null,
    categories: null,
  };

  componentWillMount() {
    this.fetchActivationDetails(this.props.accountId); // accountId = undefined if not present
  }

  fetchActivationDetails(accountId) {
    Promise.all([
      merchantFetch({
        url: 'merchant/activation',
        mode: 'live',
        accountId,
      }),
      !accountId && merchantFetch('merchant/activation/business_categories'),
    ]).then(([data, categories]) => {
      this.setState({
        data: data.data,
        categories: categories.data,
        isFormTouched: isFormTouched(data.data),
      });
    });
  }

  updateSession(data) {
    const { session, accountId } = this.props;

    // Update data
    this.setState({ data });

    // Session need not be updated if it's linked account form
    if (accountId) {
      return;
    }

    const {
      activation_progress,
      activated,
      activation_status,
      submitted,
    } = data;

    // Updating % activation_progress (side bar) and other important activation fields
    const user = new User({
      ...session.user,
      activation_progress,
      activated,
      activation_status,
      submitted: +submitted,
    });

    this.props.updateSession({
      user,
      mode: session.mode,
    });
  }

  submitForm = data => {
    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      data: { submit: 1 },
      accountId: this.props.accountId, // accountId for linked_accounts. Axios auto-ignore undefined keys in options
    })
      .then(response => {
        if (!response.data.can_submit) {
          throw { errors: ['Some mandatory fields are required'] };
        }

        this.postSubmitStep(response);

        return response;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        throw err;
      });
  };

  saveStep = data => {
    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      headers: {
        'content-type': 'application/json',
      },
      accountId: this.props.accountId, // accountId for linked_accounts. Axios auto-ignore undefined keys in options
      data,
    })
      .then(response => {
        if (!response.data) {
          this.props.showNotification({
            type: 'error',
            message: response.errors,
          });
        } else {
          this.updateSession(response.data);
        }

        return response;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors ? err.errors : err,
        });

        return err;
      });
  };

  postSubmitStep(response) {
    if (this.props.accountId) {
      this.props.callback && this.props.callback(); // Support for callback for linked_account activation
    } else {
      this.setState({ showSuccessScreen: true });
      this.updateSession(response.data); // Updating % activation_progress (side bar)
    }
  }

  saveFile = (fieldName, file, progressTracker) => {
    let formData = new FormData();

    //TODO: This mapping is just for past form cross-check. It can be removed now after verifying fields.
    let fieldNameMapping = {
      business_proof_url: 'business_proof_url',
      business_operation_proof: 'business_operation_proof_url',
      business_pan_url: 'business_pan_url',
      address_proof_url: 'address_proof_url',
      promoter_proof: 'promoter_proof_url',
      promoter_pan_url: 'promoter_pan_url',
      promoter_address_url: 'promoter_address_url',
      form_12a_url: 'form_12a_url',
      form_80g_url: 'form_80g_url',
    };
    formData.append(fieldNameMapping[fieldName], file);

    return merchantFetch({
      url: 'merchant/activation/upload',
      method: 'post',
      mode: 'live',
      data: formData,
      accountId: this.props.accountId,
      onUploadProgress: progressTracker,
    })
      .then(response => {
        if (response.data) {
          this.props.showNotification({
            type: 'success',
            message: 'File uploaded successfully',
          });

          this.updateSession(response.data);

          return response;
        }
      })
      .catch(err => {
        if (err.errors.length && err.errors[0]) {
          this.props.showNotification({
            type: 'error',
            message: err.errors,
          });
        }
        return err;
      });
  };

  // Fetch state_code and city to auto populate business_*_state and business_*_city fields in form
  getPincodeDetails(pincode) {
    return merchantFetch(`pincodes/${pincode}`)
      .then(response => {
        if (response.data) {
          return {
            city: response.data.city,
            state_code: response.data.state_code,
          };
        }

        return null;
      })
      .catch(err => {
        return null;
      });
  }

  closeWelcomeScreen = e => {
    trackLinkClick('Activate Later');

    this.props.onClose(e);
  };

  openWizard = () => {
    trackLinkClick('Activate Now');
    this.setState({ openWizard: true });
  };

  /*
  * 1. For linked account form, only spinner or Activation wizard.
  * 2. For main account form, spinner, Welcome Screen, Activation wizard and Success screens are shown.
  * */
  render() {
    const accountId = this.props.accountId; // If accountId present, then Welcome screen and Success screen are not required.

    let { data, categories } = this.state;

    const filledEvenSingleDetail = false;

    let content, modalClass;

    if (!accountId && this.state.showSuccessScreen) {
      modalClass = 'Activation--success';
      content = <SuccessScreen />;
    } else if (!data) {
      modalClass = 'Activation--welcome';
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (
      !accountId &&
      !this.state.isFormTouched &&
      !this.state.openWizard
    ) {
      modalClass = 'Activation--welcome';
      content = (
        <WelcomeScreen
          onClose={this.closeWelcomeScreen}
          openWizard={this.openWizard}
        />
      );
    } else {
      modalClass = 'Activation--wizard';
      content = (
        <ActivationWizard
          accountId={this.props.accountId}
          data={data}
          categories={categories}
          isFormTouched={this.state.isFormTouched}
          save={this.saveStep}
          saveFile={this.saveFile}
          submitForm={this.submitForm}
          getPincodeDetails={this.getPincodeDetails}
        />
      );
    }

    // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
    return this.props.onClose ? (
      <Modal class={'animate-down ' + modalClass} onClose={this.props.onClose}>
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="ActivationContainer">{content}</div>
    );
  }
}

/*
 * Success screen is shown only when the user has submitted the form. It's not shown in linked account activation but only main form.
 * */
const SuccessScreen = _ => {
  function clickConfig(e) {
    trackGoToConfig();
  }

  return (
    <div class="Activation--success">
      <div class="Activation-title">
        <side-title>Activation Form submitted Successfully!</side-title>
        <div class="submit-illustration" />
      </div>
      <div class="Activation-info">
        <i class="i i-check" /> Activation Form Submitted
        <p class="desc">
          The review process usually takes 2 to 3 working days. For any
          clarifications, we will reach out on your contact email.
        </p>
      </div>

      <div class="Activation-actions">
        <side-title>What's Next?</side-title>
        <LinkCard
          title={'Personalise Your Account'}
          description={
            'Personalise your checkout form, emails and pages with your logo and brand.'
          }
          icon={'icon-done'}
          onClick={this.clickConfig}
          to="/config"
        />
      </div>
    </div>
  );
};

/*
* Welcome screen is shown only when the user has not started filling the form. It's not shown in linked account activation but only main form.
* */
const WelcomeScreen = ({ onClose, openWizard }) => {
  return (
    <div class="Activation--welcome">
      <div class="short-content">
        <h3> Welcome to Razorpay! Get Started with Activation.</h3>
        <div class="underline" />
        <p>
          Simply submit your business details and relavant proofs online to
          start accpeting payments.
        </p>
        <p>
          Once you submit the form, it may take upto 2 to 3 working days to get
          you account activated.
        </p>

        {onClose && <Button onClick={onClose}>Activate Later</Button>}
        <Button.Primary onClick={openWizard}>
          Go to Activation Form
        </Button.Primary>
      </div>
    </div>
  );
};

// ActivationContainer.MODAL_MASK_CLASS = 'Activation';

/*
* Check if user filled any of the fields to be filled on fresh form
* * */
function isFormTouched(data) {
  if (!data) {
    return false;
  }

  let isDirty = false;

  Object.keys(data).find(key => {
    if (
      defaultKeysInForm.indexOf(key) > -1 ||
      excludedFieldsInForm.indexOf(key) > -1
    ) {
      return false;
    }
    if (data[key] != null) {
      isDirty = true;
      return true;
    }
  });

  return isDirty;
}

const defaultKeysInForm = ['contact_name', 'contact_email', 'contact_mobile'];
const excludedFieldsInForm = [
  'created_at',
  'business_international',
  'locked',
  'updated_at',
  'submitted',
  'can_submit',
  'steps_finished',
  'verification',
  'archived',
  'activated',
  'activation_progress',
  'allowed_next_activation_statuses',
];

/*
* This component is temporary and will be removed once the old activation form is removed
* */
@connect(
  state => ({
    user: state.session.user,
  }),
  {}
)
export default class ActivationDecider extends React.Component {
  // Component to show old activation wizard if old user and progress is > 25% ~ effectively only 1st step done
  render() {
    let Component = <ActivationContainer {...this.props} />;

    let isLinkedAccountForm = !!this.props.accountId;

    // Showing new activation form for linked-accounts
    if (isOldUser(this.props.user) && !isLinkedAccountForm) {
      let modalClass = 'Activation--wizard Activation--wizard--old';
      let content = (
        <React.Fragment>
          <div class="modal-header">
            <h3 class="modal-title">Activation Form</h3>
          </div>
          <OldActivationWizard {...this.props} />
        </React.Fragment>
      );

      // Accounts List also provides onClose fn. prop
      Component =
        this.props.onClose && this.props.closeUrl ? (
          <Modal
            class={'animate-down ' + modalClass}
            onClose={this.props.onClose}
          >
            <ModalContent>{content}</ModalContent>
          </Modal>
        ) : (
          <div class="ActivationContainer">{content}</div>
        );
    }

    return Component;
  }
}

ActivationDecider.MODAL_MASK_CLASS = 'Activation';

function isOldUser(user) {
  if (!user) {
    return false; // Fallback to new
  }

  let currentTime = 1526031000; // TODO: It IS TO BE THE DATE OF DEPLOYMENT.. Currently, 11 May, 3:00pm
  let isCreatedEarlier = user.created_at < currentTime;

  return isCreatedEarlier;
}

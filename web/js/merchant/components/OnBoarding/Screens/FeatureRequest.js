import { connect } from 'react-redux';

import { autoPrefixUrls, isFunction } from 'rzp/utils/rzp-utils';
import LocalStorageService from 'rzp/utils/localStorage';

import Image from 'rzp/ui/Image';
import Spinner from 'rzp/ui/Spinner';

import Button, { AsyncBtn } from 'component/Button';

import { showNotification } from 'rzp/modules/notifications';

import {
  saveOnboarding,
  getOnboardingResponse,
} from 'merchant/modules/onboarding';

import OnBoardingForm from '../OnBoardingForm';

@connect(
  state => ({
    user: state.session.user,
  }),
  {
    saveOnboarding,
    getOnboardingResponse,
    showNotification,
  }
)
export default class OnBoardingFeatureRequest extends React.PureComponent {
  constructor(props) {
    super(props);

    this.state = {
      submitted: false,
      isLoading: false,
      uploadedFile: null,
      form: {},
    };
  }

  componentWillMount() {
    this.setState({
      isLoading: true,
    });

    this.props
      .getOnboardingResponse(this.props.formType)
      .then(response => {
        const submitted = !!response.data.id;

        this.setState({
          isLoading: false,
          submitted: submitted,
        });
      })
      .catch(err => {
        // Handle errors
      });
  }

  handleChange = e => {
    this.setState({
      form: {
        [e.target.name]: e.target.value,
      },
    });
  };

  // Form submit handler
  onSubmitClick = () => {
    let props = { ...this.state.form };

    let file = null;
    let fileName = null;

    if (this.state.uploadedFile && this.props.formType === 'marketplace') {
      file = this.state.uploadedFile;
      fileName = 'vendor_agreement';
    }

    if (props.website_details) {
      props.website_details = autoPrefixUrls(props.website_details);
    }

    return this.props
      .saveOnboarding(this.props.formType, props, file, fileName)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Your request has been submitted',
        });
        this.setState({ submitted: true });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  switchToTestMode = () => {
    const { user } = this.props;

    LocalStorageService.setItem(`rzp_mode--${user.current}`, 'test');
    window.location.reload();
  };

  render() {
    const {
        prev,
        desc,
        imageUrl,
        invalid,
        formType,
        user,
        heading,
        isPreStepCompleted,
      } = this.props,
      { submitted, isLoading, uploadedFile } = this.state;

    return (
      <div class="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--FeatureRequest">
        <div class="Landing--Image">
          <Image src={imageUrl} />
        </div>

        <div class="FeatureRequest">
          <div>
            <span class="Dash" /> Get Started
          </div>

          <p class="desc">{desc}</p>

          <FeatureRequestForm
            heading={heading}
            isLoading={isLoading}
            user={user}
            submitted={submitted}
            formType={formType}
            isPreStepCompleted={isPreStepCompleted}
            uploadedFile={uploadedFile}
            invalid={invalid}
            handleChange={this.handleChange}
            switchToTestMode={this.switchToTestMode}
          />

          <div class="Button-Container">
            <Button.Transparent iconBefore="arrow-back" onClick={prev}>
              Back
            </Button.Transparent>

            {!submitted &&
              !isLoading && (
                <AsyncBtn.Primary
                  feature="subscriptions"
                  class="Forward-Button"
                  onClick={this.onSubmitClick}
                >
                  Submit
                </AsyncBtn.Primary>
              )}
          </div>
        </div>
      </div>
    );
  }
}

const FeatureRequestForm = ({
  isLoading,
  formType,
  handleChange,
  switchToTestMode,
  invalid,
  heading,
  submitted,
  user,
  isPreStepCompleted,
  uploadedFile,
}) => {
  if (isLoading) {
    return (
      <div class="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  if (!submitted) {
    return (
      <div class="FeatureRequest--Form">
        <OnBoardingForm
          user={user}
          formType={formType}
          handleChange={handleChange}
          isPreStepCompleted={
            isFunction(isPreStepCompleted)
              ? isPreStepCompleted()
              : isPreStepCompleted
          }
          disabled={invalid || (formType === 'marketplace' && !uploadedFile)}
        />
      </div>
    );
  }

  return (
    <div class="alert alert-info">
      Your form is submitted for enabling {heading}. Meanwhile, you can switch
      to <a onClick={switchToTestMode}>Test Mode</a> to try the product.
      {!user.isActivated ? (
        <div class="m-t">
          <b> Please note </b> that this is activation form for {heading}. Your
          request will be processed after you submit the{' '}
          <Link to="/activation">primary activation form</Link>.
        </div>
      ) : (
        <div class="m-t">
          <b>Please note </b> that your application is under review. We will
          reach out on your contact email for all updates.
        </div>
      )}
    </div>
  );
};

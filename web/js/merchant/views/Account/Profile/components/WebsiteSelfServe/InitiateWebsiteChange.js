import { useContext } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import UpdateWebsiteDetails from './UpdateWebsiteDetails';
import TwoFactorVerificaionContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';

function InitiateWebsiteChange(props) {
  const context = useContext(TwoFactorVerificaionContext);

  const onContactVerified = () =>
    props.openModal({
      size: 'small',
      component: <UpdateWebsiteDetails getWebsiteWorkflowStatus={props.getWebsiteWorkflowStatus} />,
    });

  const onProceedClick = () =>
    context.criticalFlow({
      modes: ['test', 'live'],
      onUserTwoFaVerified: () => {
        onContactVerified();
      },
    });

  return (
    <div class="website-self-serve-initiate-modal">
      <ModalHeader title="Update Website/App" onCloseClick={props.closeModal} />
      <div class="img-container">
        <img src="https://cdn.razorpay.com/static/assets/website-self-serve/Website-change.svg" />
      </div>
      <div class="content">
        <strong>Keep in mind</strong>
        <span>
          <ol>
            <li>
              The product/services that you are selling on the website/app should fall under
              “e-commerce” category
            </li>
            <li>
              Open a new Razorpay account if your new website/app falls under a different category
            </li>
          </ol>
        </span>
        <span class="note">
          You would not be able to use your current website/app with this account once the new
          website is approved
        </span>
      </div>
      <div class="action">
        <button class="btn btn-primary" onClick={onProceedClick}>
          Proceed to update website/app
          <i class="i i-chevron-right" />
        </button>
      </div>
    </div>
  );
}

export default InitiateWebsiteChange;

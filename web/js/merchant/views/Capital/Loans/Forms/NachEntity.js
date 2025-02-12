import React, { Component } from 'react';
import { connect } from 'react-redux';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import FileUpload from 'merchant/components/File/Upload';
import {
  createNach,
  fetchLoanApplicationMeta,
  getNach,
  uploadNach,
} from 'merchant/reducers/capital';
import { merchantFetch } from 'merchant/utils/ajax';
import { downloadFromUFH } from 'merchant/utils/downloadFile';
import * as NotificationActions from 'merchant_common/reducers/notifications';

import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import { isPreceedingState } from '../../utils';
import { APPLICATION_STATES, HOTJAR_TRIGGERS } from '../constants';

const createFormData = (form = {}) => {
  const formData = new FormData();

  Object.keys(form).map((key) => {
    formData.append(key, form[key]);
  });
  return formData;
};

class NachEntity extends Component {
  constructor() {
    super();
    this.state = {
      downloading: false,
      error: null,
    };
  }
  componentDidMount() {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_SUBMIT_NACH);
  }
  createNach = () => {
    const { loanApplicationDetails, user } = this.props;
    const {
      meta,
      promoter_details: {
        data: { applicant },
      },
      credit_offer_details,
      accepted_offer_details,
    } = loanApplicationDetails;

    const acceptedCreditOfferId = accepted_offer_details.data.credit_offer_id;
    const creditOffer = credit_offer_details.data.credit_offers.find(
      (credit_offer) => credit_offer.id === acceptedCreditOfferId,
    );

    const payload = {
      los_attributes: {
        application_id: meta.data.application.id,
        entity_id: applicant.id,
        entity_type: 'APPLICANT',
      },
      create_customer_request: {
        name: applicant.kyc.first_name,
        email: applicant.emails[0].email_id,
        contact: applicant.phones[0].phone_number,
        fail_existing: '0',
        notes: {
          key: '1',
          value: '2',
        },
      },
      create_order_request: {
        amount: 0,
        currency: 'INR',
        method: 'nach',
        payment_capture: '1',
        receipt: 'Receipt No.1',
        token: {
          auth_type: 'physical',
          max_amount: creditOffer.loan_attributes.credit_offered,
          nach: {
            form_reference1: `Recurring payment for ${applicant.kyc.first_name}`,
            form_reference2: 'Method Paper Nach',
            description: 'Paper NACH',
          },
          bank_account: {
            account_number: user.bank_account_number,
            ifsc_code: user.bank_branch_ifsc,
            beneficiary_name: `${applicant.kyc.first_name} ${applicant.kyc.second_name}`,
            beneficiary_email: applicant.emails[0].email_id,
            beneficiary_mobile: applicant.phones[0].phone_number,
            account_type: user.bank_account_type ? user.bank_account_type.toLowerCase() : 'current',
          },
        },
      },
    };
    return createNach(payload);
  };

  handleDownloadNach = async () => {
    const { meta, nach_details } = this.props.loanApplicationDetails;

    const nachForm = nach_details.data.nach ? nach_details.data.nach[0] : null;

    try {
      if (!nachForm) {
        await this.createNach();
        const nachResponse = await this.props.getNach({
          application_id: meta.data.application.id,
        });
        window.open(nachResponse.data.nach[0].token.nach.prefilled_form);
      } else {
        window.open(nachForm.token.nach.prefilled_form);
      }
    } catch (e) {
      if (window.APP_ENV !== 'production') console.error(e);
      this.props.showNotification({
        type: 'error',
        message: 'Unable to download Nach Form',
      });
    }
  };

  uploadToUfh = (file, progressTracker) => {
    const { meta, promoter_details } = this.props.loanApplicationDetails;
    const data = {
      file,
      display_name: file.name,
      name: file.name,
      store: 's3',
      type: 'nach',
      'entity[type]': 'applicant',
      'entity[id]': promoter_details.data.applicant.id,
      'metadata[]': '',
    };

    return merchantFetch({
      url: 'ufh/files/upload',
      method: 'post',
      mode: 'live',
      data: createFormData(data),
      onUploadProgress: progressTracker,
    });
  };

  handleFileChange = (file, progreeTracker) => {
    return this.uploadToUfh(file, progreeTracker).then((response) => {
      if (response && !response.errors) {
        const payload = {
          application_id: this.props.loanApplicationDetails.meta.data.application.id,
          id: this.props.loanApplicationDetails.nach_details.data.nach[0].id,
          file_store_id: response.data.file_id,
        };
        return uploadNach(payload).then((_) => {
          this.props.showNotification({
            type: 'success',
            message: 'Nach Form Uploaded Successfully.',
          });
        });
      }
    });
  };

  getSteps = () => {
    return [
      {
        title: 'Dowload the NACH form',
        description: 'Printout the prefilled NACH form provided by Razorpay &' + ' Verify Details.',
        cta: () => (
          <AsyncBtn.Primary
            showLoader={false}
            pendingState="Downloading..."
            className="btn btn-outline m-t"
            onClick={this.handleDownloadNach}
          >
            Download Nach Form
          </AsyncBtn.Primary>
        ),
        icon: 'nach_download',
      },
      {
        title: 'Sign the NACH form',
        description:
          'Sign the print copy, after verifing the NACH details, Scan or take a photo of the same.',
        cta: null,
        icon: 'nach_sign',
      },
      {
        title: 'Upload the signed NACH form',
        description: 'Ensure the form is not cropped and no shadows with size less than 6 MB.',
        cta: () => (
          <FileUpload
            showCloseBtn={false}
            showFileSize
            name="nach"
            stagedFileStatus="error"
            showAcceptInfo
            // maxSize="5242880"
            accept={['pdf', 'png', 'jpg', 'jpeg']}
            uploadedFileName="Upload File here"
            onFileChange={this.handleFileChange}
            description="Nach Description"
            dropZoneCavityClassName="nach"
            id="nach"
          />
        ),
        icon: 'nach_upload',
      },
    ];
  };

  handleNext = () => {
    // this.props._trackNavigationActions('NEXT', APPLICATION_STATES.SLOT_SELECTION_PENDING);
    this.props._trackNavigationActions(
      'NEXT',
      APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
    );
    return this.props.fetchLoanApplicationMeta(
      this.props.loanApplicationDetails.meta.data.application.id,
    );
  };

  downloadSignedNach = () => {
    const { loanApplicationDetails } = this.props;
    const { nach_details, _trackEvent } = loanApplicationDetails;
    _trackEvent({
      eventAction: 'Application | Download Signed Nach',
      eventLabel: 'Complete Application | Nach Form',
    });
    const entity = nach_details.data.nach ? nach_details.data.nach[0] : null;
    return downloadFromUFH(entity.file_store_id)
      .then((_) => {
        this.setState({
          downloading: false,
          error: null,
        });
      })
      .catch((error) => {
        this.setState({
          downloading: false,
          error: true,
        });
        this.props.showNotification({
          type: 'error',
          message: 'Unable to download the signed Nach Form',
        });
        this.props._trackEvent({
          eventAction: 'Download Nach Failed',
        });
      });
  };

  render() {
    const { nach_details, credit_offer_details, accepted_offer_details, meta } =
      this.props.loanApplicationDetails;

    if (nach_details.loading || credit_offer_details.loading || accepted_offer_details.loading)
      return <FormLoader />;

    const entity = nach_details.data.nach ? nach_details.data.nach[0] : null;

    if (entity && entity.file_store_id) {
      return (
        <div className="m-all">
          <div className="panel panel-default m-all">
            <div className="panel-body">
              <strong>Signed Nach Form</strong>
              <p className="text--secondary">You have uploaded the signed Nach Form</p>
              <a className="link no-margin no-padding" onClick={this.downloadSignedNach}>
                {this.state.downloading ? (
                  'Downloading...'
                ) : (
                  <React.Fragment>
                    Download Signed Nach Form
                    <i className="i i-chevron-right" />
                  </React.Fragment>
                )}
              </a>
            </div>
          </div>
          <div className="actions pull-right">
            <Button.Transparent
              onClick={() => {
                this.props._trackNavigationActions(
                  'BACK',
                  APPLICATION_STATES.CREDIT_OFFER_GENERATED,
                );
                this.props.navigation.back();
              }}
            >
              <i className="i i-chevron-left" />
              Back
            </Button.Transparent>
            <Button.Primary
              className="m-l"
              onClick={() => {
                // this.props._trackNavigationActions(
                //   'NEXT',
                //   APPLICATION_STATES.SLOT_SELECTION_PENDING,
                // );
                this.props._trackNavigationActions(
                  'NEXT',
                  APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
                );
                this.props.navigation.next();
              }}
            >
              Next
              <i className="i i-chevron-right" />
            </Button.Primary>
          </div>
        </div>
      );
    }
    return (
      <div className="nach-procedure-wrapper">
        {this.getSteps().map((step, index) => (
          <div className="nach-submission-step">
            <div className="nach-step-icon">
              <img src={require(`assets/capital/${step.icon}.svg`)} alt="Loading icon" />
            </div>
            <div className="nach-step-instructions">
              <div className="nach-step-index">Step {index + 1}</div>
              <div className="nach-step-title">{step.title}</div>
              <div className="nach-step-description">{step.description}</div>
              <div className="nach-step-cta m-t">{step.cta ? step.cta() : null}</div>
            </div>
          </div>
        ))}
        {!isPreceedingState(meta.data.application.status, this.props.nextState) && (
          <AsyncBtn.Primary
            type="submit"
            className="btn btn-primary pull-right"
            onClick={this.handleNext}
          >
            Next
            <i className="i i-chevron-right" />
          </AsyncBtn.Primary>
        )}
        <button className="btn btn-link pull-right" onClick={this.props.navigation.back}>
          <i className="i i-chevron-left" />
          Back
        </button>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
    getNach,
    ...NotificationActions,
  },
)(NachEntity);

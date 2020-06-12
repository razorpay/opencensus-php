import React, { Component } from 'react';
import { connect } from 'react-redux';
import {
  fetchLoanApplicationMeta,
  changeActiveState,
} from 'merchant/reducers/capital';
import { showNotification } from 'merchant_common/reducers/notifications';
import { FormLoader } from '../components/FormSectionLoadingSkeleton';
import { APPLICATION_STATES, BUSINESS_TYPES } from '../constants';
import { states } from 'merchant/helpers/data';
import { isPreceedingState } from '../utils';
import { AsyncBtn } from 'common/new-ui/Button';

const DOCUMENT_TYPE_LABELS = {
  business: 'Business',
  personal: 'Personal',
};

@connect(
  state => ({
    loanApplicationDetails: state.loanApplicationDetails,
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
    showNotification,
    changeActiveState,
  }
)
class DocumentCollectionInformation extends Component {
  getMasterDocument = masterDocId => {
    const { loanApplicationDetails } = this.props;
    const { document_groups } = loanApplicationDetails;
    const masterDocuments = document_groups.data.document_groups.reduce(
      (acc, docGroup) =>
        docGroup.master_documents ? [...acc, ...docGroup.master_documents] : acc
    );

    return masterDocuments.find(doc => doc.id === masterDocId);
  };

  getOtherVerificationDocument = document => {
    const otherDocumentsLabels = {
      dcol_nach: 'Nach Document',
    };
    if (
      document &&
      document.verfication_type === 'DOCUMENT' &&
      otherDocumentsLabels[document.task_code]
    ) {
      return otherDocumentsLabels[document.task_code];
    } else {
      return null;
    }
  };

  getDocuments = () => {
    const { loanApplicationDetails, user } = this.props;

    const { lender_details, meta } = loanApplicationDetails;

    const lenderDetails = lender_details.data.lender;
    const lenderProduct = lenderDetails.products.find(
      product => product.id === meta.data.application.product_id
    );
    const productVerificationRequirements =
      lenderProduct.attributes.required_document_groups
        .verification_requirements;
    const businessType = BUSINESS_TYPES[parseInt(user.business_type)];
    const businessSpecificRequirements = productVerificationRequirements.find(
      requirement => requirement.deed_type === businessType
    );
    const requiredDocuments = {
      personal:
        businessSpecificRequirements.applicant_verification_requirements,
      business: businessSpecificRequirements.business_verification_requirements,
    };

    return (
      <div class="documents-wrapper flex">
        {Object.entries(requiredDocuments).map(([documentType, documents]) => (
          <div class="section">
            {documents.map(document => (
              <p>
                {this.getMasterDocument(document.document_master_id)
                  ? this.getMasterDocument(document.document_master_id).name ||
                    this.getMasterDocument(document.document_master_id).type
                  : this.getOtherVerificationDocument(document)}
              </p>
            ))}
          </div>
        ))}
      </div>
    );
  };

  getDocumentTypes = () => {
    const { loanApplicationDetails, user } = this.props;

    const { lender_details, meta } = loanApplicationDetails;

    const lenderDetails = lender_details.data.lender;
    const lenderProduct = lenderDetails.products.find(
      product => product.id === meta.data.application.product_id
    );
    const productVerificationRequirements =
      lenderProduct.attributes.required_document_groups
        .verification_requirements;
    const businessType = BUSINESS_TYPES[parseInt(user.business_type)];
    const businessSpecificRequirements = productVerificationRequirements.find(
      requirement => requirement.deed_type === businessType
    );
    const requiredDocuments = {
      personal:
        businessSpecificRequirements.applicant_verification_requirements,
      business: businessSpecificRequirements.business_verification_requirements,
    };

    return (
      <div class="document-types flex">
        {Object.entries(requiredDocuments).map(([documentType, documents]) => (
          <div className="section">
            <p class="text-faded">{DOCUMENT_TYPE_LABELS[documentType]}</p>
          </div>
        ))}
      </div>
    );
  };
  render() {
    const { loanApplicationDetails } = this.props;

    const { lender_details, meta, schedule_details } = loanApplicationDetails;

    if (lender_details.loading || schedule_details.loading) {
      return <FormLoader />;
    }

    const applicationStatus = meta.data.application.status;

    const scheduleDetails = schedule_details.data;
    const slotAddress = scheduleDetails.addresses[0].address;

    const hasDocumentsCollected = !isPreceedingState(
      applicationStatus,
      APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW
    );

    return (
      <div class="verification-schedule-details">
        <div className="panel panel-default slot-details">
          <div className="panel-body no-margin full-width">
            <div class="document-types flex">
              <div className="section">
                <strong>
                  {hasDocumentsCollected ? 'Picked at' : 'Date & Time'}
                </strong>
              </div>
              <div className="section">
                <strong>{hasDocumentsCollected ? 'From' : 'Address'}</strong>
              </div>
            </div>
            <div class="documents-wrapper flex slot-details-wrapper">
              <div class="section">
                <p>{scheduleDetails.slot_timing}</p>
                <p>{moment(scheduleDetails.slot_date).format('ll')}</p>
              </div>
              <div class="section">
                {`${slotAddress.line_1}, ${slotAddress.city}, ${
                  states[slotAddress.state]
                } - ${slotAddress.pin_code}`}
              </div>
            </div>
          </div>
        </div>

        <div className="panel panel-default required-documents-info-container">
          <div className="panel-body no-margin full-width">
            <strong>
              {hasDocumentsCollected
                ? 'Documents Collected'
                : 'Documents To be Ready with'}
            </strong>
            {this.getDocumentTypes()}
            {this.getDocuments()}
          </div>
        </div>

        <div className="actions pull-right m-r">
          <button
            className="btn btn-link"
            onClick={() =>
              this.props.changeActiveState(
                APPLICATION_STATES.SLOT_SELECTION_PENDING
              )
            }
          >
            <i className="i i-chevron-left" />
            Back
          </button>
          {hasDocumentsCollected && (
            <AsyncBtn.Primary
              type="submit"
              class="btn btn-primary pull-right"
              onClick={() =>
                this.props.changeActiveState(
                  APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW
                )
              }
            >
              Next
              <i className="i i-chevron-right" />
            </AsyncBtn.Primary>
          )}
        </div>
      </div>
    );
  }
}

export default DocumentCollectionInformation;

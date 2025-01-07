import React, { Component } from 'react';
import Button from 'common/new-ui/Button';
import { connect } from 'react-redux';
import { APPLICATION_STATES, CAPITAL_LINKS, OFFLINE_COLLECTION_DOCUMENTS } from '../constants';
import { isPreceedingState } from '../../utils';
import { BUSINESS_TYPE_MAP } from 'merchant/views/Account/constants';
import { titleCase } from 'common/utils/rzp-utils';

@connect((state) => ({
  loanApplicationDetails: state.loanApplicationDetails,
  user: state.session.user,
}))
class OfflineDocumentCollection extends Component {
  renderDetails = (businessType) => {
    return Object.entries(businessType).map(([documentEntity, requiredDocuments]) => (
      <div className="section" key={documentEntity}>
        <p className="content-padding title">{documentEntity}</p>
        <div className="content-padding highlight">
          {requiredDocuments.map((document) => (
            <>
              <p key={document.type}>
                {document.type}
                {'   '}
                {document.allowedDocuments && document.allowedDocuments.length > 0 && (
                  <span class="text-faded">
                    (
                    {document.allowedDocuments.reduce((acc, doc, index) => {
                      return `${acc}${doc}${
                        index < document.allowedDocuments.length - 1 ? ' / ' : ''
                      }`;
                    }, '')}
                    )
                  </span>
                )}
              </p>
              {document.list && document.list.length > 0 && (
                <div className="list-wrapper">
                  {document.list.map((item) => {
                    return <li class="list-item">{item}</li>;
                  })}
                </div>
              )}
            </>
          ))}
        </div>
      </div>
    ));
  };

  render() {
    const businessType = BUSINESS_TYPE_MAP[this.props.user.business_type].toLowerCase();

    return (
      <div className="offline-loan-document-collection">
        <div className="step">
          <div className="step-header">
            <div className="icon">
              <img src={require(`assets/capital/mail_icon.svg`)} alt="Loading icon" />
            </div>
            <div class="title-content-wrapper">
              <strong class="text-faded step-index">STEP 1</strong>
              <p class="step-instruction">Sign & Stamp the following documents</p>
            </div>
          </div>
          <div class="content-wrapper">
            {businessType && businessType in OFFLINE_COLLECTION_DOCUMENTS
              ? this.renderDetails(OFFLINE_COLLECTION_DOCUMENTS[businessType])
              : this.renderDetails(OFFLINE_COLLECTION_DOCUMENTS['others'])}
          </div>
        </div>

        <div className="step">
          <div className="step-header">
            <div class="icon">
              <img src={require(`assets/capital/sign_documents.svg`)} alt="Loading icon" />
            </div>
            <div className="title-content-wrapper">
              <strong className="text-faded step-index">STEP 2</strong>
              <p className="step-instruction">Mail the Signed & Stamped Documents</p>
            </div>
          </div>
          <div className="content-wrapper">
            <div className="section">
              <p class="content-padding">
                Please attach the copies of above signed and stamped documents and mail them to{' '}
                <a href={`mailto:${CAPITAL_LINKS.support_email}`}>{CAPITAL_LINKS.support_email}</a>
              </p>
            </div>
          </div>
        </div>
        <div className="actions pull-right">
          <Button.Transparent
            onClick={() => {
              this.props._trackNavigationActions('BACK', APPLICATION_STATES.CREDIT_OFFER_GENERATED);
              this.props.navigation.back();
            }}
          >
            <i className="i i-chevron-left" />
            Back
          </Button.Transparent>
          {!isPreceedingState(
            this.props.loanApplicationDetails.meta.data.application.status,
            APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
          ) && (
            <Button.Primary
              onClick={() => {
                this.props._trackNavigationActions('NEXT', this.props.nextState);
                this.props.navigation.next();
              }}
            >
              Next
              <i className="i i-chevron-right" />
            </Button.Primary>
          )}
        </div>
      </div>
    );
  }
}

export default OfflineDocumentCollection;

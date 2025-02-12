import React, { Component } from 'react';
import { connect } from 'react-redux';

import Note from '../../components/Note';
import {
  fetchLoanApplicationMeta,
  getAgreementStatus,
  getLegalAgreementUrl,
} from 'merchant/reducers/capital';

import '../Leegaliity';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { downloadFromUFH } from 'merchant/utils/downloadFile';
import * as NotificationActions from 'merchant_common/reducers/notifications';

import { APPLICATION_STATES, CAPITAL_PRODUCT_CODES } from '../constants';

class ContractEntity extends Component {
  constructor(props) {
    super(props);
    this.state = {
      downloading: false,
      error: null,
    };
  }
  callback = (resolve, reject, response) => {
    const { showNotification } = this.props;
    if (response.error) {
      showNotification({
        type: 'error',
        message: 'Agreement Signing Aborted.',
      });
      reject();
    } else {
      //This timeout is need as leegality has to inform LOS with signed
      // agreement.
      setTimeout(() => {
        const applicationId = this.props.loanApplicationDetails.meta.data.application.id;
        this.props
          .getAgreementStatus({
            application_id: applicationId,
          })
          .then((_) => this.props.fetchLoanApplicationMeta(applicationId))
          .then((_) => {
            showNotification({
              type: 'success',
              message: 'Loan Agreement signed Successfully!',
            });
            resolve();
          });
      }, 500);
    }
  };

  sign = () => {
    const { agreement_details } = this.props.loanApplicationDetails;
    if (agreement_details.data && agreement_details.data.signers) {
      return new Promise((resolve, reject) => {
        const obj = {
          callback: (response) => this.callback(resolve, reject, response),
        };
        const leegality = new window.Leegality(obj);
        leegality.init();
        //In future, if we support multiple signers:
        //signer.find(signer => signer.applicant_id ===
        // this.props.loanApplicationDetails.promoter_details.data.applicant.id
        leegality.esign(agreement_details.data.signers[0].sign_url);
      });
    }
  };

  hasSigned = () => {
    const { agreement_details } = this.props.loanApplicationDetails;
    if (agreement_details.data && agreement_details.data.signers) {
      return agreement_details.data.signers[0].sign_status === 'SIGNED';
    }
    return false;
  };

  handleDownloadAgreement = () => {
    const { loanApplicationDetails, _trackEvent } = this.props;
    _trackEvent({
      eventAction: 'Application | Download Loan Agreement',
      eventLabel: 'Complete Application | Loan Agreement',
    });
    this.setState({
      downloading: true,
      error: null,
    });
    const { showNotification } = this.props;
    return getLegalAgreementUrl({
      application_id: loanApplicationDetails.meta.data.application.id,
    })
      .then((res) => {
        if (res && !res.errors) {
          downloadFromUFH(res.data.ufh_ids.agreement)
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
              showNotification({
                type: 'error',
                message: 'Unable to download the agreement.',
              });
            });
        }
      })
      .catch((error) => {
        this.setState({
          downloading: false,
          error: true,
        });
        showNotification({
          type: 'error',
          message: 'Unable to download the agreement.',
        });
      });
  };

  render() {
    const { meta, agreement_details } = this.props.loanApplicationDetails;
    if (agreement_details.loading) return 'Fetching Agreement Status...';

    return (
      //Add this in CSS
      <div className="m-l m-r">
        {agreement_details.data ? (
          this.hasSigned() ? (
            <div className="panel panel-default m-all">
              <div className="panel-body">
                <strong>Signed Loan Agreement</strong>
                <p className="text--secondary">
                  Check the signed agreement with loan offer details.
                </p>
                <a className="link no-margin no-padding" onClick={this.handleDownloadAgreement}>
                  {this.state.downloading ? (
                    'Downloading...'
                  ) : (
                    <React.Fragment>
                      Download Loan Agreement
                      <i className="i i-chevron-right" />
                    </React.Fragment>
                  )}
                </a>
              </div>
              <div className="actions pull-right m-t">
                <button
                  className="btn btn-link"
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
                </button>
                <Button.Primary
                  className="no-margin"
                  onClick={() => {
                    this.props._trackNavigationActions(
                      'NEXT',
                      APPLICATION_STATES.NACH_CREATION_PENDING,
                    );
                    this.props.navigation.next();
                  }}
                >
                  Next
                  <i className="i i-chevron-right" />
                </Button.Primary>
              </div>
            </div>
          ) : (
            <div>
              <Note
                product={CAPITAL_PRODUCT_CODES.LOAN}
                applicationId={meta.data.application.id}
                message={
                  <span>
                    Your loan agreement has been generated with your loan offer details. The loan
                    agreement contains the commercials around the offer and the collection process.
                    Please sign the loan agreement by clicking the Sign agreement button.
                  </span>
                }
              />
              <div className="actions pull-right m-r">
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
                <AsyncBtn.Primary
                  className="m-r m-l"
                  onClick={this.sign}
                  showLoader={false}
                  pendingState="Signing..."
                >
                  Sign Loan Agreement
                  <i className="i i-chevron-right" />
                </AsyncBtn.Primary>
              </div>
            </div>
          )
        ) : (
          <Note
            product={CAPITAL_PRODUCT_CODES.LOAN}
            message={
              <span>
                Loan Agreement is not generated yet. You will be receiving a mail soon to sign the
                loan agreement. Please come back then.
              </span>
            }
          />
        )}
      </div>
    );
  }
}

export default connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    getAgreementStatus,
    fetchLoanApplicationMeta,
    ...NotificationActions,
  },
)(ContractEntity);

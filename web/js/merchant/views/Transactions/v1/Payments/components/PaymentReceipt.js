import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import {
  getReceiptDetails,
  sendReceipt,
  saveReceipt,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { showNotification } from 'merchant_common/reducers/notifications';
import { compose, bindActionCreators } from 'redux';

class PaymentReceipt extends Component {
  state = {
    isActionInProgress: false,
    invoiceId: null,
    receipt: null, // null => manual receipt, or if  payment_id => automatic receipt
    showCustomReceiptInput: false,
  };

  componentDidMount() {
    if (this.isSectionAllowed()) {
      getReceiptDetails(this.props.payment.id).then((res) => {
        if (res && res.data) {
          this.setState({
            receipt: res.data.receipt,
            invoiceId: res.data.invoice_id,
            downloadUrl: res.data.receipt_download_url,
          });
        }
      });
    }
  }

  sendReceipt = (receipt) => {
    this.setState({
      isActionInProgress: true,
    });

    sendReceipt(this.props.payment.id, receipt)
      .then((res) => {
        if (res.data && res.data.success) {
          this.setState({
            isActionInProgress: false,
            showCustomReceiptInput: false,
          });

          this.props.tracking.trackEvent(
            window.rzpQ.paymentPages().success('pp.receipt.resend', {
              receipt: this.state.receipt,
              invoiceId: this.state.invoiceId,
              type: this.state.showCustomReceiptInput && 'resend',
            }),
          );

          this.props.showNotification({
            type: 'success',
            message: 'Receipt is sent successfully',
          });

          setTimeout(this.props.onUpdateReferenceId);
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  openDownloadReceiptUrl = () => {
    window.location.href = this.state.downloadUrl;
  };

  downloadReceipt = (receipt) => {
    this.props.showNotification({
      type: 'success',
      message: 'Receipt is downloading...',
    });

    this.props.tracking.trackEvent(
      window.rzpQ.paymentPages().success('pp.receipt.download', {
        receipt: this.state.receipt,
        invoiceId: this.state.invoiceId,
        type: this.state.showCustomReceiptInput && 'download',
      }),
    );

    if (receipt) {
      this.setState({
        isActionInProgress: true,
      });

      saveReceipt(this.props.payment.id, receipt)
        .then((res) => {
          if (res && res.success) {
            this.openDownloadReceiptUrl();

            this.setState({
              isActionInProgress: false,
              showCustomReceiptInput: false,
              receipt: res.data.receipt,
            });

            setTimeout(this.props.onUpdateReferenceId);
          }
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors[0],
          });
        });
    } else {
      this.openDownloadReceiptUrl();
    }
  };

  handleSend = () => {
    if (!this.state.receipt) {
      this.setState({
        showCustomReceiptInput: true,
        isActionDownload: false,
      });
    } else {
      this.sendReceipt(); // receipt not required for automatic receipt
    }
  };

  handleDownload = () => {
    if (!this.state.receipt) {
      this.setState({
        showCustomReceiptInput: true,
        isActionDownload: true,
      });
    } else {
      this.downloadReceipt();
    }
  };

  closeManualReceiptAction = () => {
    this.setState({
      showCustomReceiptInput: false,
    });

    this.props.tracking.trackEvent(
      window.rzpQ.paymentPages().success('pp.receipt.enter_custom_cancel', {
        receipt: this.state.receipt,
        invoiceId: this.state.invoiceId,
      }),
    );
  };

  // eslint-disable-next-line consistent-return
  isSectionAllowed() {
    let hash = this.props.location.hash;

    if (hash) {
      hash = hash.substring(1);
      const allowedModules = ['paymentpages', 'paymentbuttons', 'subscription_buttons'];

      return allowedModules.indexOf(hash) > -1;
    }
  }

  render() {
    const showReceiptActions = !!this.state.invoiceId && this.isSectionAllowed(); // TODO: Must add support for product names as constants in dashboard

    // Payment Receipt Actions only to be shown for payment pages for which invoice id exists in GET /receipt call
    if (!showReceiptActions) {
      return null;
    }

    let manualReceiptActions;

    if (this.state.showCustomReceiptInput) {
      let manualReceiptActionHandler, manualReceiptActionHandlerLabel;

      if (this.state.isActionDownload) {
        manualReceiptActionHandler = this.downloadReceipt;
        manualReceiptActionHandlerLabel = 'Download';
      } else {
        manualReceiptActionHandler = this.sendReceipt;
        manualReceiptActionHandlerLabel = 'Send';
      }

      manualReceiptActions = (
        <Form onSubmit={(formData) => manualReceiptActionHandler(formData.receipt)}>
          <Input name="receipt" placeholder="Enter Reference ID" required />

          <div class="pull-right">
            <button
              class="btn btn-default m-r"
              type="button"
              onClick={this.closeManualReceiptAction}
              disabled={this.state.isActionInProgress}
            >
              Cancel
            </button>
            <button
              class="btn btn-primary"
              disabled={this.state.isActionInProgress}
              onClick={() => {
                this.props.tracking.trackEvent(
                  window.rzpQ.paymentPages().success('pp.receipt.enter_custom_save', {
                    receipt: this.state.receipt,
                    invoiceId: this.state.invoiceId,
                  }),
                );
              }}
            >
              {manualReceiptActionHandlerLabel}
              {this.state.isActionInProgress ? 'ing...' : ''}
            </button>
          </div>
        </Form>
      );
    }

    return (
      <EntityDetailRow label="Payment Receipt">
        <div>
          {this.state.receipt && <div class="m-b">Reference ID: {this.state.receipt}</div>}

          {this.state.showCustomReceiptInput ? (
            manualReceiptActions
          ) : (
            <div>
              <button
                class="btn btn-default m-r"
                onClick={this.handleSend}
                disabled={this.state.isActionInProgress}
              >
                {this.state.isActionInProgress ? 'Sending..' : 'Send'}
              </button>
              <button class="btn btn-default" onClick={this.handleDownload}>
                Download
              </button>
            </div>
          )}
        </div>
      </EntityDetailRow>
    );
  }
}

export default compose(
  withRouter,
  connect(null, (dispatch) => bindActionCreators({ showNotification }, dispatch)),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('PaymentReceiptDetails')),
)(PaymentReceipt);

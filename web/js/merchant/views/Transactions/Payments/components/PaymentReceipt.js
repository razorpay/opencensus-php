import { connect } from 'react-redux';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import {
  getReceiptDetails,
  sendReceipt,
  saveReceipt,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect(null, {
  showNotification,
})
export default class PaymentReceipt extends React.Component {
  state = {
    invoiceId: null,
    receipt: null, // null => manual receipt, or if  payment_id => automatic receipt
    showCustomReceiptInput: false,
  };

  componentDidMount() {
    getReceiptDetails(this.props.payment.id).then(res => {
      if (res && res.data) {
        this.setState({
          receipt: res.data.receipt,
          invoiceId: res.data.invoice_id,
        });
      }
    });
  }

  sendReceipt = receipt => {
    sendReceipt(this.props.payment.id, receipt)
      .then(res => {
        if (res && res.success) {
          this.setState({
            showCustomReceiptInput: false,
          });

          this.showNotification({
            type: 'success',
            message: 'Receipt is sent successfully',
          });
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
    window.location.href = `https://invoices.razorpay.com/v1/invoices/${
      this.state.invoiceId
    }/pdf?download=1`;
  };

  downloadReceipt = receipt => {
    if (receipt) {
      this.props.showNotification({
        type: 'success',
        message: 'Receipt is downloading...',
      });

      saveReceipt(this.props.payment.id, receipt)
        .then(res => {
          if (res && res.success) {
            this.openDownloadReceiptUrl();

            this.setState({
              showCustomReceiptInput: false,
              receipt: res.data.receipt,
            });
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
  };

  render() {
    const { payment } = this.props;
    const hideReceiptActions = !this.state.invoiceId;

    // Payment Receipt Actions only to be shown for payment pages for which invoice id exists in GET /receipt call
    if (hideReceiptActions) {
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
        <Form
          onSubmit={formData => manualReceiptActionHandler(formData.receipt)}
        >
          <Input name="receipt" placeholder="Enter Reference ID" required />

          <div class="pull-right">
            <button
              class="btn btn-default m-r"
              type="button"
              onClick={this.closeManualReceiptAction}
            >
              Cancel
            </button>
            <button class="btn btn-primary">
              {manualReceiptActionHandlerLabel}
            </button>
          </div>
        </Form>
      );
    }

    return (
      <EntityDetailRow label="Payment Receipt">
        <div>
          {this.state.receipt && (
            <div class="m-b">Reference ID: {this.state.receipt}</div>
          )}

          {this.state.showCustomReceiptInput ? (
            manualReceiptActions
          ) : (
            <div>
              <button class="btn btn-default m-r" onClick={this.handleSend}>
                Send
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

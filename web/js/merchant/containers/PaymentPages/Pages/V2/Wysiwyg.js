import { connect } from 'react-redux';
import { render } from 'react-dom';

import Button from 'component/Button';
import Svelte from './Svelte';

import DetailsView from './views/Details/index';
import FormView from './views/Form/index';

@connect(state => ({
  user: state.session.user,
  mode: state.session.mode,
  config: state.config.config,
  ...state.wysiwyg,
}))
export default class PaymentPagesWysiwyg extends React.PureComponent {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = { isPageReady: false };

  componentDidMount() {
    // Insert script in local

    const script = document.createElement('script');

    script.onload = () => {
      // Init the Svelte App in wysiwyg-root;
      this.setState({
        isPageReady: true,
      });
    };

    script.src = 'http://127.0.0.1:7999/static/hosted/wysiwyg.js';

    document.head.appendChild(script);
  }

  handleClose = () => {
    console.log('Handle close button');
  };

  initSubApps() {
    render(<DetailsView />, document.getElementById('details-section'));
    render(<FormView />, document.getElementById('form-section'));
  }

  handleCreate = () => {
    console.log('Handle Create..', this.props.paymentPageEntity);
  };

  render() {
    const { isPageReady } = this.state;

    const merchantData = {
      name: this.props.user.name,
      brand_color: this.props.config.brand_color,
      image:
        'https://cdn.razorpay.com/logos/AjkWrnqhycTNfR_medium.png' ||
        this.props.user.logo_url,
    };

    const actionBtns = (
      <React.Fragment>
        <Button class="Button--invert" onClick={this.handleCreate}>
          Create Page
        </Button>
      </React.Fragment>
    );

    return (
      <div class="payment-pages-v2">
        <Header
          title="Create New Payment Page"
          actionBtns={actionBtns}
          handleClose={this.handleClose}
          isPageReady={isPageReady}
        />
        {isPageReady && (
          <Svelte
            isTestMode={this.props.mode.toLowerCase() === 'test'}
            merchantData={merchantData}
            onMount={this.initSubApps}
          />
        )}
      </div>
    );
  }
}

const Header = ({ title, actionBtns, handleClose, isPageReady }) => {
  return (
    <div class="page-nav">
      <div class="page-size">
        <div class="page-title">{title}</div>

        {actionBtns &&
          isPageReady && <div class="page-action">{actionBtns}</div>}

        {handleClose &&
          isPageReady && (
            <Button.Transparent class="close-btn" onClick={handleClose}>
              ×
            </Button.Transparent>
          )}
      </div>
    </div>
  );
};

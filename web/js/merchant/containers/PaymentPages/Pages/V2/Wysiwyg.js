import { connect } from 'react-redux';
import { render } from 'react-dom';

import Button from 'component/Button';
import Svelte from './Svelte';

import DetailsView from './views/Details/index';
import FormView from './views/Form/index';

/* This is just dummy data. To be  from API call + taken from props */
const ppData = {
  title: 'Invoice and Bill Payments',
  description:
    "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, A when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type a A  And scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and  A  A typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.  AIt has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I",
  social_share: 1,
  support: {
    email: 'support@savethewhales.org',
    phone: '1800-1234-1323 (Timings: 9AM to 6PM)',
  },
  terms: 'If payment fails, we give free even ticket within 4 days. Enjoy!',
};

const isTestMode = true;

const merchantData = {
  name: 'Dummy Merchant Name',
  brand_color: '#4f8cf3',
  image: 'https://dummyimage.com/055aa0/ffffff/300x300&text=Merchant%20Logo',
};

@connect(state => ({
  user: state.session.user,
  config: state.config,
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
    console.log('...INIT sub apps to render...');
    setTimeout(() => {
      console.log('...SUCCESS sub apps rendered...');
      console.log(
        'DETAILS-SECTION',
        document.getElementById('details-section')
      );

      render(<DetailsView />, document.getElementById('details-section'));
      render(<FormView />, document.getElementById('form-section'));
    }, 2000); // Artificial delay. Need to see if we've any hook from Svelte's side for onMount, otherwise custom window listener to be added.
  }

  render() {
    const { user, config } = this.props;
    const { isPageReady } = this.state;

    const paymentPageData = {}; // If creating new payment page
    // const paymentPageData = ppData; // If editing existing payment page

    const actionBtns = (
      <React.Fragment>
        <Button class="Button--invert">Create Page</Button>
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
            isTestMode={isTestMode}
            merchantData={merchantData}
            paymentPageData={paymentPageData}
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

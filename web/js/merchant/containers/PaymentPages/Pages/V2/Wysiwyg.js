import Button from 'component/Button';

import FORM_SCHEMA from './form_schema';

export default class PaymentPagesWysiwyg extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentDidMount() {
    // Insert script in local

    const script = document.createElement('script');

    script.onload = () => {
      // Init the Svelte App in wysiwyg-root;
      const templateData = {
        schema: FORM_SCHEMA,
        data: {
          is_test_mode: true,
          merchant: {
            name: 'Dummy Merchant Name',
            brand_color: '#4f8cf3',
            image:
              'https://dummyimage.com/055aa0/ffffff/300x300&text=Merchant%20Logo',
          },
          payment_page_data: {
            title: 'Invoice and Bill Payments',
            description:
              "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, A when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type a A  And scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I. Lorem Ipsum is simply dummy text of the printing and  A  A typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.  AIt has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. I",
            social_share: 1,
            support: {
              email: 'support@savethewhales.org',
              phone: '1800-1234-1323 (Timings: 9AM to 6PM)',
            },
            terms:
              'If payment fails, we give free even ticket within 4 days. Enjoy!',
          },
        },
        context: {
          title: 'Payment Details',
          isEditMode: false, // should be true for dashboard
        },
      };

      window.RZP.renderApp('wysiwyg-root', templateData);
    };
    script.src = 'http://127.0.0.1:7999/static/hosted/wysiwyg.js';

    document.head.appendChild(script);
  }

  handleClose = () => {
    console.log('Handle close button');
  };

  render() {
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
        />
        <div id="wysiwyg-root" />
      </div>
    );
  }
}

const Header = ({ title, actionBtns, handleClose }) => {
  return (
    <div class="page-nav">
      <div class="page-size">
        <div class="page-title">{title}</div>

        <div class="page-action">{actionBtns}</div>

        {handleClose && (
          <Button.Transparent class="close-btn" onClick={handleClose}>
            ×
          </Button.Transparent>
        )}
      </div>
    </div>
  );
};

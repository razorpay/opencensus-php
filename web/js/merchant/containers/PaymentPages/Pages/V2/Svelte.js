export default class Svelte extends React.PureComponent {
  initialize = node => {
    if (!node) return;

    this.templateData = {
      // schema: FORM_SCHEMA,
      data: {
        is_test_mode: this.props.isTestMode,
        merchant: this.props.merchantData,
        payment_page_data: this.props.paymentPageData,
      },

      context: {
        title: 'Payment Details',
        isWYSIWYGMode: true,
      },
    };

    this._svelteInstance = window.RZP.renderApp(node, this.templateData);
  };

  componentWillUnmount() {
    this._svelteInstance && this._svelteInstance.teardown();
  }

  componentWillReceiveProps(nextProps) {
    // const newData = {}; // Update application data
    // this._svelteInstanceinstance.set(newData);
  }

  componentDidMount() {
    this.props.onMount();
  }

  render() {
    return React.createElement('div', {
      ref: this.initialize,
      id: 'wysiwyg-root',
    });
  }
}

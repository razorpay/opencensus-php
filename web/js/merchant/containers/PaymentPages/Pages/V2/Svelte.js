import DESC_LIMIT from './views/Details/Description';

export default class Svelte extends React.Component {
  shouldComponentUpdate() {
    return false; // No need to re-render again, all 3 React apps are working independently bridged via store
  }

  initialize = node => {
    if (!node) return;

    const { payment_page_id } = this.props;

    this.templateData = {
      is_test_mode: this.props.isTestMode,
      merchant: this.props.merchantData,
      context: {
        page_title: payment_page_id
          ? 'Edit Payment Page - ' + payment_page_id
          : 'Create New Payment Page',
        form_title: 'Payment Details',
        isWYSIWYGMode: true,
        DESC_LIMIT: {
          DESKTOP: DESC_LIMIT.DESKTOP,
          MOBILE: DESC_LIMIT.MOBILE,
        },
      },
      // Other keys are not required by Svelte app in isWYSIWYGMode
    };

    this._svelteInstance = window.RZP.renderApp(node, this.templateData);
  };

  componentWillUnmount() {
    this._svelteInstance && this._svelteInstance.destroy();
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

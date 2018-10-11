export default class Svelte extends React.Component {
  shouldComponentUpdate() {
    return false; // No need to re-render again, all 3 React apps are working independently bridged via store
  }

  initialize = node => {
    if (!node) return;

    this.templateData = {
      data: {
        is_test_mode: this.props.isTestMode,
        merchant: this.props.merchantData,
      },

      context: {
        title: 'Payment Details',
        isWYSIWYGMode: true,
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
    console.log('Render Svelete: should re-render only once');

    return React.createElement('div', {
      ref: this.initialize,
      id: 'wysiwyg-root',
    });
  }
}

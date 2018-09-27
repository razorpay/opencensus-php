import Button from 'component/Button';

export default class PaymentPagesWysiwyg extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentDidMount() {
    // Insert script in local

    const script = document.createElement('script');

    script.onload = () => {
      // Init the Svelte App in wysiwyg-root;
    };
    script.src = '/static/hosted/script_path/script.js';

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
      </div>

      {handleClose && (
        <Button.Transparent class="close-btn" onClick={handleClose}>
          ×
        </Button.Transparent>
      )}
    </div>
  );
};

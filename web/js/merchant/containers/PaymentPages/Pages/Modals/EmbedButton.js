import { connect } from 'react-redux';
import ModalHeader from 'rzp/ui/ModalHeader';
import Button from 'component/Button';
import Input from 'component/Input';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import ReactDOMServer from 'react-dom/server';
import { closeModal } from 'rzp/modules/modals';

@connect(
  state => ({
    config: state.config.config,
  }),
  { closeModal }
)
export default class extends React.Component {
  state = { btnSize: '0', btnLabel: 'Pay Now' };
  componentWillMount() {
    const script = document.createElement('script');

    script.onload = () => {
      const textClr =
        !window.colorLib || window.colorLib.isDark(this.color)
          ? '#fff'
          : 'rgba(0, 0, 0, 0.85)';

      this.setState({
        textClr,
      });
    };
    script.src = 'https://cdn.razorpay.com/static/assets/color.js';

    document.head.appendChild(script);
  }

  updateButtonText = e => {
    this.setState({
      btnLabel: e.target.value,
    });
  };

  updateButtonSize = e => {
    this.setState({
      btnSize: e.target.value,
    });
  };

  get color() {
    return this.props.config.brand_color;
  }

  render() {
    const { shortUrl, closeModal } = this.props;
    const { btnLabel, btnSize } = this.state;
    const el = document.getElementById('embed-btn-preview');

    let width;
    switch (btnSize) {
      case '1':
        width = 180;
        break;
      case '2':
        width = 120;
        break;
      default:
        width = 240;
    }

    const previewBtnCode = (
      <span>
        <a
          href={shortUrl}
          style={{
            position: 'relative',
            display: 'block',
            minHeight: 38,
            width,
            padding: 10,
            lineHeight: '18px',
            fontWeight: 600,
            fontSize: 14,
            fontFamily:
              'Lato, Muli, -apple-system, BlinkMacSystemFont, Arial, sans-serif',
            wordBreak: 'break-word',
            borderRadius: 2,
            textAlign: 'center',
            backgroundColor: this.color,
            color: this.state.textClr || '#fff',
            boxShadow: '0 0 24px 0 rgba(0,0,0,0.2)',
            zIndex: 2,
          }}
          target="_blank"
        >
          {this.state.textClr && btnLabel}
        </a>
        <div style={{ marginTop: 4, textAlign: 'center' }}>
          <img
            height="16px"
            src="https://cdn.razorpay.com/static/assets/powered_by_razorpay.png"
          />
        </div>
      </span>
    );

    const liveCode = ReactDOMServer.renderToStaticMarkup(previewBtnCode);

    return (
      <div>
        <ModalHeader title="Create Embed Button" onCloseClick={closeModal} />

        <div class="modal-body embed-button-form" style={{ paddingTop: 0 }}>
          <div class="ModalForm ModalForm--Share">
            <Input
              label="What will the button say?"
              className="Input--vTop"
              placeholder="Enter button text"
              onChange={this.updateButtonText}
              value={btnLabel}
              autoFocus
            />
            <Input.Radio
              label="Button size"
              options={['Large', 'Medium', 'Small']}
              className="Input--vTop"
              value={btnSize}
              onChange={this.updateButtonSize}
            />
            <div className="Input Input--vTop Input--radio">
              <div class="Input-label">Preview</div>
              <div id="embed-btn-preview">{previewBtnCode}</div>
            </div>
            <Input.Textarea
              id="code-copier"
              label={() => (
                <div>
                  HTML Code
                  <div class="description">
                    Copy & Paste this HTML in your code
                    <CustomClipboard value={liveCode}>
                      <button class="btn btn-link btn-xs">
                        <i class="i i-copy" style={{ marginRight: 4 }} />
                        Copy
                      </button>
                    </CustomClipboard>
                  </div>
                </div>
              )}
              class="Input--vTop"
              value={liveCode}
              readOnly
            />

            <br />
            <Button.Primary class="btn-block" onClick={closeModal}>
              Done
            </Button.Primary>
          </div>
        </div>
      </div>
    );
  }
}

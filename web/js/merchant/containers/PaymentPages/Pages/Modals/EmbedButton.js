import { connect } from 'react-redux';
import ModalHeader from 'rzp/ui/ModalHeader';
import Button from 'component/Button';
import Input from 'component/Input';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { closeModal } from 'merchant_common/reducers/modals';
import { trackCreateButtonSizeSelection, trackCreateButtonCancel } from '../ga';

const BTN_SIZES = ['Large', 'Medium', 'Small'];

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
            margin: '0 auto',
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

    /* Embed Button */

    const scriptURL = 'https://cdn.razorpay.com/static/embed_btn/bundle.js';
    const buttonClass = 'razorpay-embed-btn';
    const scriptTagID = 'razorpay-embed-btn-js';

    const embedBtnCode = `<div class="${buttonClass}" data-url="${shortUrl}" data-text="${
      this.state.btnLabel
    }" data-color="${this.color}" data-size="${BTN_SIZES[
      btnSize
    ].toLowerCase()}">
  <script>
    (function(){
      var d=document; var x=!d.getElementById('${scriptTagID}')
      if(x){ var s=d.createElement('script'); s.defer=!0;s.id='${scriptTagID}';
      s.src='${scriptURL}';d.body.appendChild(s);} else{var rzp=window['__rzp__'];
      rzp && rzp.init && rzp.init()}})();
  </script>
</div>
    `;

    return (
      <div>
        <ModalHeader
          title="Create Payment Button"
          onCloseClick={() => {
            closeModal();
            trackCreateButtonCancel();
          }}
        />

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
              options={BTN_SIZES}
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
                    <CustomClipboard value={embedBtnCode}>
                      <button
                        class="btn btn-link btn-xs"
                        onClick={() =>
                          trackCreateButtonSizeSelection(BTN_SIZES[btnSize])
                        }
                      >
                        <i class="i i-copy" style={{ marginRight: 4 }} />
                        Copy
                      </button>
                    </CustomClipboard>
                  </div>
                </div>
              )}
              class="Input--vTop"
              value={embedBtnCode.trim()}
              readOnly
            />

            <br />
            <Button.Primary
              class="btn-block"
              onClick={() => {
                closeModal();
                trackCreateButtonCancel();
              }}
            >
              Done
            </Button.Primary>
          </div>
        </div>
      </div>
    );
  }
}

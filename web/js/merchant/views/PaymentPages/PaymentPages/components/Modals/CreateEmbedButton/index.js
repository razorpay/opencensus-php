import React from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';

import PreviewEmbedButton from './PreviewEmbedButton';
import { trackCreateButtonSizeSelection, trackCreateButtonCancel } from '../../../ga';

const BTN_SIZES = ['Large', 'Medium', 'Small'];

class CreateEmbedButton extends React.Component {
  state = { btnSize: '0', btnLabel: 'Pay Now' };

  updateButtonText = (e) => {
    this.setState({
      btnLabel: e.target.value,
    });
  };

  updateButtonSize = (e) => {
    this.setState({
      btnSize: e.target.value,
    });
  };

  get merchantThemeColor() {
    return this.props.config.brand_color;
  }

  render() {
    const { closeModal, id } = this.props;
    const { btnLabel, btnSize } = this.state;
    // const el = document.getElementById('embed-btn-preview');

    /* Embed Button */

    const scriptURL = 'https://cdn.razorpay.com/static/embed_btn/bundle.js';
    const buttonClass = 'razorpay-embed-btn';
    const scriptTagID = 'razorpay-embed-btn-js';
    const pageUrl = `https://pages.razorpay.com/${id}/view`;

    const embedBtnCode = `<div className="${buttonClass}" data-url="${pageUrl}" data-text="${
      this.state.btnLabel
    }" data-color="${this.merchantThemeColor}" data-size="${BTN_SIZES[btnSize].toLowerCase()}">
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
          title="Create Hyperlink Button"
          onCloseClick={() => {
            closeModal();
            trackCreateButtonCancel();
          }}
        />

        <div className="modal-body embed-button-form" style={{ paddingTop: 0 }}>
          <div className="ModalForm ModalForm--Share">
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
              <div className="Input-label">Preview</div>
              <PreviewEmbedButton url={pageUrl} btnSize={btnSize} btnLabel={btnLabel} />
            </div>
            <Input.Textarea
              id="code-copier"
              label={() => (
                <div>
                  HTML Code
                  <div className="description">
                    Copy & Paste this HTML in your code
                    <CustomClipboard value={embedBtnCode}>
                      <button
                        className="btn btn-link btn-xs"
                        onClick={() => trackCreateButtonSizeSelection(BTN_SIZES[btnSize])}
                      >
                        <i className="i i-copy" style={{ marginRight: 4 }} />
                        Copy
                      </button>
                    </CustomClipboard>
                  </div>
                </div>
              )}
              className="Input--vTop"
              value={embedBtnCode.trim()}
              readOnly
            />

            <br />
            <Button.Primary
              className="btn-block"
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

export default connect((state) => ({ config: state.config.config }), { closeModal })(
  CreateEmbedButton,
);

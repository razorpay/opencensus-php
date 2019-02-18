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
            textAlign: 'center',
            backgroundColor: this.color,
            color: this.state.textClr || '#fff',
            width,
            padding: 10,
            borderRadius: 2,
            boxShadow: '0 0 24px 0 rgba(0,0,0,0.2)',
            lineHeight: '18px',
            fontWeight: 600,
            fontSize: 14,
            fontFamily: '"Lato", "Muli"',
          }}
          target="_blank"
        >
          {btnLabel}
        </a>
        <div style={{ fontSize: 8, marginTop: 8 }}>
          <b>Powered by </b>{' '}
          <img
            height="12px"
            style={{ verticalAlign: 'bottom' }}
            src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGcAAAAXCAMAAAAhvaEKAAAAxlBMVEUAAAAseN8BKFcIJlMGJlQGJlQHJlQHJVQHJlQHJlQGJlQGJlMGJlQHJlQHJlQKKFUFJVQJKVYHJlQGJVQHJVIIJVQHJlQIJlQJJ1YHJlQIJlQGJlQGJlQGJVMHJlQGJlUHJlQHJlQHJlMzlv8HJlUzlv8zl/8HKFcHJlQylfw0l/8zlf8xkfkwj/Y0lP43ov0zlv80mv8zl/8eYa0ylP0CDzozlv8AAB4AABIBBy4ylf8AABYAAAAHKFsGJlU2oP80m/8ILGEuefE7AAAAPXRSTlMABQs7Vury1/ngKnRNwUcXMRLOfCOD96gdrUHHopyVNbqMXdezoXxrZAz+9U9jKBjluo5YPevLy7eHVJZIz9ZxYQAAA4pJREFUSMe1VGl32jAQXMm3McYXYMyZ0BASSNIm6d2Vzf//U501tO5L09d+KMN7WFpZM7uzkulXKKKbGzo7IPPl6uw6UsxjfYXBCXQWgHa/rps9dfznUXq4um1u396TKpJQMDiP0Je3dbOr3ykqA+P6o5HrzAZnENrU9Yf1bb0hitgI2PC865X6ZUTUDV4sIvRizynwoxE3u3pzv65v90QX7F/DutxlT9Nrvep2vQr154Ci/bu7b/tdvX4g1WdnILGAV8gozvNExfaSyqiFnWCtuMxzW2NbYUc6zK91clnRsjeMWy5t571S23ZR2nZIQIK3Tgk+PH38tGnkVIcOjwm4NNyjYmX44Mx9vqDZgRmOYqSGDmMyLYlm7I7dQ5ZmnI0ZyCFjZcwHb2Y4jpgXCCQOz051pZ/BelU3G+E3b+zt9WLEmUo9NquZC/qYZl7WD9C1hCbM/YUn7UsDNuz4vRD9dMZ9w25Blsv+ZIKAm4aGJyQGeelRJvL48P5pXe+eiebYCkCgoDFaRWQbEwxIKwozdmLK2eQkdFOywLbVheoZg+r0lE1F7Ts0NKZPg4CnSvpdkcJvMDfs88e722Z9Q9pjd4rEzRgOjFoLExdpKVAH7CVUOtwnAgcePYa3wIR9ixRS9MPoaJ5teCiVBCpuR0RtGr7hr5u6QXuWI54oKj12ljRkY5PGHrApaVi/pHaGYOXD+zfsFBiLppziKQfpnJ2QtGyNpX1BnGFNdFC8yJjrd3VzRz9SRFe3SNNNiMA2srBwvE/CECG4YGNp58gRG2n0UTqT2yCuwGpY7HrgEBlh9o3h909vm909BIRUC00PAvhTsNprAwut0lR0cOS2YmnUWtJKeyUlGWxT4I0oHcsJEAvAfH2UARtmh093Tf1BiQUgVciE57J/lDlGss3hrecFzhjls8ky5n5KF1Iasl/xiJ3+iM1WWmXcqWtaq9EE0Wt1wAzbfP782DSPRPHJna3hN5Ti9vAKp+SSpowtbHiB+h2MnKEm3RdzFBUOry4YNdmYhB5WZ+3B0LISlKdytNVicP/8/ABVyyoJSK0qVNR+CAaVpSnBK8vl0pKLUEZ21X4xllVIx7OVk2VXmgRpZIdUVEuwS8Gi/Teof1sEW3wKdeFTAgvq5kdQ9239+ewWXnytu3flOLf2dRslCohrmfT6v0DJNR7/Xju0VuxaeP4vpFVcvBbXcZx0s+9rYnr6FWty9AAAAABJRU5ErkJggg=="
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

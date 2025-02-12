import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';

function MobileAppQRCode(props) {
  const isActivated = props.user.activation_status === 'activated';
  const qrCodeUrl = isActivated
    ? 'https://cdn.razorpay.com/static/assets/mobile-app-camp/MTUs_QR-code.jpeg'
    : 'https://cdn.razorpay.com/static/assets/mobile-app-camp/Prospects_QR-code.jpeg';

  return (
    <div className="MobileAppQRCode--container">
      <ModalHeader title="Scan to Download" onCloseClick={props.closeModal} />
      <div className="qrcode">
        <div>
          <img src={qrCodeUrl} />
        </div>
      </div>
      <div className="note">
        <p>Scan the QR code above from the camera app in your mobile to download.</p>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(MobileAppQRCode);

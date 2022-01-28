import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import InfoComponent from 'merchant/views/MagicCheckout/ShippingServices/ShipRocketAccountModal/components/InfoComponent';
import ShipRocketForm from 'merchant/views/MagicCheckout/ShippingServices/ShipRocketAccountModal/containers/ShipRocketForm';
import ModalHeader from 'common/ui/ModalHeader';

const stepTexts = [
  {
    header: 'Link Shiprocket account',
    desc:
      'Magic checkout will able to check pincode serviceability and receive realtime order statuses for better RTO protection',
    Component: InfoComponent,
  },
  {
    header: 'Link Shiprocket account',
    desc:
      'Magic checkout will able to check pincode serviceability and receive realtime order statuses for better RTO protection',
    Component: InfoComponent,
  },
  {
    header: 'Enter your Shiprocket API credentials',
    desc: 'Please enter your Shiprocket API user account credentials:',
    Component: ShipRocketForm,
  },
];

const LinkAccount = ({ closeModal, step, setStep }) => {
  const { Component, header, desc } = stepTexts[step];
  return (
    <>
      <ModalHeader onCloseClick={closeModal} />
      <div className="link-account-content">
        <div className="link-account-header font-bold color-black">{header}</div>
        <div className="link-account-desc">{desc}</div>
        <Component step={step} setStep={setStep} />
      </div>
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(LinkAccount);

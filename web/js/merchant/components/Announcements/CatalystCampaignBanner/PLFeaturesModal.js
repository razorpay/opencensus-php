import { closeModal } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';
import { Link } from 'react-router-dom';

const PLFeaturesModal = ({ closeModal }) => {
  return (
    <div className='pl-catalyst-modal'>
      <div className='white-circle'></div>
      <button type="button" className="close" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div className='modal-body'>
        <h3 className='heading nomob'>
          <img className='pl-bullet' src='https://cdn.razorpay.com/static/assets/payment-links/pl-bullet.svg' alt='Point'/>
          Bring your shoppers back to buy your stuff
        </h3>
        <h4 className='sub-heading'>
          <img className='pl-bullet mob' src='https://cdn.razorpay.com/static/assets/payment-links/pl-bullet.svg' alt='Point'/>
          Get paid instantly by {' '}
          <br className='mob' />
          sharing payment links on the go
        </h4>
        <div className='pl-planes-group mob'>
          <img src='https://cdn.razorpay.com/static/assets/payment-links/pl-plane-purple.svg' className='pl-plane__rel' id='purple-mob-top'/>
          <img src='https://cdn.razorpay.com/static/assets/payment-links/pl-plane-green.svg' className='pl-plane__rel' id='green-mob-middle' />
          <img src='https://cdn.razorpay.com/static/assets/payment-links/pl-plane-purple.svg' className='pl-plane__rel' id='purple-mob-bottom' />
        </div>
        <div className='section'>
          <div className='left-section'>
            <ul>
              <li>Convert COD to online payments</li>
              <li className='nomob'>Enable partial payment options</li>
              <li>Send timely reminders to customers</li>
              <li>Share via SMS, WhatsApp and Chatbot</li>
              <li className='nomob'>Create multiple links in one go</li>
              <li className='nomob'>Recapture failed payments instantly</li>
            </ul>
            <Link to='/paymentlinks/new' className='modal-cta' onClick={closeModal}>
              <Button.Primary className="btn btn-primary" type="button">
                Create a Payment Link
              </Button.Primary>
            </Link>
          </div>
          <div className='right-section nomob'>
            <div className='x-dashboard-view'>
              <div className="tab-section">
                <span className="dot" id="red"></span>
                <span className="dot" id="orange"></span>
                <span className="dot" id="green"></span>
              </div>
              <img src='https://cdn.razorpay.com/static/assets/payment-links/pl-creation-steps.gif' alt='Steps to create a Payment Link'/>
            </div>
          </div>
        </div>
        <div className='footer'>
          <div className='title'>
            Trusted by
          </div>
          <div className='company-list'>
            <div className='company-group'>
              <div className='logo'>
                <img src='https://cdn.razorpay.com/static/assets/external-products/zivame.svg' alt='Zivame'/>
              </div>
              <div className='logo'>
                <img src='https://cdn.razorpay.com/static/assets/external-products/furlenco.svg' alt='Furlenco'/>
              </div>
            </div>
            <div className='company-group'>
              <div className='logo'>
                <img src='https://cdn.razorpay.com/static/assets/external-products/mamaearth.svg' alt='Mamaearth'/>
              </div>
              <div className='logo'>
                <img src='https://cdn.razorpay.com/static/assets/external-products/kama-ayurveda.svg' alt='Kama Ayurveda'/>
              </div>
              <div className='logo'>
                <img src='https://cdn.razorpay.com/static/assets/external-products/vijay-sales.svg' alt='Vijay Sales'/>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className='nomob'>
        <img src='https://cdn.razorpay.com/static/assets/payment-links/pl-plane-green.svg' className='pl-plane' id='green-top-left'/>
        <img src='https://cdn.razorpay.com/static/assets/payment-links/pl-plane-purple.svg' className='pl-plane' id='purple-top-left'/>
        <img src='https://cdn.razorpay.com/static/assets/payment-links/pl-plane-green.svg' className='pl-plane' id='green-bottom-right'/>
      </div>
    </div>
  );
};

export default compose(
  connect(null, { closeModal })
)(PLFeaturesModal);

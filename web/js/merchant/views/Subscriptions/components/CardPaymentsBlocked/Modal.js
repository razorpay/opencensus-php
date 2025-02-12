import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';
import Button from 'common/new-ui/Button';
import { DocLink } from 'merchant/components/DocsLink';
import useLocalStorageCheck from 'merchant/hooks/localStorageCheck';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import BackgroundImg from './background.svg';

const CardPaymentsBlockedModal = ({ user }) => {
  const [isHidden, toggleIsHidden] = useLocalStorageCheck(
    `${user.current}_cards_recurring_blocked__${
      user.isChargeAtWillEnabled ? 'caw' : 'subscription'
    }`,
  );

  if (isHidden) return null;

  return (
    <ModalMask>
      <Modal
        className={classList('CardPaymentsBlockedModal', 'animate-down')}
        showCloseBtn={false}
        onClose={toggleIsHidden}
      >
        <ModalContent>
          <div className="header">
            <img src={BackgroundImg} />
            <div className="text-center">
              <div className="heading">
                {user.isChargeAtWillEnabled ? (
                  <>Impact on Card Payments for Registration Links</>
                ) : (
                  <>Impact on Card Payments for New Subscriptions</>
                )}
              </div>
            </div>
          </div>
          <div className="content">
            <div>
              {user.isChargeAtWillEnabled ? (
                <>
                  Due to recent RBI mandate, cards issued by Indian banks as payment method is being
                  temporarily disabled for new registration links starting 01 Apr, 2021
                </>
              ) : (
                <>
                  Due to a recent RBI mandate, cards issued by Indian banks as a payment method for
                  new Subscription registrations is being temporarily disabled starting 01 Apr,
                  2021.
                </>
              )}
            </div>
            <div>
              {user.isChargeAtWillEnabled ? (
                <>To learn more about these changes and implications, please refer to </>
              ) : (
                <>To understand business impact for such subscriptions, please refer to</>
              )}{' '}
              <DocLink
                href={
                  user.isChargeAtWillEnabled
                    ? 'https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/recurring-payments'
                    : 'https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/subscriptions'
                }
                target="_blank"
                rel="noopener noreferrer"
                className="doc-link"
              >
                our documentation here.
              </DocLink>
            </div>
            <div className="banner">
              {user.isChargeAtWillEnabled ? (
                <>
                  Registration links and tokens which were created on or before 31 Mar, 2021 are not
                  impacted and will continue to function normally. <br />
                  <br /> Once card issuers start to comply with RBI mandate, this cards will be
                  enabled for registration links.
                </>
              ) : (
                <>
                  Subscriptions which started on or before 31 Mar, 2021 are not impacted and will
                  continue to function normally. <br />
                  <br /> Once card issuers start to comply with RBI mandate, this payment method
                  will be enabled for Subscriptions.
                </>
              )}
            </div>
            <div className="italic">
              If you have any further queries or concerns, please reach out to support.
            </div>
            <div className="footer">
              <Button.Primary onClick={toggleIsHidden}>Close</Button.Primary>
            </div>
          </div>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};

export default connect((state) => ({ user: state.session.user }))(CardPaymentsBlockedModal);

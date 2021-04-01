import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';
import Button from 'common/new-ui/Button';

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
        class={classList('CardPaymentsBlockedModal', 'animate-down')}
        showCloseBtn={false}
        onClose={toggleIsHidden}
      >
        <ModalContent>
          <div class="header">
            <img src={BackgroundImg} />
            <div class="text-center">
              <div class="heading">
                {user.isChargeAtWillEnabled ? (
                  <>Impact on Card Payments for Registration Links</>
                ) : (
                  <>Impact on Card Payments for New Subscriptions</>
                )}
              </div>
            </div>
          </div>
          <div class="content">
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
              <a
                href={
                  user.isChargeAtWillEnabled
                    ? 'https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/recurring-payments'
                    : 'https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/subscriptions'
                }
                target="_blank"
                rel="noopener noreferrer"
                class="doc-link"
              >
                our documentation here.
              </a>
            </div>
            <div class="banner">
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
            <div class="italic">
              If you have any further queries or concerns, please reach out to support.
            </div>
            <div class="footer">
              <Button.Primary onClick={toggleIsHidden}>Close</Button.Primary>
            </div>
          </div>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};

export default connect((state) => ({ user: state.session.user }))(CardPaymentsBlockedModal);

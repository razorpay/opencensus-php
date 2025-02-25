import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Link from '@razorpay/blade-old/src/icons/Link';
import Button from '../../Button';
import DesktopOnlyView from '../../responsiveView/DesktopOnlyView';
import MobileOnlyView from '../../responsiveView/MobileOnlyView';
import getBrowserDetails from '../../../utils/getBrowser';
import { SIGNIN } from '../../../screens/screenHelpers';
import Modal from '../../Modal';

const browsers = {
  chrome: 'chrome',
  safari: 'safari',
  firefox: 'firefox',
};

const cookieInfoDocs = {
  chrome: 'https://support.google.com/chrome/answer/95647?co=GENIE.Platform%3DDesktop&hl=en-GB',
  safari: 'https://support.apple.com/en-in/guide/safari/sfri11471/mac',
  firefox: 'https://support.mozilla.org/en-US/kb/websites-say-cookies-are-blocked-unblock-them',
};

const GoogleAuthCookieModal = ({ closeModal, context }) => {
  const [browser, setBrowser] = useState('');
  const [showKnowMore, setShowKnowMore] = useState(false);
  const module = context === SIGNIN ? 'Signin' : 'Signup';

  useEffect(() => {
    const browserName = getBrowserDetails().browser;
    setBrowser(browserName);

    if (
      browserName === browsers.chrome ||
      browserName === browsers.firefox ||
      browserName === browsers.safari
    ) {
      setShowKnowMore(true);
    }
  }, []);

  const handleKnowMore = () => {
    let docsLink;
    if (browser === browsers.chrome) docsLink = cookieInfoDocs.chrome;
    if (browser === browsers.safari) docsLink = cookieInfoDocs.safari;
    if (browser === browsers.firefox) docsLink = cookieInfoDocs.firefox;
    closeModal();
    window.open(docsLink, '_blank');
  };

  return (
    <Modal>
      <View>
        <Heading weight="bold" size="xlarge">
          {`'${module} with Google' failed`}
        </Heading>
        <Space padding={[0.5, 0]}>
          <Text size="large" color="shade.980">
            {`It looks like your browser has blocked the third party cookies. You can choose to update your browser settings to allow third party cookies or you can ${module} with email & password. ${
              module === 'Signin' ? 'If cookies are already enabled, please refresh the page.' : ''
            }`}
          </Text>
        </Space>
      </View>
      <DesktopOnlyView>
        <Space margin={[6.5, 0, 0, 0]}>
          <Flex justifyContent="flex-end">
            <View>
              {showKnowMore ? (
                <Space margin={[0, 1.5, 0, 0]}>
                  <Button onClick={handleKnowMore} variant="tertiary" icon="link" iconAlign="right">
                    Know more
                  </Button>
                </Space>
              ) : null}
              <Button onClick={closeModal}>Okay, got it</Button>
            </View>
          </Flex>
        </Space>
      </DesktopOnlyView>
      <MobileOnlyView>
        <Space margin={[5.5, 0, 0, 0]}>
          <Flex flexDirection="column-reverse">
            <View>
              {showKnowMore ? (
                <Size width="100%">
                  <Space margin={[1.5, 0, 0, 0]}>
                    <Button
                      onClick={handleKnowMore}
                      variant="tertiary"
                      icon={Link}
                      iconAlign="right"
                    >
                      Know more
                    </Button>
                  </Space>
                </Size>
              ) : null}
              <Size width="100%">
                <Button onClick={closeModal}>Okay, got it</Button>
              </Size>
            </View>
          </Flex>
        </Space>
      </MobileOnlyView>
    </Modal>
  );
};

GoogleAuthCookieModal.propTypes = {
  closeModal: PropTypes.func.isRequired,
  context: PropTypes.string,
};

export default GoogleAuthCookieModal;

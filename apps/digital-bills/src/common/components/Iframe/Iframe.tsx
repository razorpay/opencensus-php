import React, { useEffect, useState } from 'react';
import { Box, Heading, Spinner } from '@razorpay/blade/components';

type IframeProps = {
  pathname: string;
  onRouteChange?: (pathname: string) => void;
  title?: string;
};

const Iframe = (props: IframeProps): React.ReactElement => {
  const { pathname, onRouteChange, title = 'Digital Billing Iframe' } = props;
  const [isLoading, setIsLoading] = useState(true);
  useEffect(() => {
    setIsLoading(true);
    const messageListener = (event: MessageEvent): void => {
      if (event.origin !== process.env.UNIVERSE_PUBLIC_BILLME_IFRAME_URL) return;
      if (event.data.type === 'locationChange') {
        const eventPayload = event.data.payload;

        onRouteChange?.(eventPayload?.location);
        if (eventPayload?.isAuthenticated && eventPayload?.location !== '/login') {
          setIsLoading(false);
        }
      }
    };
    window.addEventListener('message', messageListener);
    return () => {
      window.removeEventListener('message', messageListener);
    };
  }, [pathname]);

  return (
    <Box
      height="100%"
      width="100%"
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="start"
    >
      <iframe
        id="billme-iframe"
        title={title}
        style={{
          display: isLoading ? 'none' : 'block',
          width: '100%',
          height: '100%',
          border: 'none',
        }}
        src={`${process.env.UNIVERSE_PUBLIC_BILLME_IFRAME_URL}${pathname}`}
        allow={`clipboard-read; clipboard-write self ${process.env.UNIVERSE_PUBLIC_BILLME_IFRAME_URL}`}
      />

      {isLoading ? (
        <Box
          display="flex"
          flexDirection="column"
          justifyContent="center"
          alignItems="center"
          gap="spacing.4"
          width="100%"
        >
          <Heading>Page loading</Heading>
          <Spinner size="xlarge" accessibilityLabel="Page loading" />
        </Box>
      ) : null}
    </Box>
  );
};

export default Iframe;

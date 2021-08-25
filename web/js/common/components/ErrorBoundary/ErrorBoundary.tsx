import React, { Component, ErrorInfo, ReactNode } from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Link from '@razorpay/commander-shield/src/shared/Link';
import View from '@razorpay/blade-old/src/atoms/View';
import styled from 'styled-components';
import ErrorImage from './error_illustration.svg';

const Container = styled(View)`
  margin-top: 56px;
  text-align: center;
`;

interface ContentWithSDKProps {
  lastEventId: string | null;
  showReportDialog: () => void;
}

export const ContentWithSDK: React.FC<ContentWithSDKProps> = ({
  lastEventId,
  showReportDialog,
}) => {
  return (
    <Size height="100%" width="100%">
      <Container>
        <img src={ErrorImage} />
        <Text size="large" weight="bold" as="p" align="center">
          We&apos;re sorry — something&apos;s gone wrong.
        </Text>
        <Text size="large" align="center">
          Our team has been notified, but
          <Link onClick={showReportDialog}> click here </Link>
          <Text size="large" as="span" align="center">
            to fill out a report.
          </Text>
        </Text>
        {!!lastEventId && (
          <Text align="center">
            Error Code: <code>{lastEventId}</code>
          </Text>
        )}
      </Container>
    </Size>
  );
};

interface ContentWithoutSDKProps {
  error: any;
  info: ErrorInfo | null;
}

export const ContentWithoutSDK: React.FC<ContentWithoutSDKProps> = ({ error, info }) => {
  return (
    <Size height="100%" width="100%">
      <View>
        <Text>An error occured</Text>
        <pre>{error && error.toString()}</pre>
        <pre>{info && info.componentStack.replace(/^\n/gm, '')}</pre>
      </View>
    </Size>
  );
};

export default class ErrorBoundary extends Component {
  state = {
    error: false,
    info: null,
    eventId: null,
  };

  componentDidCatch(error: Error, info: ErrorInfo): void {
    let eventId = null;
    if (window.Sentry) {
      window.Sentry.withScope((scope: { setExtras: (arg0: React.ErrorInfo) => void }) => {
        scope.setExtras(info);
        eventId = window.Sentry.captureException(error);
      });
    } else {
      // eslint-disable-next-line no-console
      console.error(error, info);
    }

    this.setState({ error, info, eventId });
  }

  render(): ReactNode {
    const SDK = window.Sentry;
    const hasSDK = !!SDK;
    const showReportDialog = (): void => {
      SDK.showReportDialog({ eventId: this.state.eventId });
    };
    if (this.state.error) {
      return hasSDK ? (
        <ContentWithSDK lastEventId={this.state.eventId} showReportDialog={showReportDialog} />
      ) : (
        <ContentWithoutSDK error={this.state.error} info={this.state.info} />
      );
    }
    return this.props.children || null;
  }
}

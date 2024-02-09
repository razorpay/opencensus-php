import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

export const trackPOSBannerLoaded = (): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Partner Dashboard POS Banner',
    actionName: 'Loaded',
    screen: 'Partner Dashboard Home',
    properties: {
      screen: 'partner_dashboard_homepage',
    },
  });
};

export const trackPartnerHomepageCtaClicked = ({ ctaClicked }: { ctaClicked: string }): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Partner Dashboard Homepage Cta',
    actionName: 'Clicked',
    screen: 'Partner Dashboard Home',
    properties: {
      screen: 'partner_dashboard_homepage',
      ctaClicked,
    },
  });
};

export const trackInviteNewMemberModalLoaded = ({ screen }: { screen: string }): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Invite New Member Modal',
    actionName: 'Loaded',
    screen: 'Partner Dashboard Home',
    properties: {
      screen,
    },
  });
};

export const trackInviteNewMemberModalClicked = ({
  ctaClicked,
  screen,
}: {
  ctaClicked: string;
  screen: string;
}): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Invite New Member Modal Cta',
    actionName: 'Clicked',
    screen: 'Partner Dashboard Home',
    properties: {
      screen,
      ctaClicked,
    },
  });
};

export const trackAgentInviteCtaClicked = ({ screen }: { screen: string }): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Agent Invite Cta',
    actionName: 'Clicked',
    screen: 'Partner Dashboard Home',
    properties: {
      screen,
    },
  });
};

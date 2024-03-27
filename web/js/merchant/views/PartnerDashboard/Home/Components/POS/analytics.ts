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

export const trackInviteNewMemberModalLoaded = ({
  screen,
  isRenderedFromPartnerRoute,
}: {
  screen: string;
  isRenderedFromPartnerRoute: boolean;
}): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Invite New Member Modal',
    actionName: 'Loaded',
    screen: 'Partner Dashboard Home',
    properties: {
      screen,
      isRenderedFromPartnerRoute,
    },
  });
};

export const trackInviteNewMemberModalClicked = ({
  ctaClicked,
  screen,
  isRenderedFromPartnerRoute,
}: {
  ctaClicked: string;
  screen: string;
  isRenderedFromPartnerRoute: boolean;
}): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Invite New Member Modal Cta',
    actionName: 'Clicked',
    screen: 'Partner Dashboard Home',
    properties: {
      screen,
      ctaClicked,
      isRenderedFromPartnerRoute,
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

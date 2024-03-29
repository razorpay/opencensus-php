import React from 'react';
import { NavLink } from 'react-router-dom';
import { ArrowLeftIcon } from '@razorpay/blade/components';
import ShowWhen from 'merchant/components/ShowWhen';
import WhiteLabelTheme from './WhiteLabelTheme';
import { StyledHeader, StyledLink } from './styles';
import { useSplitzService } from 'common/splitz';

interface ThemeConfigProps {
  appId?: string;
}
interface AppConfigProps {
  match: {
    params: {
      id: string;
    };
  };
  location: {
    state: {
      appName: string;
    };
  };
}
const ThemeConfiguration = ({ appId }: ThemeConfigProps): JSX.Element => (
  <div className="content">
    <div className="content-wrapper " id="partner-configurator-content">
      <WhiteLabelTheme appId={appId} />
    </div>
  </div>
);

export const AppConfiguration = ({
  match: {
    params: { id },
  },
  location: {
    state: { appName },
  },
}: AppConfigProps): JSX.Element => {
  const { abExperiments } = useSplitzService();
  const isExpEnabled =
    abExperiments.partnerships_oauth_phantom_configurator?.variables?.result === 'on';

  return (
    <div className="tabbed-container">
      <header>
        <StyledHeader>
          <StyledLink to="/partners/applications">
            <ArrowLeftIcon
              color="interactive.icon.primary.subtle"
              size="medium"
              marginRight="5px"
            />
            All Applications
          </StyledLink>
          <strong>
            &nbsp;/ {appName}&nbsp;({id})
          </strong>
        </StyledHeader>
      </header>
      <header>
        <NavLink to={`/partners/applications/${id}`}>Integration Settings</NavLink>
        {isExpEnabled ? (
          <NavLink to={`/partners/applications/configuration/${id}`}>
            Onboarding UI Configurator
          </NavLink>
        ) : null}
      </header>
      <ThemeConfiguration appId={id} />
    </div>
  );
};

const Configuration = (): JSX.Element => {
  return (
    <div className="tabbed-container">
      <header>
        <NavLink end to="/partners/settings">
          Settings
        </NavLink>
        <ShowWhen additionalCondition={(user) => user.isPartnershipForPhantomEnabled}>
          <NavLink end to="/partners/config">
            Configuration
          </NavLink>
        </ShowWhen>
      </header>
      <ThemeConfiguration />
    </div>
  );
};

export default Configuration;

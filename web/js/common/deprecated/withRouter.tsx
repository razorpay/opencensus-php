import React, { ComponentType } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { WithRouterProps } from './RouteComponentProps';
/**
 * @deprecated Migrate to a function component and use hooks instead. withRouter was removed in React Router v6.
 * @see https://reactrouter.com/en/main/upgrading/v5#upgrade-to-react-router-v51
 */
export const withRouter =
  <T extends WithRouterProps>(Component: ComponentType<T>) =>
  (props: T): JSX.Element => {
    const location = useLocation();
    const navigate = useNavigate();
    const params = useParams();

    const historyPush = (pushRef, isReplace?: boolean) => {
      switch (true) {
        case typeof pushRef === 'object':
          const { state, hash, pathname, search } = pushRef ?? {};
          return navigate(
            hash || search
              ? {
                  hash,
                  pathname,
                  search,
                }
              : pathname,
            {
              state,
              replace: isReplace,
            },
          );
        default:
          return navigate(pushRef);
      }
    };

    // Mapping reference: https://stackoverflow.com/a/72478436/7435656
    /**
     * @deprecated Not to be used in new code. Use navigate instead.
     * Please check the arg schema before using history.
     */
    const history = {
      push: historyPush,
      replace: (path) => historyPush(path, true),
      go: navigate,
      goBack: () => navigate(-1),
      location: location,
    };

    return (
      <Component
        {...props}
        location={location}
        params={params}
        match={{ params }}
        navigate={navigate}
        history={history}
      />
    );
  };

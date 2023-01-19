import React, { useState } from 'react';
import { connect } from 'react-redux';
import Keys from 'merchant/views/Settings/Keys/List';
import ApiKeysAndPlugins from 'merchant/views/ApiKeysAndPlugins';
import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';
import { APIKeysProps } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/typings';
import { bindActionCreators } from 'redux';

const APIKeysTab = (props: APIKeysProps): JSX.Element => {
  const { user } = props;
  const [isWebsiteInWorkflow, setIsWebsiteInWorkflow] = useState(false);

  const onWebsiteAdd = () => setIsWebsiteInWorkflow(true);

  const shouldShowNewAPIKeysAndPluginsView =
    (user.isProductLedOnboardingRZP || user.isApiKeysRevampEnabled) && user.activated;

  React.useEffect(() => {
    props
      .fetchAddWebsiteWorkflowStatus()
      .then(({ data }) => {
        setIsWebsiteInWorkflow(data);
      })
      .catch(() => {
        // empty catch
      });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return shouldShowNewAPIKeysAndPluginsView ? (
    <ApiKeysAndPlugins />
  ) : (
    <Keys {...props} onWebsiteAdd={onWebsiteAdd} isWebsiteInWorkflow={isWebsiteInWorkflow} />
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchAddWebsiteWorkflowStatus,
    },
    dispatch,
  );

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  mapDispatchToProps,
)(APIKeysTab);

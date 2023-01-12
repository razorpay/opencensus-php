import React, { useState } from 'react';
import { connect } from 'react-redux';
import Keys from 'merchant/views/Settings/Keys/List';
import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';
import { APIKeysProps } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/typings';
import { bindActionCreators } from 'redux';

const APIKeysTab = (props: APIKeysProps): JSX.Element => {
  const [isWebsiteInWorkflow, setIsWebsiteInWorkflow] = useState(false);

  const onWebsiteAdd = () => setIsWebsiteInWorkflow(true);

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

  return <Keys {...props} onWebsiteAdd={onWebsiteAdd} isWebsiteInWorkflow={isWebsiteInWorkflow} />;
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchAddWebsiteWorkflowStatus,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(APIKeysTab);

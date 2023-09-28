import React, { useState, useEffect } from 'react';
import axios from 'axios';
import * as Redux from 'redux';
import * as ReactRedux from 'react-redux';
import * as ReactRouter from 'react-router';
import * as ReactRouterDOM from 'react-router-dom';
import * as ReactDOM from 'react-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { loadScript } from './utils/loadRemoteScript';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';
import { trackEvent as trackSegmentEvent } from './CashAdvance/TrackEvents/trackEvents';

const fetch = (url, data, progressTracker) => {
  return merchantFetch({
    url,
    mode: 'live',
    method: 'post',
    data,
    headers: {
      'Content-Type': 'application/json',
    },
    onUploadProgress: progressTracker,
  });
};

const externals = {
  axios,
  React,
  Redux,
  ReactRedux,
  ReactRouter,
  ReactRouterDOM,
  ReactDOM,
  host: {
    fetch,
    trackSegmentEvent,
  },
};

function RemoteComponent({ project, history, showNotification, user, ...rest }) {
  const [Component, setComponent] = useState(<div className="spinner center" />);

  const notifySuccess = (message) =>
    showNotification({
      type: 'success',
      message,
    });

  const notifyError = (message) =>
    showNotification({
      type: 'error',
      message,
    });

  useEffect(() => {
    const updatedExternals = {
      ...externals,
      host: {
        user,
        history,
        notifySuccess,
        notifyError,
        ...externals.host,
      },
    };

    loadScript(project, updatedExternals)
      .then((Output) => {
        setComponent(<Output {...rest} history={history} />);
      })
      .catch((e) => {
        console.error(e);
        setComponent(<div>Error</div>);
      });
  }, []);

  return Component;
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = {
  showNotification,
};

export default withRouter(ReactRedux.connect(mapStateToProps, mapDispatchToProps)(RemoteComponent));

import React from 'react';
import { Navigate, Route } from 'react-router-dom';
export default class PaypalOnboardRedirect extends React.Component {
  render() {
    return <Route path="*" element={<Navigate to="/config" replace />} />;
  }
}

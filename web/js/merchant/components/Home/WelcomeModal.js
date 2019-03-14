import React from 'react';
import { Link } from 'react-router-dom';

export default ({ onActivate, onClose, isInternationalFeatureRolledOut }) => (
  <div className="welcome-modal-content">
    <h1 className="welcome-title">Welcome to your</h1>
    <h1 className="welcome-title welcome-subtitle">Razorpay Dashboard</h1>
    <p>Get started with accepting payments right away.</p>
    <p>
      You are just one step away form activating your account to accept domestic{' '}
      {isInternationalFeatureRolledOut ? ' and international ' : ' '} payments
      from your customers. We just need a few more details.
    </p>
    <div className="welcome-modal-actions">
      <Link to="/activation" onClick={onActivate} className="btn btn-primary">
        Activate your account
      </Link>
      <span className="btn-link m-l cursor-pointer" onClick={onClose}>
        Try out the Dashboard
      </span>
    </div>
  </div>
);

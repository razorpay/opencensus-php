import React from 'react';
import { Link } from 'react-router-dom';

export default ({ onClose }) => (
  <div className="welcome-modal-content">
    <h1 className="text-primary welcome-title">Welcome to your</h1>
    <h1 className="welcome-title welcome-subtitle">Razorpay Dashboard</h1>
    <p style={{ marginTop: '80px' }}>
      You are just a step away from accepting live online payments!
    </p>
    <p>
      Get started with Razorpay and start accepting domestic online payments
      using products like Payment Links, Payments Pages or by simply integrating
      the Checkout with your website or mobile application.
    </p>
    <div className="welcome-modal-actions">
      <Link to="/activation" onClick={onClose} className="btn btn-primary">
        Activate your account
      </Link>
      <span className="btn-link m-l cursor-pointer" onClick={onClose}>
        Try out the Dashboard
      </span>
    </div>
  </div>
);

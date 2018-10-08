import React from 'react';
import { Link } from 'react-router-dom';

export default ({ onClose }) => (
  <div className="welcome-modal-content">
    <h1 className="text-primary welcome-title">Welcome to your</h1>
    <h1 className="welcome-title welcome-subtitle">Razorpay Dashboard</h1>

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

import React from 'react';
import { Link } from 'react-router-dom';

export default () => (
  <div className="Announcement_Banner">
    You can personalize your account to add your logo, add brand colour and do
    lots more<div className="big-dot-separator" />
    <Link to="/config">Personalize account</Link>
  </div>
);

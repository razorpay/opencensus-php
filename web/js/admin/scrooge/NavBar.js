import React, { Component } from 'react';
import { Link } from 'react-router-dom';

export default class NavBar extends Component {
  render() {
    const { active } = this.props;
    return (
      <div className="box refunds-tabs-box">
        <ul className="tabs-nav">
          <li className={active === 'refunds' ? 'selected' : ''}>
            <Link to={`/scrooge/refunds`}>Refunds</Link>
          </li>
          <li className={active === 'reports' ? 'selected' : ''}>
            <Link to={`/scrooge/reports`}>Failed Reports</Link>
          </li>
          <li className={active === 'actions' ? 'selected' : ''}>
            <Link to={`/scrooge/actions`}>Actions</Link>
          </li>
        </ul>
      </div>
    );
  }
}

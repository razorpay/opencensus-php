import React from 'react';

export default function LoanStatusFooter({ icon, children }) {
  return (
    <footer className="flex">
      {icon ? <i className={icon} /> : null}
      <div className="ml-8">{children}</div>
    </footer>
  );
}

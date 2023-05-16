const ErrorContainer = ({ message, action }) => {
  return (
    <div className="bottom-container">
      <i className="i i-info-outline" data-testid="info-icon" />
      <div className="error-message">
        {message && <p className="message">{message}</p>}
        {action && <div className="action">{action}</div>}
      </div>
    </div>
  );
};

export default ErrorContainer;

import LoaderDots from 'common/ui/LoaderDots';

export default ({ className, content, title, loading, error }) => {
  const renderContent = () => {
    if (loading) {
      return <LoaderDots />;
    }
    if (error) {
      return <small className="text-danger font-sm">Error!</small>;
    }
    if (typeof content === 'function') {
      return <div className="h1">{content()}</div>;
    }
    return <div className="h1">{content}</div>;
  };

  return (
    <div className="col-xs-12 col-sm-6 col-md-4 col-lg-6">
      <div className={`panel InfoCard ${className}`}>
        <div className="InfoCard__value">{renderContent()}</div>
        <span className="InfoCard__label">{title}</span>
      </div>
    </div>
  );
};

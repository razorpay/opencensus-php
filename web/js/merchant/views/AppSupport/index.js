import Loader from 'common/components/Loader';
const AppSupport = () => {
  // will only return loader and support component will auto open modal for webview
  return (
    <div className="full-view-wizard app-support-wrapper">
      <Loader />
    </div>
  );
};

export default AppSupport;
